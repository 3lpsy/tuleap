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

/**
 * Controller for site admin views
 */
class FullTextSearch_Controller_Admin extends FullTextSearch_Controller_Search {

    /* FullTextSearch_DocmanSystemEventManager */
    private $docman_system_event_manager;

    /* FullTextSearch_WikiSystemEventManager */
    private $wiki_system_event_manager;

    /* FullTextSearch_TrackerSystemEventManager */
    private $tracker_system_event_manager;

    public function __construct(
        Codendi_Request $request,
        FullTextSearch_ISearchDocumentsForAdmin $client,
        FullTextSearch_DocmanSystemEventManager $docman_system_event_manager,
        FullTextSearch_WikiSystemEventManager $wiki_system_event_manager,
        FullTextSearch_TrackerSystemEventManager $tracker_system_event_manager
    ) {
        parent::__construct($request, $client);

        $this->docman_system_event_manager  = $docman_system_event_manager;
        $this->wiki_system_event_manager    = $wiki_system_event_manager;
        $this->tracker_system_event_manager = $tracker_system_event_manager;
    }

    public function getIndexStatus() {
        return $this->client->getStatus();
    }

    public function index() {
        $project_manager    = ProjectManager::instance();
        $project_presenters = $this->getProjectPresenters($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE));

        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('plugin_fulltextsearch', 'admin_title')));
        $this->renderer->renderToPage('admin', new FullTextSearch_Presenter_AdminPresenter($project_presenters));
        $GLOBALS['HTML']->footer(array());
    }

    public function reindexDocman($group_id) {
        $this->docman_system_event_manager->queueDocmanProjectReindexation($group_id);
    }

    public function reindexWiki($group_id) {
        $this->wiki_system_event_manager->queueWikiProjectReindexation($group_id);
    }

    public function reindexTrackers($group_id) {
        $this->tracker_system_event_manager->queueTrackersProjectReindexation($group_id);
    }

    private function getProjectPresenters($projects) {
        $presenters = array();
        foreach ($projects as $project) {
            $presenters[] = new FullTextSearch_Presenter_ProjectPresenter($project);
        }

        return $presenters;
    }
}