<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/plugin/Plugin.class.php';
require_once 'autoload.php';

class fulltextsearchPlugin extends Plugin {

    const SEARCH_TYPE         = 'fulltext';
    const SEARCH_DOCMAN_TYPE  = 'docman';
    const SEARCH_TRACKER_TYPE = 'tracker';

    private $actions;
    private $trackerActions;

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->allowed_for_project = array();
    }

    public function getHooksAndCallbacks() {
        // docman
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_new_document', false);
        $this->_addHook('plugin_docman_event_del', 'plugin_docman_event_del', false);
        $this->_addHook('plugin_docman_event_update', 'plugin_docman_event_update', false);
        $this->_addHook('plugin_docman_event_perms_change', 'plugin_docman_event_perms_change', false);
        $this->_addHook('plugin_docman_event_new_version', 'plugin_docman_event_new_version', false);

        // tracker
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook(TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA);
            $this->_addHook(TRACKER_EVENT_REPORT_PROCESS_ADDITIONAL_QUERY);
            $this->_addHook('tracker_followup_event_add', 'tracker_followup_event_add', false);
            $this->_addHook('tracker_followup_event_update', 'tracker_followup_event_update', false);
            $this->_addHook('tracker_report_followup_warning', 'tracker_report_followup_warning', false);
        }

        // site admin
        $this->_addHook('site_admin_option_hook',   'site_admin_option_hook', false);

        // assets
        $this->_addHook('cssfile', 'cssfile', false);
        $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);

        // system events
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES, 'system_event_get_types', false);

        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::PLUGINS_POWERED_SEARCH);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
        $this->addHook(Event::SEARCH_TYPE, 'search_type');

        return parent::getHooksAndCallbacks();
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    public function layout_search_entry($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            $params['search_entries'][] = array(
                'value'    => self::SEARCH_TYPE,
                'label'    => 'Fulltext',
                'selected' => $params['type_of_search'] == self::SEARCH_TYPE,
            );
        }
    }

    public function plugins_powered_search($params) {
        if ($this->getCurrentUser()->useLabFeatures() && $params['type_of_search'] === self::SEARCH_TYPE) {
            $params['plugins_powered_search'] = true;
        }
    }

    public function search_types_presenters($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            $params['site_presenters'][] = new Search_SearchTypePresenter(
                self::SEARCH_TYPE,
                $GLOBALS['Language']->getText('plugin_fulltextsearch', 'accordion_type'),
                null
            );
        }
    }

    public function search_type($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            if ($params['query']->getTypeOfSearch() === self::SEARCH_TYPE) {
                try {
                    $search_controller = $this->getSearchController();
                } catch (ElasticSearch_ClientNotFoundException $exception) {
                    $search_error_controller = $this->getSearchErrorController();
                    $search_error_controller->clientNotFound($params);
                    return;
                }

                $search_controller->search();
                $search_controller->siteSearch($params);
            }
        }
    }

    public function system_event_get_types($params) {
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_INDEX';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_DELETE';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE';
        $params['types'][] = 'FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD';
        $params['types'][] = 'FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE';
    }

    /**
     * This callback make SystemEvent manager knows about fulltext plugin System Events
     */
    public function get_system_event_class($params) {
        try {
            if (strpos($params['type'], 'FULLTEXTSEARCH_DOCMAN') !== false) {
                $params['class']        = 'SystemEvent_'. $params['type'];
                $params['dependencies'] = array($this->getActions(), new Docman_ItemFactory(), new Docman_VersionFactory());
            }
            if (strpos($params['type'], 'FULLTEXTSEARCH_TRACKER') !== false) {
                $params['class']        = 'SystemEvent_'. $params['type'];
                $params['dependencies'] = array($this->getTrackerActions());
            }
        } catch (ElasticSearch_ClientNotFoundException $exception) {
            // do nothing
        }
    }

    /**
     * Return true if given project has the right to use this plugin.
     *
     * @param string $group_id
     *
     * @return bool
     */
    function isAllowed($group_id) {
        if(!isset($this->allowedForProject[$group_id])) {
            $this->allowed_for_project[$group_id] = PluginManager::instance()->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowed_for_project[$group_id];
    }

    private function getActions() {
        if (!isset($this->actions) && ($search_client = $this->getIndexClient(self::SEARCH_DOCMAN_TYPE))) {
            $this->actions = new FullTextSearchDocmanActions(
                $search_client,
                new ElasticSearch_1_2_RequestDataFactory(
                    $this->getBareDocmanMetadataFactory(),
                    new Docman_PermissionsItemManager()
                ),
                new BackendLogger()
            );
        }
        return $this->actions;
    }

    private function getBareDocmanMetadataFactory() {
        $empty_group_id = 0;

        return new Docman_MetadataFactory($empty_group_id);
    }

    /**
     * Get tracker actions
     *
     * @return FullTextSearchTrackerActions
     */
    private function getTrackerActions() {
        $type = self::SEARCH_TRACKER_TYPE;
        if (!isset($this->trackerActions) && ($search_client = $this->getIndexClient($type))) {
            $this->trackerActions = new FullTextSearchTrackerActions($search_client);
        }
        return $this->trackerActions;
    }

    /**
     * Event triggered when a document is updated
     *
     * @param array $params
     */
    public function plugin_docman_event_update($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA', SystemEvent::PRIORITY_MEDIUM, $params['item']);
    }

    /**
     * Event triggered when a document is created
     *
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_INDEX', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
    }

    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_DELETE', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document change
     */
    public function plugin_docman_event_perms_change($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document version update
     */
    public function plugin_docman_event_new_version($params) {
        // will be done in plugin_docman_after_new_document since we
        // receive both event for a new document
        if ($params['version']->getNumber() > 1) {
            $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
        }
    }

    /**
     * Display search form in tracker followup
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_event_report_display_additional_criteria($params) {
        if (! $params['tracker']) {
            return;
        }

        if ($this->tracker_followup_check_preconditions($params['tracker']->getGroupId())) {
            $request = HTTPRequest::instance();
            $hp      = Codendi_HTMLPurifier::instance();
            $filter  = $hp->purify($request->getValidated('search_followups', 'string', ''));

            $additional_criteria  = '';
            $additional_criteria .='<span class ="lab_features">';
            $additional_criteria .= '<label title="'.$GLOBALS['Language']->getText('plugin_fulltextsearch', 'search_followup_comments').'" for="tracker_report_crit_followup_search">';
            $additional_criteria .= $GLOBALS['Language']->getText('plugin_fulltextsearch', 'followups_search');
            $additional_criteria .= '</label>';
            $additional_criteria .= '<input id="tracker_report_crit_followup_search" type="text" name="search_followups" value="'.$filter.'" />';
            $additional_criteria .= '</span>';
            $params['array_of_html_criteria'][] = $additional_criteria;
        }
    }

    /**
     * Process warning display for tracker followup search
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_report_followup_warning($params) {
        if ($this->tracker_followup_check_preconditions($params['group_id'])) {
            if ($params['request']->get('search_followups')) {
                $params['html'] .= '<div id="tracker_report_selection" class="tracker_report_haschanged_and_isobsolete" style="z-index: 2;position: relative;">';
                $params['html'] .= $GLOBALS['HTML']->getimage('ic/warning.png', array('style' => 'vertical-align:top;'));
                $params['html'] .= $GLOBALS['Language']->getText('plugin_fulltextsearch', 'followup_full_text_warning_search');
                $params['html'] .= '</div>';
            }
        }
    }

    /**
     * This method check preconditions to use fulltext:
     * Elastic Search is available, the plugin is enabled for this project and the user is enabling mode Lab.
     *
     * @param Integer $group_id Project Id
     *
     * @return Boolean
     */
    private function tracker_followup_check_preconditions($group_id) {
        try {
            $index_status = $this->getAdminController()->getIndexStatus();
        } catch (ElasticSearchTransportHTTPException $e) {
            return false;
        } catch (ElasticSearch_ClientNotFoundException $exception) {
            return false;
        }

        return $this->isAllowed($group_id) && $this->getCurrentUser()->useLabFeatures();
    }

    /**
     * Process search in tracker followup
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_event_report_process_additional_query($params) {
        $group_id = $params['tracker']->getGroupId();
        if (! $this->tracker_followup_check_preconditions($group_id)) {
            return;
        }

        if (! $params['request']) {
            return;
        }

        $filter = $params['request']->get('search_followups');
        if (empty($filter)) {
            return;
        }

        try {
            $controller         = $this->getSearchController('tracker');
            $params['result'][] = $controller->search($params['request']);
            $params['search_performed'] = true;
        } catch (ElasticSearch_ClientNotFoundException $exception) {
            // do nothing
        }
    }

    /**
     * Index added followup comment
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_followup_event_add($params) {
        $this->createTrackerSystemEvent('FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD', SystemEvent::PRIORITY_MEDIUM, $params);
    }

    /**
     * Index updated followup comment
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_followup_event_update($params) {
        $this->createTrackerSystemEvent('FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE', SystemEvent::PRIORITY_MEDIUM, $params);
    }

    /**
     * Create system event for docman indexing operations
     *
     * @param String      $type              Type of the event
     * @param String      $priority          Priority of the system event
     * @param Docman_Item $item              Docman item
     * @param Mixed       $additional_params Extra params
     *
     * @return Void
     */
    private function createDocmanSystemEvent($type, $priority, Docman_Item $item, $additional_params = '') {
        if ($this->isAllowed($item->getGroupId())) {

            $params = $item->getGroupId() . SystemEvent::PARAMETER_SEPARATOR . $item->getId();
            if ($additional_params) {
                $params .= SystemEvent::PARAMETER_SEPARATOR . $additional_params;
            }
            SystemEventManager::instance()->createEvent($type, $params, $priority);
        }
    }

    /**
     * Create system event for docman indexing operations
     *
     * @param String $type     Type of the event
     * @param String $priority Priority of the system event
     * @param Array  $params   Event params
     *
     * @return Void
     */
    private function createTrackerSystemEvent($type, $priority, $params) {
        if ($this->isAllowed($params['group_id'])) {
            $params = $params['group_id'].SystemEvent::PARAMETER_SEPARATOR.$params['artifact_id'].SystemEvent::PARAMETER_SEPARATOR.$params['changeset_id'].SystemEvent::PARAMETER_SEPARATOR.$params['text'];
            SystemEventManager::instance()->createEvent($type, $params, $priority);
        }
    }

    /**
     * Event to display something in siteadmin interface
     *
     * @param array $params
     */
    public function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Full Text Search</a></li>';
    }

    /**
     * Event to load css stylesheet
     *
     * @param array $params
     */
    public function cssfile($params) {
        if ($this->canIncludeAssets()) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                $this->getPluginPath().'/scripts/full-text-search.js',
            )
        );
    }

    private function canIncludeAssets() {
        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/search/') === 0;
    }

    /**
     * Obtain index client
     *
     * @param String $type Type of the index
     *
     * @return ElasticSearch_IndexClientFacade
     */
    private function getIndexClient($type) {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildIndexClient($client_path, $server_host, $server_port, $server_user, $server_password, $type);
    }

    /**
     * Obtain search client
     *
     * @param String $type Type of the index
     *
     * @return ElasticSearch_SearchClientFacade
     */
    private function getSearchClient($type) {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildSearchClient($client_path, $server_host, $server_port, $server_user, $server_password, $this->getProjectManager(), $type);
    }

    private function getSearchAdminClient() {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildSearchAdminClient($client_path, $server_host, $server_port, $server_user, $server_password, $this->getProjectManager());
    }

    private function getClientFactory() {
        return new ElasticSearch_ClientFactory();
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'FulltextsearchPluginInfo')) {
            $this->pluginInfo = new FulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @throws ElasticSearch_ClientNotFoundException
     */
    private function getSearchController($type = self::SEARCH_DOCMAN_TYPE) {
        return new FullTextSearch_Controller_Search($this->getRequest(), $this->getSearchClient($type));
    }

    private function getSearchErrorController() {
        return new FullTextSearch_Controller_SearchError($this->getRequest());
    }

    /**
     * @throws ElasticSearch_ClientNotFoundException
     */
    private function getAdminController() {
        return new FullTextSearch_Controller_Admin($this->getRequest(), $this->getSearchAdminClient());
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getRequest() {
        return HTTPRequest::instance();
    }

    public function process() {
        $request = $this->getRequest();
        // Grant access only to site admin
        if (!$request->getCurrentUser()->isSuperUser()) {
            header('Location: ' . get_server_url());
        }

        try {
            $controller = $this->getAdminController();
            if ($request->get('words')) {
                $controller->search();
            } else {
                $controller->index();
            }
        } catch (ElasticSearch_ClientNotFoundException $exception) {
            $this->clientIsNotFound($exception);
        }
    }

    private function clientIsNotFound(ElasticSearch_ClientNotFoundException $exception) {
        $GLOBALS['Response']->addFeedback('error', $exception->getMessage());
        $GLOBALS['HTML']->redirect('/');
        die();
    }
}

?>
