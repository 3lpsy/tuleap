<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminPaneContent;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportDao;
use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\ProjectDashboardXMLImporter;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksUpdater;
use Tuleap\Project\Label\LabelDao;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FrsPermissionDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

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
    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;
    /** @var EventManager */
    private $event_manager;

    public function __construct(
        Codendi_Request                     $request,
        PlanningFactory                     $planning_factory,
        AgileDashboard_KanbanManager        $kanban_manager,
        AgileDashboard_KanbanFactory        $kanban_factory,
        AgileDashboard_ConfigurationManager $config_manager,
        TrackerFactory                      $tracker_factory,
        AgileDashboard_PermissionsManager   $permissions_manager,
        ScrumForMonoMilestoneChecker        $scrum_mono_milestone_checker,
        EventManager                        $event_manager
    ) {
        parent::__construct('agiledashboard', $request);

        $this->group_id                     = (int) $this->request->get('group_id');
        $this->planning_factory             = $planning_factory;
        $this->kanban_manager               = $kanban_manager;
        $this->kanban_factory               = $kanban_factory;
        $this->config_manager               = $config_manager;
        $this->tracker_factory              = $tracker_factory;
        $this->permissions_manager          = $permissions_manager;
        $this->scrum_mono_milestone_checker = $scrum_mono_milestone_checker;
        $this->event_manager                = $event_manager;
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        return new BreadCrumb_AgileDashboard();
    }

    public function adminScrum() {
        return $this->renderToString(
            'admin-scrum',
            $this->getAdminScrumPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    public function adminKanban() {
        return $this->renderToString(
            'admin-kanban',
            $this->getAdminKanbanPresenter(
                $this->getCurrentUser(),
                $this->group_id
            )
        );
    }

    private function getAdminScrumPresenter(PFUser $user, $group_id)
    {
        $can_create_planning         = true;
        $tracker_uri                 = '';
        $root_planning_name          = '';
        $potential_planning_trackers = array();
        $root_planning               = $this->planning_factory->getRootPlanning($user, $group_id);
        $scrum_activated             = $this->config_manager->scrumIsActivatedForProject($group_id);

        if ($root_planning) {
            $can_create_planning         = count($this->planning_factory->getAvailablePlanningTrackers($user, $group_id)) > 0;
            $tracker_uri                 = $root_planning->getPlanningTracker()->getUri();
            $root_planning_name          = $root_planning->getName();
            $potential_planning_trackers = $this->planning_factory->getPotentialPlanningTrackers($user, $group_id);
        }

        return new AdminScrumPresenter(
            $this->getPlanningAdminPresenterList($user, $group_id, $root_planning_name),
            $group_id,
            $can_create_planning,
            $tracker_uri,
            $root_planning_name,
            $potential_planning_trackers,
            $scrum_activated,
            $this->config_manager->getScrumTitle($group_id),
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable($user, $group_id),
            $this->isScrumMonoMilestoneEnable($group_id),
            $this->doesConfigurationAllowsPlanningCreation($user, $group_id, $can_create_planning),
            $this->getAdditionalContent()
        );
    }

    private function doesConfigurationAllowsPlanningCreation(PFUser $user, $project_id, $can_create_planning)
    {
        if ($this->isScrumMonoMilestoneEnable($project_id) === false) {
            return $can_create_planning;
        }

        return $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation($user, $project_id);
    }

    private function isScrumMonoMilestoneEnable($project_id)
    {
        return $this->scrum_mono_milestone_checker->isMonoMilestoneEnabled($project_id);
    }

    private function getAdminKanbanPresenter(PFUser $user, $project_id)
    {
        $has_kanban = count($this->kanban_factory->getListOfKanbansForProject($user, $project_id)) > 0;

        return new AdminKanbanPresenter(
            $project_id,
            $this->config_manager->kanbanIsActivatedForProject($project_id),
            $this->config_manager->getKanbanTitle($project_id),
            $has_kanban
        );
    }

    public function getAdditionalContent()
    {
        $event = new GetAdditionalScrumAdminPaneContent();
        $this->event_manager->processEvent($event);

        return $event->getAdditionalContent();
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

    public function updateConfiguration()
    {
        $token = new CSRFSynchronizerToken('/plugins/agiledashboard/?action=admin');
        $token->check();

        if (! $this->request->getCurrentUser()->isAdmin($this->group_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        $response = new AgileDashboardConfigurationResponse(
            $this->request->getProject(),
            $this->request->exist('home-ease-onboarding')
        );

        $user_manager = UserManager::instance();
        if ($this->request->exist('activate-kanban')) {
            $updater = new AgileDashboardKanbanConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstKanbanCreator(
                    $this->request->getProject(),
                    $this->kanban_manager,
                    $this->tracker_factory,
                    TrackerXmlImport::build(new XMLImportHelper(UserManager::instance())),
                    $this->kanban_factory,
                    new TrackerReportUpdater(new TrackerReportDao()),
                    Tracker_ReportFactory::instance(),
                    TrackerXmlImport::build(new XMLImportHelper($user_manager))
                )
            );
        } else {
            $ugroup_user_dao   = new UGroupUserDao();
            $ugroup_manager    = new UGroupManager();
            $ugroup_duplicator = new UgroupDuplicator(
                new UGroupDao(),
                $ugroup_manager,
                new UGroupBinding($ugroup_user_dao, $ugroup_manager),
                $ugroup_user_dao,
                $this->event_manager
            );

            $send_notifications = false;
            $force_activation   = true;

            $frs_permissions_creator = new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            );

            $widget_factory = new WidgetFactory(
                $user_manager,
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                $this->event_manager
            );

            $widget_dao        = new DashboardWidgetDao($widget_factory);
            $project_dao       = new ProjectDashboardDao($widget_dao);
            $project_retriever = new ProjectDashboardRetriever($project_dao);
            $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
            $duplicator        = new ProjectDashboardDuplicator(
                $project_dao,
                $project_retriever,
                $widget_dao,
                $widget_retriever,
                $widget_factory
            );

            $project_creator = new ProjectCreator(
                ProjectManager::instance(),
                ReferenceManager::instance(),
                $user_manager,
                $ugroup_duplicator,
                $send_notifications,
                $frs_permissions_creator,
                $duplicator,
                new ServiceCreator(),
                new LabelDao(),
                $force_activation
            );

            $scrum_mono_milestone_dao = new ScrumForMonoMilestoneDao();
            $logger                   = new ProjectXMLImporterLogger();
            $updater                  = new AgileDashboardScrumConfigurationUpdater(
                $this->request,
                $this->config_manager,
                $response,
                new AgileDashboard_FirstScrumCreator(
                    $this->request->getProject(),
                    $this->planning_factory,
                    $this->tracker_factory,
                    new ProjectXMLImporter(
                        $this->event_manager,
                        ProjectManager::instance(),
                        $user_manager,
                        new XML_RNGValidator(),
                        $ugroup_manager,
                        new XMLImportHelper($user_manager),
                        ServiceManager::instance(),
                        $logger,
                        $ugroup_duplicator,
                        $frs_permissions_creator,
                        new UserRemover(
                            ProjectManager::instance(),
                            $this->event_manager,
                            new ArtifactTypeFactory(false),
                            new UserRemoverDao(),
                            $user_manager,
                            new ProjectHistoryDao(),
                            new UGroupManager()
                        ),
                        $project_creator,
                        new UploadedLinksUpdater(new UploadedLinksDao(), FRSLog::instance()),
                        new ProjectDashboardXMLImporter(
                            new ProjectDashboardSaver(
                                $project_dao
                            ),
                            $widget_factory,
                            $widget_dao,
                            $logger,
                            $this->event_manager
                        )
                    )
                ),
                new ScrumForMonoMilestoneEnabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneDisabler($scrum_mono_milestone_dao),
                new ScrumForMonoMilestoneChecker($scrum_mono_milestone_dao, $this->planning_factory)
            );
        }

        return $updater->updateConfiguration();
    }

    public function createKanban() {
        $kanban_name = $this->request->get('kanban-name');
        $tracker_id  = $this->request->get('tracker-kanban');
        $tracker     = $this->tracker_factory->getTrackerById($tracker_id);
        $user        = $this->request->getCurrentUser();

        if (! $user->isAdmin($this->group_id)) {
             $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );

            return;
        }

        if (! $tracker_id) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'no_tracker_selected')
            );

            $this->redirectToHome();
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

            $filter_tracker_report_id = $this->request->get('tracker_report_id');
            $dashboard_widget_id      = 0;
            return $this->renderToString(
                'kanban',
                new KanbanPresenter(
                    $kanban,
                    $user,
                    $user_is_kanban_admin,
                    $user->getShortLocale(),
                    $tracker->getGroupId(),
                    $dashboard_widget_id,
                    $filter_tracker_report_id
                )
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_not_found'));
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'error_perm_denied'));
        }
    }
}
