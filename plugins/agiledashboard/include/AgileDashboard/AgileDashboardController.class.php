<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'common/mvc2/PluginController.class.php';

class AgileDashboard_Controller extends MVC2_PluginController {

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_PermissionsManager */
    private $permissions_manager;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        AgileDashboard_KanbanManager $kanban_manager,
        AgileDashboard_KanbanFactory $kanban_factory,
        AgileDashboard_ConfigurationManager $config_manager,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id            = (int) $this->request->get('group_id');
        $this->planning_factory    = $planning_factory;
        $this->kanban_manager      = $kanban_manager;
        $this->kanban_factory      = $kanban_factory;
        $this->config_manager      = $config_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->permissions_manager = $permissions_manager;
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        return new BreadCrumb_AgileDashboard();
    }

    public function admin() {
        return $this->renderToString(
            'admin',
            $this->getAdminPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    private function getAdminPresenter(PFUser $user, $group_id) {
        $can_create_planning         = true;
        $tracker_uri                 = '';
        $root_planning_name          = '';
        $potential_planning_trackers = array();
        $root_planning               = $this->planning_factory->getRootPlanning($user, $group_id);
        $kanban_activated            = $this->config_manager->kanbanIsActivatedForProject($group_id);
        $scrum_activated             = $this->config_manager->scrumIsActivatedForProject($group_id);
        $all_activated               = $kanban_activated && $scrum_activated;

        if ($root_planning) {
            $can_create_planning         = count($this->planning_factory->getAvailablePlanningTrackers($user, $group_id)) > 0;
            $tracker_uri                 = $root_planning->getPlanningTracker()->getUri();
            $root_planning_name          = $root_planning->getName();
            $potential_planning_trackers = $this->planning_factory->getPotentialPlanningTrackers($user, $group_id);
        }

        return new AdminPresenter(
            $this->getPlanningAdminPresenterList($user, $group_id, $root_planning_name),
            $group_id,
            $can_create_planning,
            $tracker_uri,
            $root_planning_name,
            $potential_planning_trackers,
            $kanban_activated,
            $scrum_activated,
            $all_activated,
            $this->config_manager->getScrumTitle($group_id),
            $this->config_manager->getKanbanTitle($group_id)
        );
    }

    private function getPlanningAdminPresenterList(PFUser $user, $group_id, $root_planning_name) {
        $plannings                 = array();
        $planning_out_of_hierarchy = array();
        foreach ($this->planning_factory->getPlanningsOutOfRootPlanningHierarchy($user, $group_id) as $planning) {
            $planning_out_of_hierarchy[$planning->getId()] = true;
        }
        foreach ($this->planning_factory->getPlannings($user, $group_id) as $planning) {
            if (isset($planning_out_of_hierarchy[$planning->getId()])) {
                $plannings[] = new Planning_PlanningOutOfHierarchyAdminPresenter($planning, $root_planning_name);
            } else {
                $plannings[] = new Planning_PlanningAdminPresenter($planning);
            }
        }
        return $plannings;
    }

    public function updateConfiguration() {
        $this->checkIfRequestIsValid();

        if (! $this->request->getCurrentUser()->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        switch ($this->request->get('activate-ad-component')) {
            case 'activate-scrum':
                 $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_activated')
                );

                $scrum_is_activated  = 1;
                $kanban_is_activated = 0;

                break;
            case 'activate-kanban':
                 $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_activated')
                );

                $scrum_is_activated  = 0;
                $kanban_is_activated = 1;
                break;

            case 'activate-all':
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('plugin_agiledashboard', 'all_activated')
                );

                $scrum_is_activated  = 1;
                $kanban_is_activated = 1;
                break;

            default:
                $this->notifyErrorAndRedirectToAdmin();
                return;
        }

        $this->config_manager->updateConfiguration(
            $this->group_id,
            $scrum_is_activated,
            $kanban_is_activated,
            $this->getScrumTitle(),
            $this->getKanbanTitle()
        );

        $this->redirectToAdmin();
    }

    private function getScrumTitle() {
        $scrum_title     = trim($this->request->get('scrum-title-admin'));
        $old_scrum_title = $this->request->get('old-scrum-title-admin');

        if ($scrum_title !== $old_scrum_title) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_title_changed')
            );
        }

        if ($scrum_title == '') {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'scrum_title_empty')
            );

            $scrum_title = $old_scrum_title;
        }

        return $scrum_title;
    }

    private function getKanbanTitle() {
        $kanban_title     = trim($this->request->get('kanban-title-admin'));
        $old_kanban_title = $this->request->get('old-kanban-title-admin');

        if ($kanban_title !== $old_kanban_title) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_title_changed')
            );
        }

        if ($kanban_title == '') {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_title_empty')
            );

            $kanban_title = $old_kanban_title;
        }

        return $kanban_title;
    }

    private function notifyErrorAndRedirectToAdmin() {
         $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_agiledashboard', 'invalid_request')
        );

        $this->redirectToAdmin();
    }

    private function checkIfRequestIsValid() {
        if (! $this->request->exist('activate-ad-component') &&
            ! $this->request->exist('scrum-title-admin') &&
            ! $this->request->exist('kanban-title-admin')
        ) {
            $this->notifyErrorAndRedirectToAdmin();

            return false;
        }

        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $token->check('/', $this->request);

        return true;
    }

    public function createKanban() {
        $kanban_name = $this->request->get('kanban-name');
        $tracker_id  = $this->request->get('tracker-kanban');
        $tracker     = $this->tracker_factory->getTrackerById($tracker_id);
        $user        = $this->request->getCurrentUser();
        $hierarchy   = new Tracker_Hierarchy();

        if (! $user->isAdmin($this->group_id)) {
             $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        if ($this->kanban_manager->doesKanbanExistForTracker($tracker)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_tracker_used')
            );

            $this->redirectToHome();
            return;
        }

        try {
            $hierarchy->getLevel($tracker_id);
            $is_in_hierarchy = true;
        } catch (Tracker_Hierarchy_NotInHierarchyException $exeption) {
            $is_in_hierarchy = false;
        }

        if ((count($this->planning_factory->getPlanningsByBacklogTracker($tracker)) > 0 ||
            count($this->planning_factory->getPlanningByPlanningTracker($tracker)) > 0) &&
            ! $is_in_hierarchy
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'tracker_used_in_scrum')
            );

            $this->redirectToHome();
            return;
        }

        if ($this->kanban_manager->createKanban($kanban_name, $tracker_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_created', array($kanban_name))
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_creation_error', array($kanban_name))
            );
        }

        $this->redirectToHome();
    }

    private function redirectToHome() {
        $this->redirect(array(
            'group_id' => $this->group_id
        ));
    }

    private function redirectToAdmin() {
        $this->redirect(array(
           'group_id' => $this->group_id,
           'action'   => 'admin'
        ));
    }

    public function showKanban() {
        $kanban_id = $this->request->get('id');
        $user      = $this->request->getCurrentUser();

        try {
            $kanban  = $this->kanban_factory->getKanban($user, $kanban_id);
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate(
                $user,
                $tracker->getGroupId()
            );

            return $this->renderToString(
                'kanban',
                new KanbanPresenter(
                    $kanban,
                    $user_is_kanban_admin,
                    $user->getShortLocale(),
                    $tracker->getGroupId()
                )
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_not_found'));
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'error_perm_denied'));
        }
    }
}