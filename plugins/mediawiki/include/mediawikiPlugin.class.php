<?php
/**
 * MediaWikiPlugin Class
 *
 * Copyright 2000-2011, Fusionforge Team
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright (c) Enalean SAS 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/plugin/Plugin.class.php';
require_once 'constants.php';

class MediaWikiPlugin extends Plugin {

    const SERVICE_SHORTNAME = 'plugin_mediawiki';

    function __construct ($id=0) {
            $this->Plugin($id) ;
            $this->name = "mediawiki" ;
            $this->text = "Mediawiki" ; // To show in the tabs, use...
            $this->_addHook("groupmenu") ;	// To put into the project tabs
            $this->_addHook("groupisactivecheckbox") ; // The "use ..." checkbox in editgroupinfo
            $this->_addHook("groupisactivecheckboxpost") ; //
            $this->_addHook("project_public_area");
            $this->_addHook("role_get");
            $this->_addHook("role_normalize");
            $this->_addHook("role_translate_strings");
            $this->_addHook("role_has_permission");
            $this->_addHook("role_get_setting");
            $this->_addHook("list_roles_by_permission");
            $this->_addHook("clone_project_from_template") ;
            $this->_addHook('group_delete');
            $this->_addHook('cssfile');
            $this->addHook(Event::SERVICE_ICON);
            $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
            $this->addHook(Event::PROCCESS_SYSTEM_CHECK);

            $this->addHook('permission_get_name');
            $this->_addHook('service_is_used');
            $this->_addHook('register_project_creation');

            $this->_addHook(Event::SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK);
            $this->_addHook(Event::RENAME_PROJECT, 'rename_project');
            $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');
            $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);

            //User permissions
            $this->_addHook('project_admin_remove_user');
            $this->_addHook('project_admin_change_user_permissions');
            $this->_addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
            $this->_addHook('project_admin_ugroup_remove_user');
            $this->_addHook('project_admin_remove_user_from_project_ugroups');
            $this->_addHook('project_admin_ugroup_deletion');
            $this->_addHook(Event::HAS_USER_BEEN_DELEGATED_ACCESS, 'has_user_been_delegated_access');
            $this->addHook(Event::IS_SCRIPT_HANDLED_FOR_RESTRICTED);
            $this->addHook(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED);

            // Search
            $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
            $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
            $this->addHook(Event::SEARCH_TYPE);

            $this->_addHook('plugin_statistics_service_usage');

            $this->addHook(Event::SERVICE_CLASSNAMES);
            $this->addHook(Event::GET_PROJECTID_FROM_URL);

            // Stats plugin
            $this->addHook('plugin_statistics_disk_usage_collect_project');
            $this->addHook('plugin_statistics_disk_usage_service_label');
            $this->addHook('plugin_statistics_color');

            // Site admin link
            $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);

            $this->addHook(Event::PROJECT_ACCESS_CHANGE);
            $this->addHook(Event::SITE_ACCESS_CHANGE);

            /**
             * HACK
             */
            require_once MEDIAWIKI_BASE_DIR.'/../../fusionforge_compat/include/fusionforge_compatPlugin.class.php';
            $ff_plugin = new fusionforge_compatPlugin();
            $ff_plugin->loaded();
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e812';
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('fusionforge_compat');
    }

        public function loaded() {
            parent::loaded();
            require_once 'plugins_utils.php';
            if(is_dir("/usr/share/mediawiki")){
                forge_define_config_item('src_path','mediawiki', "/usr/share/mediawiki");
                forge_define_config_item('mwdata_path', 'mediawiki', '$core/data_path/plugins/mediawiki');
                forge_define_config_item('projects_path', 'mediawiki', '$mediawiki/mwdata_path/projects');
                forge_define_config_item('master_path', 'mediawiki', '$mediawiki/mwdata_path/master');
                forge_define_config_item('enable_uploads', 'mediawiki', false);
                forge_set_config_item_bool('enable_uploads', 'mediawiki');
            }
        }

        public function layout_search_entry($params) {
            $project = $this->getProjectFromRequest();
            if ($this->isSearchEntryAvailable($project)) {
                $params['search_entries'][] = array(
                    'value'    => $this->name,
                    'label'    => $this->text,
                    'selected' => $this->isSearchEntrySelected($params['type_of_search']),
                );
                $params['hidden_fields'][] = array(
                    'name'  => 'group_id',
                    'value' => $project->getID()
                );
            }
        }

        /**
         * @see Event::SEARCH_TYPE
         */
        public function search_type($params) {
            $query   = $params['query'];
            $project = $query->getProject();

            if ($query->getTypeOfSearch() == $this->name && $this->isSearchEntryAvailable($project)) {
                if (! $project->isError()) {
                   util_return_to($this->getMediawikiSearchURI($project, $query->getWords()));
                }
            }
        }

        /**
         * @see Event::SEARCH_TYPES_PRESENTERS
         */
        public function search_types_presenters($params) {
            if ($this->isSearchEntryAvailable($params['project'])) {
                $params['project_presenters'][] = new Search_SearchTypePresenter(
                    $this->name,
                    $this->text,
                    array(),
                    $this->getMediawikiSearchURI($params['project'], $params['words'])
                );
            }
        }

    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check($params) {
        $this->getMediawikiMLEBExtensionManager()->activateMLEBForCompatibleProjects($params['logger']);
    }

        private function getMediawikiSearchURI(Project $project, $words) {
            return $this->getPluginPath().'/wiki/'. $project->getUnixName() .'/index.php?title=Special%3ASearch&search=' . urlencode($words) . '&go=Go';
        }

        private function isSearchEntryAvailable(Project $project = null) {
            if ($project && ! $project->isError()) {
                return $project->usesService(self::SERVICE_SHORTNAME);
            }
            return false;
        }

        private function isSearchEntrySelected($type_of_search) {
            return ($type_of_search == $this->name) || $this->isMediawikiUrl();
        }

        private function isMediawikiUrl() {
            return preg_match('%'.$this->getPluginPath().'/wiki/.*%', $_SERVER['REQUEST_URI']);
        }

        /**
         *
         * @return Project | null
         */
        private function getProjectFromRequest() {
            $matches = array();
            preg_match('%'.$this->getPluginPath().'/wiki/([^/]+)/.*%', $_SERVER['REQUEST_URI'], $matches);
            if (isset($matches[1])) {
                $project = ProjectManager::instance()->getProjectByUnixName($matches[1]);

                if ($project->isError()) {
                    $project = ProjectManager::instance()->getProject($matches[1]);
                }

                if (! $project->isError()) {
                    return $project;
                }
            }
            return null;
        }

        public function cssFile($params) {
            // Only show the stylesheet if we're actually in the Mediawiki pages.
            if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
                strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0) {
                echo '<link rel="stylesheet" type="text/css" href="/plugins/mediawiki/themes/default/css/style.css" />';
            }
        }

        public function showImage(Codendi_Request $request) {
            $project = $this->getProjectFromRequest();
            $user    = $request->getCurrentUser();

            if (! $project) {
                exit;
            }

            if ((! $project->isPublic() || $user->isRestricted())
                && ! $project->userIsMember()
                && ! $user->isSuperUser()
                && ! $this->doesUserHavePermission($user)
            ) {
                exit;
            }

            preg_match('%'.$this->getPluginPath().'/wiki/[^/]+/images(.*)%', $_SERVER['REQUEST_URI'], $matches);
            $file_location = $matches[1];

            $folder_location = '';
            if (is_dir('/var/lib/codendi/mediawiki/projects/' . $project->getUnixName())) {
                $folder_location = '/var/lib/codendi/mediawiki/projects/' . $project->getUnixName().'/images';
            } elseif (is_dir('/var/lib/codendi/mediawiki/projects/' . $project->getId())) {
                $folder_location = '/var/lib/codendi/mediawiki/projects/' . $project->getId().'/images';
            } else {
                exit;
            }

            $file = $folder_location.$file_location;
            if (! file_exists($file)) {
                exit;
            }

            $size = getimagesize($file);
            $fp   = fopen($file, 'r');

            if ($size and $fp) {
                header('Content-Type: '.$size['mime']);
                header('Content-Length: '.filesize($file));

                readfile($file);
                exit;
            }
        }

        function process() {
        echo '<h1>Mediawiki</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
        }

        function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'MediaWikiPluginInfo')) {
            require_once 'MediaWikiPluginInfo.class.php';
            $this->pluginInfo = new MediaWikiPluginInfo($this);
        }
        return $this->pluginInfo;
    }

        public function service_replace_template_name_in_link($params) {
            $params['link'] = preg_replace(
                '#/plugins/mediawiki/wiki/'.preg_quote($params['template']['name']).'(/|$)#',
                '/plugins/mediawiki/wiki/'. $params['project']->getUnixName().'$1',
                $params['link']
            );
        }

    function CallHook ($hookname, &$params) {
        if (isset($params['group_id'])) {
            $group_id=$params['group_id'];
        } elseif (isset($params['group'])) {
            $group_id=$params['group'];
        } else {
            $group_id=null;
        }
        if ($hookname == "groupmenu") {
            $project = group_get_object($group_id);
            if (!$project || !is_object($project)) {
                return;
            }
            if ($project->isError()) {
                return;
            }
            if (!$project->isProject()) {
                return;
            }
            if ( $project->usesPlugin ( $this->name ) ) {
                $params['TITLES'][]=$this->text;
                $params['DIRS'][]=util_make_url('/plugins/mediawiki/wiki/'.$project->getUnixName().'/index.php');
                $params['ADMIN'][]='';
                $params['TOOLTIPS'][] = _('Mediawiki Space');
            }
            (($params['toptab'] == $this->name) ? $params['selected']=(count($params['TITLES'])-1) : '' );
        } elseif ($hookname == "groupisactivecheckbox") {
            //Check if the group is active
            // this code creates the checkbox in the project edit public info page to activate/deactivate the plugin
            $group = group_get_object($group_id);
            echo "<tr>";
            echo "<td>";
            echo ' <input type="checkbox" name="use_mediawikiplugin" value="1" ';
            // checked or unchecked?
            if ( $group->usesPlugin ( $this->name ) ) {
                echo "checked";
            }
            echo " /><br/>";
            echo "</td>";
            echo "<td>";
            echo "<strong>Use ".$this->text." Plugin</strong>";
            echo "</td>";
            echo "</tr>";
        } elseif ($hookname == "groupisactivecheckboxpost") {
            // this code actually activates/deactivates the plugin after the form was submitted in the project edit public info page
            $group = group_get_object($group_id);
            $use_mediawikiplugin = getStringFromRequest('use_mediawikiplugin');
            if ( $use_mediawikiplugin == 1 ) {
                $group->setPluginUse ( $this->name );
            } else {
                $group->setPluginUse ( $this->name, false );
            }
        } elseif ($hookname == "project_public_area") {
            $project = group_get_object($group_id);
            if (!$project || !is_object($project)) {
                return;
            }
            if ($project->isError()) {
                return;
            }
            if (!$project->isProject()) {
                return;
            }
            if ( $project->usesPlugin ( $this->name ) ) {
                echo '<div class="public-area-box">';
                print '<a href="'. util_make_url ('/plugins/mediawiki/wiki/'.$project->getUnixName().'/index.php').'">';
                print html_abs_image(util_make_url ('/plugins/mediawiki/wiki/'.$project->getUnixName().'/skins/fusionforge/wiki.png'),'20','20',array('alt'=>'Mediawiki'));
                print ' Mediawiki';
                print '</a>';
                echo '</div>';
            }
        } elseif ($hookname == "role_get") {
            $role =& $params['role'] ;

            // Read access
            $right = new PluginSpecificRoleSetting ($role,
                                'plugin_mediawiki_read') ;
            $right->SetAllowedValues (array ('0', '1')) ;
            $right->SetDefaultValues (array ('Admin' => '1',
                             'Senior Developer' => '1',
                             'Junior Developer' => '1',
                             'Doc Writer' => '1',
                             'Support Tech' => '1')) ;

            // Edit privileges
            $right = new PluginSpecificRoleSetting ($role,
                                'plugin_mediawiki_edit') ;
            $right->SetAllowedValues (array ('0', '1', '2', '3')) ;
            $right->SetDefaultValues (array ('Admin' => '3',
                             'Senior Developer' => '2',
                             'Junior Developer' => '1',
                             'Doc Writer' => '3',
                             'Support Tech' => '0')) ;

            // File upload privileges
            $right = new PluginSpecificRoleSetting ($role,
                                'plugin_mediawiki_upload') ;
            $right->SetAllowedValues (array ('0', '1', '2')) ;
            $right->SetDefaultValues (array ('Admin' => '2',
                             'Senior Developer' => '2',
                             'Junior Developer' => '1',
                             'Doc Writer' => '2',
                             'Support Tech' => '0')) ;

            // Administrative tasks
            $right = new PluginSpecificRoleSetting ($role,
                                'plugin_mediawiki_admin') ;
            $right->SetAllowedValues (array ('0', '1')) ;
            $right->SetDefaultValues (array ('Admin' => '1',
                             'Senior Developer' => '0',
                             'Junior Developer' => '0',
                             'Doc Writer' => '0',
                             'Support Tech' => '0')) ;

        } elseif ($hookname == "role_normalize") {
            $role =& $params['role'] ;
            $new_sa =& $params['new_sa'] ;
            $new_pa =& $params['new_pa'] ;

            $projects = $role->getLinkedProjects() ;
            foreach ($projects as $p) {
                $role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_read', $p->getID()) ;
                $role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_edit', $p->getID()) ;
                $role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_upload', $p->getID()) ;
                $role->normalizePermsForSection ($new_pa, 'plugin_mediawiki_admin', $p->getID()) ;
            }
        } elseif ($hookname == "role_translate_strings") {
            $right = new PluginSpecificRoleSetting ($role,
                                   'plugin_mediawiki_read') ;
            $right->setDescription (_('Mediawiki read access')) ;
            $right->setValueDescriptions (array ('0' => _('No reading'),
                                 '1' => _('Read access'))) ;

            $right = new PluginSpecificRoleSetting ($role,
                                   'plugin_mediawiki_edit') ;
            $right->setDescription (_('Mediawiki write access')) ;
            $right->setValueDescriptions (array ('0' => _('No editing'),
                                 '1' => _('Edit existing pages only'),
                                 '2' => _('Edit and create pages'),
                                 '3' => _('Edit, create, move, delete pages'))) ;

            $right = new PluginSpecificRoleSetting ($role,
                                   'plugin_mediawiki_upload') ;
            $right->setDescription (_('Mediawiki file upload')) ;
            $right->setValueDescriptions (array ('0' => _('No uploading'),
                                 '1' => _('Upload permitted'),
                                 '2' => _('Upload and re-upload'))) ;

            $right = new PluginSpecificRoleSetting ($role,
                                   'plugin_mediawiki_admin') ;
            $right->setDescription (_('Mediawiki administrative tasks')) ;
            $right->setValueDescriptions (array ('0' => _('No administrative access'),
                                 '1' => _('Edit interface, import XML dumps'))) ;
        } elseif ($hookname == "role_get_setting") {
            $role = $params['role'] ;
            $reference = $params['reference'] ;
            $value = $params['value'] ;

            switch ($params['section']) {
            case 'plugin_mediawiki_read':
                if ($role->hasPermission('project_admin', $reference)) {
                    $params['result'] = 1 ;
                } else {
                    $params['result'] =  $value ;
                }
                break ;
            case 'plugin_mediawiki_edit':
                if ($role->hasPermission('project_admin', $reference)) {
                    $params['result'] = 3 ;
                } else {
                    $params['result'] =  $value ;
                }
                break ;
            case 'plugin_mediawiki_upload':
                if ($role->hasPermission('project_admin', $reference)) {
                    $params['result'] = 2 ;
                } else {
                    $params['result'] =  $value ;
                }
                break ;
            case 'plugin_mediawiki_admin':
                if ($role->hasPermission('project_admin', $reference)) {
                    $params['result'] = 1 ;
                } else {
                    $params['result'] =  $value ;
                }
                break ;
            }
        } elseif ($hookname == "role_has_permission") {
            $value = $params['value'];
            switch ($params['section']) {
            case 'plugin_mediawiki_read':
                switch ($params['action']) {
                case 'read':
                default:
                    $params['result'] |= ($value >= 1) ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_edit':
                switch ($params['action']) {
                case 'editexisting':
                    $params['result'] |= ($value >= 1) ;
                    break ;
                case 'editnew':
                    $params['result'] |= ($value >= 2) ;
                    break ;
                case 'editmove':
                    $params['result'] |= ($value >= 3) ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_upload':
                switch ($params['action']) {
                case 'upload':
                    $params['result'] |= ($value >= 1) ;
                    break ;
                case 'reupload':
                    $params['result'] |= ($value >= 2) ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_admin':
                switch ($params['action']) {
                case 'admin':
                default:
                    $params['result'] |= ($value >= 1) ;
                    break ;
                }
                break ;
            }
        } elseif ($hookname == "list_roles_by_permission") {
            switch ($params['section']) {
            case 'plugin_mediawiki_read':
                switch ($params['action']) {
                case 'read':
                default:
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_edit':
                switch ($params['action']) {
                case 'editexisting':
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
                    break ;
                case 'editnew':
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 2') ;
                    break ;
                case 'editmove':
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 3') ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_upload':
                switch ($params['action']) {
                case 'upload':
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
                    break ;
                case 'reupload':
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 2') ;
                    break ;
                }
                break ;
            case 'plugin_mediawiki_admin':
                switch ($params['action']) {
                case 'admin':
                default:
                    $params['qpa'] = db_construct_qpa ($params['qpa'], ' AND perm_val >= 1') ;
                    break ;
                }
                break ;
            }
        } elseif ($hookname == "clone_project_from_template") {
            $template = $params['template'] ;
            $project = $params['project'] ;
            $id_mappings = $params['id_mappings'] ;

            $sections = array ('plugin_mediawiki_read', 'plugin_mediawiki_edit', 'plugin_mediawiki_upload', 'plugin_mediawiki_admin') ;

            foreach ($template->getRoles() as $oldrole) {
                $newrole = RBACEngine::getInstance()->getRoleById ($id_mappings['role'][$oldrole->getID()]) ;
                $oldsettings = $oldrole->getSettingsForProject ($template) ;

                foreach ($sections as $section) {
                    if (isset ($oldsettings[$section][$template->getID()])) {
                        $newrole->setSetting ($section, $project->getID(), $oldsettings[$section][$template->getID()]) ;
                    }
                }
            }
        } elseif ($hookname == 'group_delete') {
            $projectId = $params['group_id'];
            $projectObject = group_get_object($projectId);
            if ($projectObject->usesPlugin($this->name)) {
                //delete the files and db schema
                $schema = 'plugin_mediawiki_'.$projectObject->getUnixName();
                // Sanitize schema name
                $schema = strtr($schema, "-", "_");
                db_query_params('drop schema $1 cascade', array($schema));
                exec('/bin/rm -rf '.forge_get_config('projects_path', 'mediawiki').'/'.$projectObject->getUnixName());
            }
        }
    }

    public function register_project_creation($params) {
        if ($this->serviceIsUsedInTemplate($params['template_id'])) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiateFromTemplate($params['ugroupsMapping']);
            }
        }
    }

    public function has_user_been_delegated_access($params) {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {

            /**
             * Only change the access rights to the affirmative.
             * Otherwise, we could overwrite a "true" value set by another plugin.
             */
            if ($this->doesUserHavePermission($params['user'])) {
                $params['can_access'] = true;
            }
        }
    }

    private function doesUserHavePermission(PFUser $user) {
        $forge_user_manager = $this->getForgeUserGroupPermissionsManager();

        return $forge_user_manager->doesUserHavePermission(
            $user,
            new User_ForgeUserGroupPermission_MediawikiAdminAllProjects()
        );
    }

    /**
     * @return User_ForgeUserGroupPermissionsManager
     */
    private function getForgeUserGroupPermissionsManager() {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * @see Event::IS_SCRIPT_HANDLED_FOR_RESTRICTED
     */
    public function is_script_handled_for_restricted($params) {
        $uri = $params['uri'];
        if (strpos($uri, $this->getPluginPath()) === 0) {
            $params['allow_restricted'] = true;
        }
    }

    /**
     * @see Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED
     */
    public function get_services_allowed_for_restricted($params) {
        $params['allowed_services'][] = $this->getServiceShortname();
    }

    private function serviceIsUsedInTemplate($project_id) {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($project_id);

        return $project->usesService(self::SERVICE_SHORTNAME);
    }

    public function service_is_used($params) {
        if ($params['shortname'] == 'plugin_mediawiki' && $params['is_used']) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    private function getInstantiater($group_id) {
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($group_id);

        if (! $project instanceof Project || $project->isError()) {
            return;
        }

        include dirname(__FILE__) .'/MediawikiInstantiater.class.php';

        return new MediaWikiInstantiater(
            $project,
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiMLEBExtensionManager()
        );
    }

    private function getMediawikiLanguageManager() {
        require_once 'MediawikiLanguageManager.php';
        require_once 'MediawikiLanguageDao.php';

        return new MediawikiLanguageManager(new MediawikiLanguageDao());
    }

    public function plugin_statistics_service_usage($params) {
        require_once 'MediawikiDao.class.php';

        $dao             = new MediawikiDao();
        $project_manager = ProjectManager::instance();
        $start_date      = $params['start_date'];
        $end_date        = $params['end_date'];

        $number_of_page                   = array();
        $number_of_page_between_two_dates = array();
        $number_of_page_since_a_date      = array();
        foreach($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE) as $project) {
            if ($project->usesService('plugin_mediawiki')) {
                $number_of_page[] = $dao->getMediawikiPagesNumberOfAProject($project);
                $number_of_page_between_two_dates[] = $dao->getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate($project, $start_date, $end_date);
                $number_of_page_since_a_date[] = $dao->getCreatedPagesNumberSinceStartDate($project, $start_date);
            }
        }

        $params['csv_exporter']->buildDatas($number_of_page, "Mediawiki Pages");
        $params['csv_exporter']->buildDatas($number_of_page_between_two_dates, "Modified Mediawiki pages");
        $params['csv_exporter']->buildDatas($number_of_page_since_a_date, "Number of created Mediawiki pages since start date");
    }

    public function project_admin_ugroup_deletion($params) {
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->deleteUserGroup($project->getID(), $params['ugroup_id']);
            $dao->resetUserGroups($project);
        }
    }

    public function project_admin_remove_user($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_ugroup_remove_user($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_change_user_permissions($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_remove_user_from_project_ugroups($params) {
        $this->updateUserGroupMapping($params);
    }

    private function updateUserGroupMapping($params) {
        $user    = $this->getUserFromParams($params);
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->resetUserGroupsForUser($user, $project);
        }
    }

    public function systemevent_user_rename($params) {
        $user            = $params['user'];
        $projects        = ProjectManager::instance()->getAllProjectsButDeleted();
        foreach ($projects as $project) {
            if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
                $this->getDao()->renameUser($project, $params['old_user_name'], $user->getUnixName());
            }
        }
    }

    private function getUserFromParams($params) {
        $user_id  = $params['user_id'];

        return UserManager::instance()->getUserById($user_id);
    }

    private function getProjectFromParams($params) {
        $group_id = $params['group_id'];

        return ProjectManager::instance()->getProject($group_id);
    }

    private function getDao() {
        return new MediawikiDao();
    }

    /**
     * @return MediawikiManager
     */
    private function getMediawikiManager() {
        include_once 'MediawikiManager.class.php';

        return new MediawikiManager($this->getDao());
    }

    public function service_classnames(array $params) {
        include_once 'ServiceMediawiki.class.php';
        $params['classnames']['plugin_mediawiki'] = 'ServiceMediawiki';
    }

    public function rename_project($params) {
        $project         = $params['project'];
        $project_manager = ProjectManager::instance();
        $new_link        = '/plugins/mediawiki/wiki/'. $params['new_name'];

        if (! $project_manager->renameProjectPluginServiceLink($project->getID(), self::SERVICE_SHORTNAME, $new_link)) {
            $params['success'] = false;
            return;
        }

        $this->updateMediawikiDirectory($project);
        $this->clearMediawikiCache($project);
    }

    private function updateMediawikiDirectory(Project $project) {
        $logger         = new BackendLogger();
        $project_id_dir = forge_get_config('projects_path', 'mediawiki') . "/". $project->getID() ;

        if (is_dir($project_id_dir)) {
            return true;
        }

        $project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getUnixName();
        if (is_dir($project_name_dir)) {
            exec("mv $project_name_dir $project_id_dir");
            return true;
        }

        $logger->error('Project Rename: Can\'t find mediawiki directory for project: '.$project->getID());
        return false;
    }

    private function clearMediawikiCache(Project $project) {
        $schema = $this->getDao()->getMediawikiDatabaseName($project, false);
        $logger = new BackendLogger();

        if ($schema) {
            $delete = $this->getDao()->clearPageCacheForSchema($schema);
            if (! $delete) {
                $logger->error('Project Clear cache: Can\'t delete mediawiki cache for schema: '.$schema);
            }
        } else  {
            $logger->error('Project Clear cache: Can\'t find mediawiki db for project: '.$project->getID());
        }
    }

    public function get_projectid_from_url($params) {
        $url = $params['url'];

        if (strpos($url,'/plugins/mediawiki/wiki/') === 0) {
            $pieces       = explode("/", $url);
            $project_name = $pieces[4];

            $dao          = $params['project_dao'];
            $dao_results  = $dao->searchByUnixGroupName($project_name);
            if ($dao_results->rowCount() < 1) {
                // project does not exist
                return false;
            }

            $project_data         = $dao_results->getRow();
            $params['project_id'] = $project_data['group_id'];
        }
    }

    public function plugin_statistics_disk_usage_collect_project($params) {
        $row     = $params['project_row'];
        $project = ProjectManager::instance()->getProject($row['group_id']);

        $project_for_parth = $this->getMediawikiManager()->instanceUsesProjectID($project) ?
            $row['group_id'] : $row['unix_group_name'];

        $path = $GLOBALS['sys_data_dir']. '/mediawiki/projects/'. $project_for_parth;
        $size = $params['DiskUsageManager']->getDirSize($path);

        $params['DiskUsageManager']->_getDao()->addGroup(
            $row['group_id'],
            self::SERVICE_SHORTNAME,
            $size,
            $_SERVER['REQUEST_TIME']
        );
    }

    public function plugin_statistics_disk_usage_service_label($params) {
        $params['services'][self::SERVICE_SHORTNAME] = 'Mediawiki';
    }

    public function plugin_statistics_color($params) {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'lightsalmon';
        }
    }

    public function site_admin_option_hook() {
        echo '<li><a href="'.$this->getPluginPath().'/forge_admin?action=site_index'.'">Mediawiki</a></li>';
    }

    public function system_event_get_types_for_default_queue(array &$params) {
        require_once 'events/SytemEvent_MEDIAWIKI_SWITCH_TO_123.class.php';

        $params['types'] = array_merge($params['types'], array(
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME
        ));
    }

    public function getSystemEventClass($params) {
        require_once 'events/SytemEvent_MEDIAWIKI_SWITCH_TO_123.class.php';

        switch($params['type']) {
            case SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME:
                $params['class'] = 'SystemEvent_MEDIAWIKI_SWITCH_TO_123';
                $params['dependencies'] = array(
                    $this->getMediawikiMigrator(),
                    $this->getProjectManager(),
                    $this->getMediawikiVersionManager(),
                    $this->getMediawikiMLEBExtensionManager()
                );
                break;
            default:
                break;
        }
    }

    private function getMediawikiMigrator() {
        require_once 'Migration/MediawikiMigrator.php';

        return new Mediawiki_Migration_MediawikiMigrator();
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    public function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
                case MediawikiManager::READ_ACCESS:
                    $params['name'] = 'Read';
                    break;
                case MediawikiManager::WRITE_ACCESS:
                    $params['name'] = 'Write';
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @see Event::PROJECT_ACCESS_CHANGE
     */
    public function project_access_change($params) {
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $this->getMediawikiManager()->updateAccessControlInProjectChangeContext(
            $project,
            $params['old_access'],
            $params['access']
        );
    }

    /**
     * @see Event::SITE_ACCESS_CHANGE
     */
    public function site_access_change($params) {
        $this->getMediawikiManager()->updateSiteAccess($params['old_value']);
    }

    private function getMediawikiVersionManager() {
        require_once 'MediawikiVersionManager.php';
        require_once 'MediawikiVersionDao.php';

        return new MediawikiVersionManager(new MediawikiVersionDao());
    }

    /**
     * @return MediawikiMLEBExtensionManager
     */
    private function getMediawikiMLEBExtensionManager() {
        require_once 'MediawikiMLEBExtensionManager.php';

        return new MediawikiMLEBExtensionManager(
            $this->getMediawikiMigrator(),
            $this->getMediawikiMLEBExtensionDao(),
            $this->getProjectManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiLanguageManager()
        );
    }

    private function getMediawikiMLEBExtensionDao() {
        require_once 'MediawikiMLEBExtensionDao.php';

        return new MediawikiMLEBExtensionDao();
    }

}
