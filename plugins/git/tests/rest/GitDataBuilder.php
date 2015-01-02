<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once 'common/autoload.php';

class GitDataBuilder extends TestDataBuilder {

    const PROJECT_TEST_GIT_SHORTNAME = 'test-git';
    const PROJECT_TEST_GIT_ID        = 107;
    const REPOSITORY_GIT_ID          = 1;

    /** @var SystemEventManager */
    private $system_event_manager;

    public function __construct() {
        parent::__construct();

        $this->system_event_manager = SystemEventManager::instance();
    }

    public function setUp() {
        $this->installPlugin();
        $this->activatePlugin('git');
        $this->generateProject();
        $this->generateGitRepository();
    }

    private function installPlugin() {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__).'/../../db/install.sql');
    }

    public function generateProject() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create Git Project\n";

        $project = $this->createProject(
            self::PROJECT_TEST_GIT_SHORTNAME,
            'Git repo',
            false,
            array($user_test_rest_1),
            array($user_test_rest_1)
        );

        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);

        return $project;
    }

    public function generateGitRepository() {
        echo "Create Git repo\n";

        $repository_factory       = new GitRepositoryFactory(new GitDao(), $this->project_manager);
        $git_system_event_manager = new Git_SystemEventManager($this->system_event_manager, $repository_factory);

        $manager = $this->getGitRepositoryManager($repository_factory, $git_system_event_manager);
        $backend = $this->getGitBackendGitolite($git_system_event_manager);

        $repository = new GitRepository();
        $repository->setBackend($backend);
        $repository->setDescription("Git repository");
        $repository->setCreator($this->user_manager->getUserByUserName(self::TEST_USER_1_NAME));
        $repository->setProject($this->project_manager->getProjectByUnixName(self::PROJECT_TEST_GIT_SHORTNAME));
        $repository->setName('repo01');

        $manager->create($repository, $backend);

        return $this;
    }

    /**
     * @return Git_Backend_Gitolite
     */
    private function getGitBackendGitolite(Git_SystemEventManager $git_system_event_manager) {
        $logger  = new BackendLogger();

        return new Git_Backend_Gitolite(
            new Git_GitoliteDriver(
                $logger,
                $git_system_event_manager,
                new Git_GitRepositoryUrlManager(
                    new GitPlugin(-1)
                )
            ),
            $logger
        );
    }

    /**
     * @return GitRepositoryManager
     */
    private function getGitRepositoryManager(
        GitRepositoryFactory $repository_factory,
        Git_SystemEventManager $git_system_event_manager
    ) {
        return new GitRepositoryManager(
            $repository_factory,
            $git_system_event_manager,
            new GitDao(),
            '/tmp'
        );
    }

}