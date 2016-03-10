<?php
/**
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
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

require_once('common/plugin/Plugin.class.php');
require_once 'constants.php';
require_once 'autoload.php';

/**
 * trackerPlugin
 */
class trackerPlugin extends Plugin {

    const EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE      = 'forge__artifacts';
    const EMAILGATEWAY_INSECURE_ARTIFACT_CREATION = 'forge__tracker';
    const EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE   = 'forge__artifact';
    const SERVICE_SHORTNAME                       = 'plugin_tracker';
    const TRUNCATED_SERVICE_NAME                  = 'Trackers';


    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->_addHook('cssfile',                             'cssFile',                           false);
        $this->_addHook(Event::GET_AVAILABLE_REFERENCE_NATURE, 'get_available_reference_natures',   false);
        $this->_addHook(Event::GET_ARTIFACT_REFERENCE_GROUP_ID,'get_artifact_reference_group_id',   false);
        $this->_addHook(Event::SET_ARTIFACT_REFERENCE_GROUP_ID);
        $this->_addHook(Event::BUILD_REFERENCE,                'build_reference',                   false);
        $this->_addHook('ajax_reference_tooltip',              'ajax_reference_tooltip',            false);
        $this->_addHook(Event::SERVICE_CLASSNAMES,             'service_classnames',                false);
        $this->_addHook(Event::COMBINED_SCRIPTS,               'combined_scripts',                  false);
        $this->_addHook(Event::JAVASCRIPT,                     'javascript',                        false);
        $this->_addHook(Event::TOGGLE,                         'toggle',                            false);
        $this->_addHook(Event::SERVICE_PUBLIC_AREAS,           'service_public_areas',              false);
        $this->_addHook('permission_get_name',                 'permission_get_name',               false);
        $this->_addHook('permission_get_object_type',          'permission_get_object_type',        false);
        $this->_addHook('permission_get_object_name',          'permission_get_object_name',        false);
        $this->_addHook('permission_get_object_fullname',      'permission_get_object_fullname',    false);
        $this->_addHook('permission_user_allowed_to_change',   'permission_user_allowed_to_change', false);
        $this->_addHook('permissions_for_ugroup',              'permissions_for_ugroup',            false);

