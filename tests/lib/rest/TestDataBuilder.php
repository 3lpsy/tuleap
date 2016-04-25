<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class REST_TestDataBuilder extends TestDataBuilder {

    const TEST_USER_4_ID          = 105;
    const TEST_USER_4_NAME        = 'rest_api_tester_4';
    const TEST_USER_4_PASS        = 'welcome0';
    const TEST_USER_4_STATUS      = 'A';

    const EPICS_TRACKER_ID        = 1;
    const RELEASES_TRACKER_ID     = 2;
    const SPRINTS_TRACKER_ID      = 3;
    const TASKS_TRACKER_ID        = 4;
    const USER_STORIES_TRACKER_ID = 5;
    const DELETED_TRACKER_ID      = 6;
    const KANBAN_TRACKER_ID       = 7;

    const KANBAN_ID = 1;

    const RELEASE_ARTIFACT_ID     = 1;
    const SPRINT_ARTIFACT_ID      = 2;
    const EPIC_1_ARTIFACT_ID      = 3;
    const EPIC_2_ARTIFACT_ID      = 4;
    const EPIC_3_ARTIFACT_ID      = 5;
    const EPIC_4_ARTIFACT_ID      = 6;
    const STORY_1_ARTIFACT_ID     = 7;
    const STORY_2_ARTIFACT_ID     = 8;
    const STORY_3_ARTIFACT_ID     = 9;
    const STORY_4_ARTIFACT_ID     = 10;
    const STORY_5_ARTIFACT_ID     = 11;
    const STORY_6_ARTIFACT_ID     = 12;
    const EPIC_5_ARTIFACT_ID      = 13;
    const EPIC_6_ARTIFACT_ID      = 14;
    const EPIC_7_ARTIFACT_ID      = 15;

    const KANBAN_ITEM_1_ARTIFACT_ID = 16;

    const KANBAN_TO_BE_DONE_COLUMN_ID = 230;
    const KANBAN_ONGOING_COLUMN_ID    = 231;
    const KANBAN_REVIEW_COLUMN_ID     = 232;
    const KANBAN_DONE_VALUE_ID        = 233;

    const PHPWIKI_PAGE_ID          = 6097;
    const PHPWIKI_SPACE_PAGE_ID    = 6100;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_HierarchyChecker */
    private $hierarchy_checker;

    /** @var string */
    protected $template_path;

    public function __construct() {
        parent::__construct();

        $this->template_path = dirname(__FILE__).'/../../rest/_fixtures/';
    }

    public function activatePlugins() {
        $this->activatePlugin('tracker');
        $this->activatePlugin('agiledashboard');
        $this->activatePlugin('cardwall');
        PluginManager::instance()->invalidateCache();
        PluginManager::instance()->loadPlugins();

        $this->tracker_artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->tracker_formelement_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory             = TrackerFactory::instance();
        $this->hierarchy_checker           = new AgileDashboard_HierarchyChecker(
            PlanningFactory::build(),
            new AgileDashboard_KanbanFactory($this->tracker_factory, new AgileDashboard_KanbanDao()),
            $this->tracker_factory
        );

        return $this;
    }

    public function initPlugins() {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/rest/init_test_data.php') as $init_file) {
            require_once $init_file;
        }
    }

    public function generateUsers() {
        $user_1 = new PFUser();
        $user_1->setUserName(self::TEST_USER_1_NAME);
        $user_1->setRealName(self::TEST_USER_1_REALNAME);
        $user_1->setLdapId(self::TEST_USER_1_LDAPID);
        $user_1->setPassword(self::TEST_USER_1_PASS);
        $user_1->setStatus(self::TEST_USER_1_STATUS);
        $user_1->setEmail(self::TEST_USER_1_EMAIL);
        $user_1->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_1);
        $user_1->setLabFeatures(true);

        $user_2 = new PFUser();
        $user_2->setUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setStatus(self::TEST_USER_2_STATUS);
        $user_2->setEmail(self::TEST_USER_2_EMAIL);
        $user_2->setLanguage($GLOBALS['Language']);
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->createAccount($user_2);

        $user_3 = new PFUser();
        $user_3->setUserName(self::TEST_USER_3_NAME);
        $user_3->setPassword(self::TEST_USER_3_PASS);
        $user_3->setStatus(self::TEST_USER_3_STATUS);
        $user_3->setEmail(self::TEST_USER_3_EMAIL);
        $user_3->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_3);

        $user_4 = new PFUser();
        $user_4->setUserName(self::TEST_USER_4_NAME);
        $user_4->setPassword(self::TEST_USER_4_PASS);
        $user_4->setStatus(self::TEST_USER_4_STATUS);
        $user_4->setEmail(self::TEST_USER_1_EMAIL);
        $user_4->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_4);

        return $this;
    }

    public function delegatePermissionsToRetrieveMembership() {
        $user = $this->user_manager->getUserById(self::TEST_USER_3_ID);

        // Create group
        $user_group_dao     = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
        $user_group         = $user_group_factory->createForgeUGroup('grokmirror users', '');

        // Grant Retrieve Membership permissions
        $permission                     = new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation();
        $permissions_dao                = new User_ForgeUserGroupPermissionsDao();
        $user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
        $user_group_permissions_manager->addPermission($user_group, $permission);

        // Add user to group
        $user_group_users_dao     = new User_ForgeUserGroupUsersDao();
        $user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);
        $user_group_users_manager->addUserToForgeUserGroup($user, $user_group);

        return $this;
    }

    public function delegatePermissionsToManageUser() {
        $user = $this->user_manager->getUserById(self::TEST_USER_3_ID);

        // Create group
        $user_group_dao     = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
        $user_group         = $user_group_factory->createForgeUGroup('site remote admins', '');

        // Grant Retrieve Membership permissions
        $permission                     = new User_ForgeUserGroupPermission_UserManagement();
        $permissions_dao                = new User_ForgeUserGroupPermissionsDao();
        $user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
        $user_group_permissions_manager->addPermission($user_group, $permission);

        // Add user to group
        $user_group_users_dao     = new User_ForgeUserGroupUsersDao();
        $user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);
        $user_group_users_manager->addUserToForgeUserGroup($user, $user_group);

        return $this;
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_test_rest_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_test_rest_3 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);

        echo "Create projects\n";

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_rest_1, $user_test_rest_2, $user_test_rest_3),
            array($user_test_rest_1),
            array()
        );
        $this->addUserGroupsToProject($project_1);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_1_ID);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_2_ID);
        $this->addUserToUserGroup($user_test_rest_2, $project_1, self::STATIC_UGROUP_2_ID);

        $project_2 = $this->createProject(
            self::PROJECT_PRIVATE_SHORTNAME,
            'Private',
            false,
            array(),
            array(),
            array()
        );
        $this->importTemplateInProject(self::PROJECT_PRIVATE_MEMBER_ID, 'tuleap_agiledashboard_template.xml');
        $this->importTemplateInProject(self::PROJECT_PRIVATE_MEMBER_ID, 'tuleap_agiledashboard_kanban_template.xml');

        $project_3 = $this->createProject(
            self::PROJECT_PUBLIC_SHORTNAME,
            'Public',
            true,
            array(),
            array(),
            array()
        );

        $project_4 = $this->createProject(
            self::PROJECT_PUBLIC_MEMBER_SHORTNAME,
            'Public member',
            true,
            array($user_test_rest_1),
            array(),
            array()
        );

        $pbi = $this->createProject(
            self::PROJECT_PBI_SHORTNAME,
            'PBI',
            true,
            array($user_test_rest_1),
            array(),
            array()
        );
        $this->importTemplateInProject($pbi->getId(), 'tuleap_agiledashboard_template_pbi_6348.xml');

        $backlog = $this->createProject(
            self::PROJECT_BACKLOG_DND,
            'Backlog drag and drop',
            true,
            array($user_test_rest_1),
            array($user_test_rest_1),
            array()
        );
        $this->importTemplateInProject($backlog->getId(), 'tuleap_agiledashboard_template.xml');

        $this->unsetGlobalsForProjectCreation();

        return $this;
    }

    protected function importTemplateInProject($project_id, $template) {
        $xml_importer = new ProjectXMLImporter(
            EventManager::instance(),
            $this->project_manager,
            UserManager::instance(),
            new XML_RNGValidator(),
            new UGroupManager(),
            new XMLImportHelper(UserManager::instance()),
            new ProjectXMLImporterLogger()
        );
        $this->user_manager->forceLogin(self::ADMIN_USER_NAME);
        $xml_importer->import($project_id, $this->template_path.$template);
    }

    public function deleteTracker() {
        echo "Delete tracker\n";

        $this->tracker_factory->markAsDeleted(self::DELETED_TRACKER_ID);

        return $this;
    }

    public function generateMilestones() {
        echo "Create milestones\n";

        $this->tracker_formelement_factory->clearInstance();
        $this->tracker_formelement_factory->instance();

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createRelease($user, 'Release 1.0', '126');
        $this->createSprint(
            $user,
            'Sprint A',
            '150',
            '2014-1-9',
            '10',
            '29'
        );

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::SPRINT_ARTIFACT_ID, $user);

        return $this;
    }

    private function clearFormElementCache() {
        $this->tracker_formelement_factory->clearInstance();
        $this->tracker_formelement_factory->instance();
    }

    public function generateContentItems() {
        echo "Create content items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createEpic($user, 'First epic', '101');
        $this->createEpic($user, 'Second epic', '102');
        $this->createEpic($user, 'Third epic', '103');
        $this->createEpic($user, 'Fourth epic', '101');

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::EPIC_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_4_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateBacklogItems() {
        echo "Create backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createUserStory($user, 'Believe', '206');
        $this->createUserStory($user, 'Break Free', '205');
        $this->createUserStory($user, 'Hughhhhhhh', '205');
        $this->createUserStory($user, 'Kill you', '205');
        $this->createUserStory($user, 'Back', '205');
        $this->createUserStory($user, 'Forward', '205');

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_4_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_5_ARTIFACT_ID, $user);

        $sprint = $this->tracker_artifact_factory->getArtifactById(self::SPRINT_ARTIFACT_ID);
        $sprint->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $sprint->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateTopBacklogItems() {
        echo "Create top backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createEpic($user, 'Epic pic', '101');
        $this->createEpic($user, "Epic c'est tout", '101');
        $this->createEpic($user, 'Epic epoc', '101');

        return $this;
    }

    public function generateKanban() {
        echo "Create 'My first kanban'\n";
        $kanban_manager = new AgileDashboard_KanbanManager(new AgileDashboard_KanbanDao(), $this->tracker_factory, $this->hierarchy_checker);
        $kanban_manager->createKanban('My first kanban', self::KANBAN_TRACKER_ID);

        echo "Populate kanban\n";
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Do something',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => 100,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Do something v2',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => 100,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Doing something',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => self::KANBAN_ONGOING_COLUMN_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Doing something v2',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => self::KANBAN_ONGOING_COLUMN_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Something archived',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => self::KANBAN_DONE_VALUE_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Something archived v2',
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'status')->getId() => self::KANBAN_DONE_VALUE_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        return $this;
    }

    private function createRelease(PFUser $user, $field_name_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::RELEASES_TRACKER_ID, 'name')->getId() => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName(self::RELEASES_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::RELEASES_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );

    }

    private function createEpic(PFUser $user, $field_summary_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => $field_summary_value,
            $this->tracker_formelement_factory->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::EPICS_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );
    }

    private function createSprint(
        PFUser $user,
        $field_name_value,
        $field_status_value,
        $field_start_date_value,
        $field_duration_value,
        $field_capacity_value
    ) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'name')->getId()       => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'status')->getId()     => $field_status_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'start_date')->getId() => $field_start_date_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'duration')->getId()   => $field_duration_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'capacity')->getId()   => $field_capacity_value,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::SPRINTS_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );

    }

    private function createUserStory(PFUser $user, $field_i_want_to_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => $field_i_want_to_value,
            $this->tracker_formelement_factory->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::USER_STORIES_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );
    }

}