        $this->_addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS,         'getSystemEventClass',               false);

        $this->_addHook('url_verification_instance',           'url_verification_instance',         false);

        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        $this->addHook('widget_instance');
        $this->addHook('widgets');
        $this->addHook('default_widgets_for_new_owner');

        $this->_addHook('project_is_deleted',                  'project_is_deleted',                false);
        $this->_addHook('register_project_creation',           'register_project_creation',         false);
        $this->_addHook('codendi_daily_start',                 'codendi_daily_start',               false);
        $this->_addHook('fill_project_history_sub_events',     'fillProjectHistorySubEvents',       false);
        $this->_addHook(Event::SOAP_DESCRIPTION,               'soap_description',                  false);
        $this->_addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook(Event::USER_MANAGER_GET_USER_INSTANCE);
        $this->_addHook('plugin_statistics_service_usage');
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_GET_PROJECT_TRACKERS);
        $this->addHook(Event::REST_OPTIONS_PROJECT_TRACKERS);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(Event::BACKEND_ALIAS_GET_ALIASES);
        $this->addHook(Event::GET_PROJECTID_FROM_URL);
        $this->addHook(Event::SITE_ADMIN_CONFIGURATION_TRACKER);
        $this->addHook(Event::EXPORT_XML_PROJECT);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::CAN_USER_ACCESS_UGROUP_INFO);
        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);
        $this->addHook('site_admin_option_hook');
    }

    public function getHooksAndCallbacks() {
        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE);
            $this->addHook(AGILEDASHBOARD_EXPORT_XML);

            // REST Milestones
            $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_MILESTONE);
            $this->addHook(AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN);
            $this->addHook(AGILEDASHBOARD_EVENT_REST_OPTIONS_BURNDOWN);
        }
        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }
        if (defined('FULLTEXTSEARCH_BASE_URL')) {
            $this->_addHook(FULLTEXTSEARCH_EVENT_FETCH_ALL_DOCUMENT_SEARCH_TYPES);
            $this->_addHook(FULLTEXTSEARCH_EVENT_FETCH_PROJECT_TRACKER_FIELDS);
            $this->_addHook(FULLTEXTSEARCH_EVENT_DOES_TRACKER_SERVICE_USE_UGROUP);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'trackerPluginInfo')) {
            include_once('trackerPluginInfo.class.php');
            $this->pluginInfo = new trackerPluginInfo($this);
        }
        return $this->pluginInfo;
    }


    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check(array $params) {
        $file_manager = new Tracker_Artifact_Attachment_TemporaryFileManager(
            $this->getUserManager(),
            new Tracker_Artifact_Attachment_TemporaryFileManagerDao(),
            new Tracker_FileInfoFactory(
                new Tracker_FileInfoDao(),
                Tracker_FormElementFactory::instance(),
                Tracker_ArtifactFactory::instance()
            ),
            new System_Command(),
            ForgeConfig::get('sys_file_deletion_delay')
        );

        $file_manager->purgeOldTemporaryFiles();
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params) {
        $params['entries'][$this->getServiceShortname()] = 'Opened artifacts';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params) {
        if ($params['character'] === $this->getServiceShortname()) {
            $params['sample'] = new Tracker_Sample();
        }
    }

    public function site_admin_option_hook($params) {
        $name = $GLOBALS['Language']->getText('plugin_tracker', 'descriptor_name');

        echo '<li><a href="'.$this->getPluginPath().'/config.php">'.$name.'</a></li>';
    }

    public function cssFile($params) {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(TRACKER_EVENT_INCLUDE_CSS_FILE, array('include_tracker_css_file' => &$include_tracker_css_file));
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if ($include_tracker_css_file ||
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/print.css" media="print" />';
            if (file_exists($this->getThemePath().'/css/ieStyle.css')) {
                echo '<!--[if lte IE 8]><link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/ieStyle.css" /><![endif]-->';
            }
        }
    }

    /**
     *This callback make SystemEvent manager knows about Tracker plugin System Events
     */
    public function getSystemEventClass($params) {
        switch($params['type']) {
            case SystemEvent_TRACKER_V3_MIGRATION::NAME:
                $params['class'] = 'SystemEvent_TRACKER_V3_MIGRATION';
                $params['dependencies'] = array(
                    $this->getMigrationManager(),
                );
                break;
            default:
                break;
        }
    }

    public function service_classnames($params) {
        include_once 'ServiceTracker.class.php';
        $params['classnames'][$this->getServiceShortname()] = 'ServiceTracker';
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                '/plugins/tracker/scripts/TrackerReports.js',
                '/plugins/tracker/scripts/TrackerEmailCopyPaste.js',
                '/plugins/tracker/scripts/TrackerReportsSaveAsModal.js',
                '/plugins/tracker/scripts/TrackerBinds.js',
                '/plugins/tracker/scripts/ReorderColumns.js',
                '/plugins/tracker/scripts/TrackerTextboxLists.js',
                '/plugins/tracker/scripts/TrackerAdminFields.js',
                '/plugins/tracker/scripts/TrackerArtifact.js',
                '/plugins/tracker/scripts/TrackerArtifactEmailActions.js',
                '/plugins/tracker/scripts/TrackerArtifactLink.js',
                '/plugins/tracker/scripts/TrackerCreate.js',
                '/plugins/tracker/scripts/TrackerFormElementFieldPermissions.js',
                '/plugins/tracker/scripts/TrackerDateReminderForms.js',
                '/plugins/tracker/scripts/TrackerTriggers.js',
                '/plugins/tracker/scripts/SubmissionKeeper.js',
                '/plugins/tracker/scripts/TrackerFieldDependencies.js',
                '/plugins/tracker/scripts/TrackerRichTextEditor.js',
                '/plugins/tracker/scripts/artifactChildren.js',
                '/plugins/tracker/scripts/load-artifactChildren.js',
                '/plugins/tracker/scripts/modal-in-place.js',
                '/plugins/tracker/scripts/TrackerArtifactEditionSwitcher.js',
                '/plugins/tracker/scripts/FixAggregatesHeaderHeight.js',
                '/plugins/tracker/scripts/TrackerSettings.js',
                '/plugins/tracker/scripts/TrackerCollapseFieldset.js',
                '/plugins/tracker/scripts/TrackerArtifactReferences.js',
                '/plugins/tracker/scripts/CopyArtifact.js',
            )
        );
    }

    public function javascript($params) {
        // TODO: Move this in ServiceTracker::displayHeader()
        include $GLOBALS['Language']->getContent('script_locale', null, 'tracker');
        echo PHP_EOL;
        echo "codendi.tracker = codendi.tracker || { };".PHP_EOL;
        echo "codendi.tracker.base_url = '". TRACKER_BASE_URL ."/';".PHP_EOL;
    }

    public function toggle($params) {
        if ($params['id'] === 'tracker_report_query_0') {
            Toggler::togglePreference($params['user'], $params['id']);
            $params['done'] = true;
        } else if (strpos($params['id'], 'tracker_report_query_') === 0) {
            $report_id = (int)substr($params['id'], strlen('tracker_report_query_'));
            $report_factory = Tracker_ReportFactory::instance();
            if (($report = $report_factory->getReportById($report_id, $params['user']->getid())) && $report->userCanUpdate($params['user'])) {
                $report->toggleQueryDisplay();
                $report_factory->save($report);
            }
            $params['done'] = true;
        }
    }

    public function agiledashboard_event_additional_panes_on_milestone($params) {
        $user      = $params['user'];
        $milestone = $params['milestone'];
        $pane_info = $this->getPaneInfo($milestone, $user);
        if (! $pane_info) {
            return;
        }

        if ($params['request']->get('pane') == Tracker_Artifact_Burndown_PaneInfo::IDENTIFIER) {
            $pane_info->setActive(true);
            $artifact = $milestone->getArtifact();
            $params['active_pane'] = new Tracker_Artifact_Burndown_Pane(
                    $pane_info,
                    $artifact,
                    $artifact->getABurndownField($user),
                    $user
            );
        }
        $params['panes'][] = $pane_info;
    }

    private function getPaneInfo($milestone, $user) {
        $artifact = $milestone->getArtifact();
        if (! $artifact->getABurndownField($user)) {
            return;
        }

        return new Tracker_Artifact_Burndown_PaneInfo($milestone);
    }

   /**
    * Project creation hook
    *
    * @param Array $params
    */
    function register_project_creation($params) {
        $tm = new TrackerManager();
        $tm->duplicate($params['template_id'], $params['group_id'], $params['ugroupsMapping']);

    }

    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_submit');
                break;
            case 'PLUGIN_TRACKER_FIELD_READ':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_read');
                break;
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_field_update');
                break;
            case Tracker::PERMISSION_SUBMITTER_ONLY:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_submitter_only_access');
                break;
            case Tracker::PERMISSION_SUBMITTER:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_submitter_access');
                break;
            case Tracker::PERMISSION_ASSIGNEE:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_assignee_access');
                break;
            case Tracker::PERMISSION_FULL:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_full_access');
                break;
            case Tracker::PERMISSION_ADMIN:
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_admin');
                break;
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                $params['name'] = $GLOBALS['Language']->getText('plugin_tracker_permissions','plugin_tracker_artifact_access');
                break;
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                $params['name'] = $GLOBALS['Language']->getText('workflow_admin','permissions_transition');
                break;
            default:
                break;
            }
        }
    }

    function permission_get_object_type($params) {
        $type = $this->getObjectTypeFromPermissions($params);
        if ($type != false) {
            $params['object_type'] = $type;
        }
    }

    function getObjectTypeFromPermissions($params) {
        switch($params['permission_type']) {
            case 'PLUGIN_TRACKER_FIELD_SUBMIT':
            case 'PLUGIN_TRACKER_FIELD_READ':
            case 'PLUGIN_TRACKER_FIELD_UPDATE':
                return 'field';
            case Tracker::PERMISSION_SUBMITTER_ONLY:
            case Tracker::PERMISSION_SUBMITTER:
            case Tracker::PERMISSION_ASSIGNEE:
            case Tracker::PERMISSION_FULL:
            case Tracker::PERMISSION_ADMIN:
                return 'tracker';
            case 'PLUGIN_TRACKER_ARTIFACT_ACCESS':
                return 'artifact';
            case 'PLUGIN_TRACKER_WORKFLOW_TRANSITION':
                return 'workflow transition';
        }
        return false;
    }

    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            $type = $this->getObjectTypeFromPermissions($params);
            if (in_array($params['permission_type'], array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY, 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS'))) {
                $object_id = $params['object_id'];
                if ($type == 'tracker') {
                    $ret = (string)$object_id;
                    if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                        $params['object_name'] = $tracker->getName();
                    }
                } else if ($type == 'field') {
                    $ret = (string)$object_id;
                    if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                        $ret = $field->getLabel() .' ('. $field->getTracker()->getName() .')';
                    }
                    $params['object_name'] =  $ret;
                } else if ($type == 'artifact') {
                    $ret = (string)$object_id;
                    if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                        $ret = 'art #'. $a->getId();
                        $semantics = $a->getTracker()
                                       ->getTrackerSemanticManager()
                                       ->getSemantics();
                        if (isset($semantics['title'])) {
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($semantics['title']->getFieldId())) {
                                $ret .= ' - '. $a->getValue($field)->getText();
                            }
                        }
                    }
                    $params['object_name'] =  $ret;
                }
            }
        }
    }

    function permission_get_object_fullname($params) {
        $this->permission_get_object_name($params);
    }

    function permissions_for_ugroup($params) {
        if (!$params['results']) {

            $group_id = $params['group_id'];
            $hp = Codendi_HTMLPurifier::instance();
            $atid = $params['object_id'];
            $objname = $params['objname'];

            if (in_array($params['permission_type'], array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY, 'PLUGIN_TRACKER_FIELD_SUBMIT', 'PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_ARTIFACT_ACCESS', 'PLUGIN_TRACKER_WORKFLOW_TRANSITION'))) {
                if (strpos($params['permission_type'], 'PLUGIN_TRACKER_ACCESS') === 0 || $params['permission_type'] === Tracker::PERMISSION_ADMIN) {
                    $params['results'] = $GLOBALS['Language']->getText('project_admin_editugroup','tracker')
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$atid.'&func=admin-perms-tracker">'
                    .$objname.'</a>';

                } else if (strpos($params['permission_type'], 'PLUGIN_TRACKER_FIELD') === 0) {
                    $field = Tracker_FormElementFactory::instance()->getFormElementById($atid);
                    $tracker_id = $field->getTrackerId();

                    $params['results'] = $GLOBALS['Language']->getText('project_admin_editugroup','tracker')
                    .' <a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=admin-perms-fields">'
                    .$objname.'</a>';

                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_ARTIFACT_ACCESS') {
                    $params['results'] = $hp->purify($objname, CODENDI_PURIFIER_BASIC);

                } else if ($params['permission_type'] == 'PLUGIN_TRACKER_WORKFLOW_TRANSITION') {
                    $transition = TransitionFactory::instance()->getTransition($atid);
                    $tracker_id = $transition->getWorkflow()->getTrackerId();
                    $edit_transition = $transition->getFieldValueFrom().'_'.$transition->getFieldValueTo();
                    $params['results'] = '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
                        array(
                            'tracker'         => $tracker_id,
                            'func'            => Workflow::FUNC_ADMIN_TRANSITIONS,
                            'edit_transition' => $edit_transition
                        )
                    ).'">'.$objname.'</a>';
                }
            }
        }
    }

    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            $allowed = array(
                Tracker::PERMISSION_ADMIN,
                Tracker::PERMISSION_FULL,
                Tracker::PERMISSION_SUBMITTER,
                Tracker::PERMISSION_SUBMITTER_ONLY,
                Tracker::PERMISSION_ASSIGNEE,
                'PLUGIN_TRACKER_FIELD_SUBMIT',
                'PLUGIN_TRACKER_FIELD_READ',
                'PLUGIN_TRACKER_FIELD_UPDATE',
                'PLUGIN_TRACKER_ARTIFACT_ACCESS',
                'PLUGIN_TRACKER_WORKFLOW_TRANSITION',
            );
            if (in_array($params['permission_type'], $allowed)) {
                $group_id  = $params['group_id'];
                $object_id = $params['object_id'];
                $type      = $this->getObjectTypeFromPermissions($params);
                if (!isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    switch ($type) {
                        case 'tracker':
                            if ($tracker = TrackerFactory::instance()->getTrackerById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $tracker->userIsAdmin();
                            }
                            break;
                        case 'field':
                            if ($field = Tracker_FormElementFactory::instance()->getFormElementById($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $field->getTracker()->userIsAdmin();
                            }
                            break;
                        case 'artifact':
                            if ($a  = Tracker_ArtifactFactory::instance()->getArtifactById($object_id)) {
                                //TODO: manage permissions related to field "permission on artifact"
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $a->getTracker()->userIsAdmin();
                            }
                        case 'workflow transition':
                            if ($transition = TransitionFactory::instance()->getTransition($object_id)) {
                                $this->_cached_permission_user_allowed_to_change[$type][$object_id] = $transition->getWorkflow()->getTracker()->userIsAdmin();
                            }
                            break;
                    }
                }
                if (isset($this->_cached_permission_user_allowed_to_change[$type][$object_id])) {
                    $params['allowed'] = $this->_cached_permission_user_allowed_to_change[$type][$object_id];
                }
            }
        }
    }

    public function get_available_reference_natures($params) {
        $natures = array(Tracker_Artifact::REFERENCE_NATURE => array('keyword' => 'artifact',
                                                                     'label'   => 'Artifact Tracker v5'));
        $params['natures'] = array_merge($params['natures'], $natures);
    }

    public function get_artifact_reference_group_id($params) {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
        if ($artifact) {
            $tracker = $artifact->getTracker();
            $params['group_id'] = $tracker->getGroupId();
        }
    }

    public function set_artifact_reference_group_id($params) {
        $reference = $params['reference'];
        if ($this->isDefaultReferenceUrl($reference)) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($params['artifact_id']);
            if ($artifact) {
                $tracker = $artifact->getTracker();
                $reference->setGroupId($tracker->getGroupId());
            }
        }
    }

    private function isDefaultReferenceUrl(Reference $reference) {
        return $reference->getLink() === TRACKER_BASE_URL. '/?&aid=$1&group_id=$group_id';
    }

    public function build_reference($params) {
        $row           = $params['row'];
        $params['ref'] = new Reference(
            $params['ref_id'],
            $row['keyword'],
            $row['description'],
            $row['link'],
            $row['scope'],
            $this->getServiceShortname(),
            Tracker_Artifact::REFERENCE_NATURE,
            $row['is_active'],
            $row['group_id']
        );
    }

    public function ajax_reference_tooltip($params) {
        if ($params['reference']->getServiceShortName() == $this->getServiceShortname()) {
            if ($params['reference']->getNature() == Tracker_Artifact::REFERENCE_NATURE) {
                $user = UserManager::instance()->getCurrentUser();
                $aid = $params['val'];
                if ($artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($aid)) {
                    if ($artifact && $artifact->getTracker()->isActive()) {
                        echo $artifact->fetchTooltip($user);
                    } else {
                        echo $GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist');
                    }
                }
            }
        }
    }

    public function url_verification_instance($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            include_once 'Tracker/Tracker_URLVerification.class.php';
            $params['url_verification'] = new Tracker_URLVerification();
        }
    }

    /**
     * Hook: event raised when widget are instanciated
     *
     * @param Array $params
     */
    public function widget_instance($params) {
        switch ($params['widget']) {
            case Tracker_Widget_MyArtifacts::ID:
                $params['instance'] = new Tracker_Widget_MyArtifacts();
                break;
            case Tracker_Widget_MyRenderer::ID:
                $params['instance'] = new Tracker_Widget_MyRenderer();
                break;
            case Tracker_Widget_ProjectRenderer::ID:
                $params['instance'] = new Tracker_Widget_ProjectRenderer();
                break;
        }
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e80d';
    }

    /**
     * Hook: event raised when user lists all available widget
     *
     * @param Array $params
     */
    public function widgets($params) {
        switch ($params['owner_type']) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $params['codendi_widgets'][] = Tracker_Widget_MyArtifacts::ID;
                $params['codendi_widgets'][] = Tracker_Widget_MyRenderer::ID;
                break;

            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $params['codendi_widgets'][] = Tracker_Widget_ProjectRenderer::ID;
                break;
        }
    }

    public function default_widgets_for_new_owner($params) {
        switch ($params['owner_type']) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $params['widgets'][] = array(
                    'name'   => Tracker_Widget_MyArtifacts::ID,
                    'column' => '2',
                    'rank'   => '5',
                );
                break;
        }
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->declareProjectPlanningResource($params['resources'], $params['project']);
    }

    function service_public_areas($params) {
        if ($params['project']->usesService($this->getServiceShortname())) {
            $tf = TrackerFactory::instance();

            // Get the artfact type list
            $trackers = $tf->getTrackersByGroupId($params['project']->getGroupId());

            if ($trackers) {
                $entries  = array();
                $purifier = Codendi_HTMLPurifier::instance();
                foreach($trackers as $t) {
                    if ($t->userCanView()) {
                        $name      = $purifier->purify($t->name, CODENDI_PURIFIER_CONVERT_HTML);
                        $entries[] = '<a href="'. TRACKER_BASE_URL .'/?tracker='. $t->id .'">'. $name .'</a>';
                    }
                }
                if ($entries) {
                    $area = '';
                    $area .= '<a href="'. TRACKER_BASE_URL .'/?group_id='. $params['project']->getGroupId() .'">';
                    $area .= $GLOBALS['HTML']->getImage('ic/clipboard-list.png');
                    $area .= ' '. $GLOBALS['Language']->getText('plugin_tracker', 'service_lbl_key');
                    $area .= '</a>';

                    $area .= '<ul><li>'. implode('</li><li>', $entries) .'</li></ul>';
                    $params['areas'][] = $area;
                }
            }
        }
    }

    /**
     * When a project is deleted, we delete all its trackers
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     *
     * @return void
     */
    function project_is_deleted($params) {
        $groupId = $params['group_id'];
        if ($groupId) {
            include_once 'Tracker/TrackerManager.class.php';
            $trackerManager = new TrackerManager();
            $trackerManager->deleteProjectTrackers($groupId);
        }
    }

   /**
     * Process the nightly job to send reminder on artifact correponding to given criteria
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function codendi_daily_start($params) {
        include_once 'Tracker/TrackerManager.class.php';
        $trackerManager = new TrackerManager();
        $logger = new BackendLogger();
        $logger->debug("[TDR] Tuleap daily start event: launch date reminder");
        return $trackerManager->sendDateReminder();
    }

    /**
     * Fill the list of subEvents related to tracker in the project history interface
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function fillProjectHistorySubEvents($params) {
        array_push($params['subEvents']['event_others'], 'tracker_date_reminder_add',
                                                         'tracker_date_reminder_edit',
                                                         'tracker_date_reminder_delete',
                                                         'tracker_date_reminder_sent'
        );
    }

    public function soap_description($params) {
        $params['end_points'][] = array(
            'title'       => 'Tracker',
            'wsdl'        => $this->getPluginPath().'/soap/?wsdl',
            'wsdl_viewer' => $this->getPluginPath().'/soap/view-wsdl',
            'changelog'   => $this->getPluginPath().'/soap/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__).'/../www/soap/VERSION'),
            'description' => 'Query and modify Trackers.',
        );
    }

    /**
     * @param array $params
     */
    public function agiledashboard_export_xml($params) {
        $can_bypass_threshold = true;
        $user_xml_exporter    = new UserXMLExporter(
            $this->getUserManager(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );

        $this->getTrackerXmlExport($user_xml_exporter, $can_bypass_threshold)
            ->exportToXml($params['project']->getID(), $params['into_xml']);
    }

    /**
     * @return TrackerXmlExport
     */
    private function getTrackerXmlExport(UserXMLExporter $user_xml_exporter, $can_bypass_threshold) {
        $rng_validator = new XML_RNGValidator();

        return new TrackerXmlExport(
            $this->getTrackerFactory(),
            $this->getTrackerFactory()->getTriggerRulesManager(),
            $rng_validator,
            new Tracker_Artifact_XMLExport(
                $rng_validator,
                $this->getArtifactFactory(),
                $can_bypass_threshold,
                $user_xml_exporter
            ),
            $user_xml_exporter
        );
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params) {
        TrackerXmlImport::build($params['user_finder'], $params['logger'])->import(
            $params['project'],
            $params['xml_content'],
            $params['extraction_path']
        );
    }

    public function user_manager_get_user_instance(array $params) {
        if ($params['row']['user_id'] == Tracker_Workflow_WorkflowUser::ID) {
            $params['user'] = new Tracker_Workflow_WorkflowUser($params['row']);
        }
    }
    public function plugin_statistics_service_usage($params) {

        $dao             = new Tracker_ArtifactDao();

        $start_date      = strtotime($params['start_date']);
        $end_date        = strtotime($params['end_date']);

        $number_of_open_artifacts_between_two_dates   = $dao->searchSubmittedArtifactBetweenTwoDates($start_date, $end_date);
        $number_of_closed_artifacts_between_two_dates = $dao->searchClosedArtifactBetweenTwoDates($start_date, $end_date);

        $params['csv_exporter']->buildDatas($number_of_open_artifacts_between_two_dates, "Trackers v5 - Opened Artifacts");
        $params['csv_exporter']->buildDatas($number_of_closed_artifacts_between_two_dates, "Trackers v5 - Closed Artifacts");
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) {
        $injector = new Tracker_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_GET_PROJECT_TRACKERS
     */
    public function rest_get_project_trackers($params) {
        $user              = UserManager::instance()->getCurrentUser();
        $planning_resource = $this->buildRightVersionOfProjectTrackersResource($params['version']);
        $project           = $params['project'];

        $this->checkProjectRESTAccess($project, $user);

        $params['result'] = $planning_resource->get(
            $user,
            $project,
            $params['limit'],
            $params['offset']
        );
    }

    /**
     * @see REST_OPTIONS_PROJECT_TRACKERS
     */
    public function rest_options_project_trackers($params) {
        $user             = UserManager::instance()->getCurrentUser();
        $project          = $params['project'];
        $tracker_resource = $this->buildRightVersionOfProjectTrackersResource($params['version']);

        $this->checkProjectRESTAccess($project, $user);

        $params['result'] = $tracker_resource->options(
            $user,
            $project,
            $params['limit'],
            $params['offset']
        );
    }

    private function checkProjectRESTAccess(Project $project, PFUser $user) {
        $project_authorization_class = '\\Tuleap\\REST\\ProjectAuthorization';
        $project_authorization       = new $project_authorization_class();

        $project_authorization->userCanAccessProject($user, $project, new Tracker_URLVerification());
    }

    private function buildRightVersionOfProjectTrackersResource($version) {
        $class_with_right_namespace = '\\Tuleap\\Tracker\\REST\\'.$version.'\\ProjectTrackersResource';
        return new $class_with_right_namespace;
    }

    public function agiledashboard_event_rest_get_milestone($params) {
        if ($this->buildRightVersionOfMilestonesBurndownResource($params['version'])->hasBurndown($params['user'], $params['milestone'])) {
            $params['milestone_representation']->enableBurndown();
        }
    }

    public function agiledashboard_event_rest_options_burndown($params) {
        $this->buildRightVersionOfMilestonesBurndownResource($params['version'])->options($params['user'], $params['milestone']);
    }

    public function agiledashboard_event_rest_get_burndown($params) {
        $params['burndown'] = $this->buildRightVersionOfMilestonesBurndownResource($params['version'])->get($params['user'], $params['milestone']);
    }

     private function buildRightVersionOfMilestonesBurndownResource($version) {
        $class_with_right_namespace = '\\Tuleap\\Tracker\\REST\\'.$version.'\\MilestonesBurndownResource';
        return new $class_with_right_namespace;
    }

    private function getTrackerSystemEventManager() {
        return new Tracker_SystemEventManager($this->getSystemEventManager());
    }

    private function getSystemEventManager() {
        return SystemEventManager::instance();
    }

    private function getMigrationManager() {
        return new Tracker_Migration_MigrationManager(
            $this->getTrackerSystemEventManager(),
            $this->getTrackerFactory(),
            $this->getArtifactFactory(),
            $this->getTrackerFormElementFactory(),
            $this->getUserManager(),
            $this->getProjectManager()
        );
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getTrackerFactory() {
        return TrackerFactory::instance();
    }

    private function getUserManager() {
        return UserManager::instance();
    }

    private function getTrackerFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }

    private function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * @see Event::BACKEND_ALIAS_GET_ALIASES
     */
    public function backend_alias_get_aliases($params) {
        $config = new TrackerPluginConfig(
            new TrackerPluginConfigDao()
        );

        $src_dir  = ForgeConfig::get('codendi_dir');
        $script   = $src_dir .'/plugins/tracker/bin/emailgateway-wrapper.sh';

        $command = "sudo -u codendiadm $script";

        if ($config->isTokenBasedEmailgatewayEnabled() || $config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_TOKEN_ARTIFACT_UPDATE, "\"|$command\"");
        }

        if ($config->isInsecureEmailgatewayEnabled()) {
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION, "\"|$command\"");
            $params['aliases'][] = new System_Alias(self::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE, "\"|$command\"");
        }

    }
    public function get_projectid_from_url($params) {
        $url = $params['url'];
        if (strpos($url,'/plugins/tracker/') === 0) {
            if (! $params['request']->get('tracker')) {
                return;
            }

            $tracker = TrackerFactory::instance()->getTrackerById($params['request']->get('tracker'));
            if ($tracker) {
                $params['project_id'] = $tracker->getGroupId();
            }
        }
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array $params) {
        $params['queues'][Tracker_SystemEvent_Tv3Tv5Queue::NAME] = new Tracker_SystemEvent_Tv3Tv5Queue();
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue($params) {
        if ($params['queue'] === Tracker_SystemEvent_Tv3Tv5Queue::NAME) {
            $params['types'][] = SystemEvent_TRACKER_V3_MIGRATION::NAME;
        }
    }

    /** @see Event::SERVICES_TRUNCATED_EMAILS */
    public function services_truncated_emails(array $params) {
        $project = $params['project'];
        if ($project->usesService($this->getServiceShortname())) {
            $params['services'][] = $GLOBALS['Language']->getText('plugin_tracker', 'service_lbl_key');
        }
    }

    public function fulltextsearch_event_fetch_all_document_search_types($params) {
        $params['all_document_search_types'][] = array(
            'key'     => 'tracker',
            'name'    => $GLOBALS['Language']->getText('plugin_tracker', 'tracker_artifacts'),
            'info'    => $GLOBALS['Language']->getText('plugin_tracker', 'tracker_fulltextsearch_info'),
            'can_use' => false,
            'special' => true,
        );
    }

    public function fulltextsearch_event_fetch_project_tracker_fields($params) {
        $user     = $params['user'];
        $trackers = $this->getTrackerFactory()->getTrackersByGroupIdUserCanView($params['project_id'], $user);
        $fields   = $this->getTrackerFormElementFactory()->getUsedSearchableTrackerFieldsUserCanView($user, $trackers);

        $params['fields'] = $fields;
    }

    public function site_admin_configuration_tracker($params) {
        $label = $GLOBALS['Language']->getText('plugin_tracker', 'admin_tracker_template');

        $params['additional_entries'][] = '<li><a href="/plugins/tracker/?group_id=100">'. $label .'</a></li>';
    }

    public function fulltextsearch_event_does_tracker_service_use_ugroup($params) {
        $dao        = new Tracker_PermissionsDao();
        $ugroup_id  = $params['ugroup_id'];
        $project_id = $params['project_id'];

        if ($dao->isThereAnExplicitPermission($ugroup_id, $project_id)) {
            $params['is_used'] = true;
            return;
        }

        if ($dao->doAllItemsHaveExplicitPermissions($project_id)) {
            $params['is_used'] = false;
            return;
        }

        $params['is_used'] = $dao->isThereADefaultPermissionThatUsesUgroup($ugroup_id);
    }

    public function export_xml_project($params) {
        if (! isset($params['options']['tracker_id'])) {
            return;
        }

        $can_bypass_threshold = $params['options']['force'] === true;
        $tracker_id           = $params['options']['tracker_id'];

        $project    = $params['project'];
        $user       = $params['user'];
        $tracker    = $this->getTrackerFactory()->getTrackerById($tracker_id);

        if (! $tracker) {
            throw new Exception ('Tracker ID does not exist');
        }

        if ($tracker->getGroupId() != $project->getID()) {
            throw new Exception ('Tracker ID does not belong to project ID');
        }

        $this->getTrackerXmlExport($params['user_xml_exporter'], $can_bypass_threshold)
            ->exportSingleTrackerToXml($params['into_xml'], $tracker_id, $user, $params['archive']);
    }

    public function get_reference($params) {
        if ($this->isArtifactReferenceInMultipleTrackerServicesContext($params['keyword'])) {
            $artifact_id       = $params['value'];
            $keyword           = $params['keyword'];
            $reference_manager = $params['reference_manager'];

            $tracker_reference_manager = $this->getTrackerReferenceManager($reference_manager);

            $reference = $tracker_reference_manager->getReference(
                $keyword,
                $artifact_id
            );

            if ($reference) {
                $params['reference'] = $reference;
            }
        }
    }

    private function isArtifactReferenceInMultipleTrackerServicesContext($keyword) {
        return (TrackerV3::instance()->available() && ($keyword === 'art' || $keyword === 'artifact'));
    }

    /**
     * @return Tracker_ReferenceManager
     */
    private function getTrackerReferenceManager(ReferenceManager $reference_manager) {
        return new Tracker_ReferenceManager(
            $reference_manager,
            $this->getArtifactFactory()
        );
    }

    public function can_user_access_ugroup_info($params) {
        $project = $params['project'];
        $user    = $params['user'];

        $trackers = $this->getTrackerFactory()->getTrackersByGroupIdUserCanView($project->getID(), $user);
        foreach ($trackers as $tracker) {
            if ($tracker->hasFieldBindedToUserGroupsViewableByUser($user)) {
                $params['can_access'] = true;
                break;
            }
        }
    }
}
