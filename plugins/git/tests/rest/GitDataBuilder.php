<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All rights reserved
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
require_once __DIR__.'/DatabaseInitialization.php';

use Tuleap\Git\REST\DatabaseInitialization;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;

class GitDataBuilder extends REST_TestDataBuilder {

    const PROJECT_TEST_GIT_SHORTNAME = 'test-git';
    const PROJECT_TEST_GIT_ID        = 107;
    const REPOSITORY_GIT_ID          = 1;

    /** @var SystemEventManager */
    private $system_event_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Git_SystemEventManager
     */
    private $git_system_event_manager;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    /**
     * @var DatabaseInitialization
     */
    private $database_init;

    public function __construct() {
        parent::__construct();

        $this->system_event_manager = SystemEventManager::instance();
        $this->database_init        = new DatabaseInitialization();
    }

    public function setUp() {
        PluginManager::instance()->installAndActivate('git');

        $this->repository_factory = new GitRepositoryFactory(new GitDao(), $this->project_manager);

        $this->git_system_event_manager = new Git_SystemEventManager(
            $this->system_event_manager,
            $this->repository_factory
        );

        $server_dao = new Git_RemoteServer_Dao();
        $git_dao = new GitDao();

        $this->server_factory = new Git_RemoteServer_GerritServerFactory(
            $server_dao,
            $git_dao,
            $this->git_system_event_manager,
            $this->project_manager
        );

        $project = $this->generateProject();
        $this->activateGitService($project);

        $repository = $this->generateGitRepository();
        $this->changeRepositoryUpdate($repository);

        $this->addGerritServers();
    }

    private function addGerritServers() {
        echo "Creating Gerrit servers\n";

        $server_01 = new Git_RemoteServer_GerritServer(
            '1',
            'localhost',
            29418,
            8080,
            'gerrit-adm',
            '',
            '',
            true,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS,
            '',
            'Digest'
        );

        $server_02 = new Git_RemoteServer_GerritServer(
            '2',
            'otherhost',
            29418,
            8080,
            'gerrit-adm',
            '',
            '',
            false,
            Git_RemoteServer_GerritServer::DEFAULT_GERRIT_VERSION,
            '',
            'Digest'
        );

        $this->server_factory->save($server_01);
        $this->server_factory->save($server_02);
    }

    private function changeRepositoryUpdate(GitRepository $repository) {
        echo "Update Git Repository Permissions\n";

        $backend = $this->getGitBackendGitolite($this->git_system_event_manager);

        $permissions = array(
            'PLUGIN_GIT_READ'  => array('3'),
            'PLUGIN_GIT_WRITE' => array('4'),
            'PLUGIN_GIT_WPLUS' => array('4')
        );

        return $backend->savePermissions($repository, $permissions);
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create Git Project\n";

        $project = $this->createProject(
            self::PROJECT_TEST_GIT_SHORTNAME,
            'Git repo',
            false,
            array($user_test_rest_1),
            array($user_test_rest_1),
            array()
        );

        $this->unsetGlobalsForProjectCreation();

        return $project;
    }

    private function activateGitService(Project $project) {
        return $this->database_init->activateGitService($project);
    }

    private function generateGitRepository() {
        echo "Create Git repo\n";

        $manager = $this->getGitRepositoryManager($this->repository_factory, $this->git_system_event_manager);
        $backend = $this->getGitBackendGitolite($this->git_system_event_manager);

        $repository = new GitRepository();
        $repository->setBackend($backend);
        $repository->setDescription("Git repository");
        $repository->setCreator($this->user_manager->getUserByUserName(self::TEST_USER_1_NAME));
        $repository->setProject($this->project_manager->getProjectByUnixName(self::PROJECT_TEST_GIT_SHORTNAME));
        $repository->setName('repo01');

        $manager->create($repository, $backend, array());

        return $repository;
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
                ),
                new GitDao(),
                new Git_Mirror_MirrorDao(),
                new GitPlugin(-1)
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
        $mirror_dao         = new Git_Mirror_MirrorDao();
        $rc_reader          = new Git_Gitolite_GitoliteRCReader();
        $default_mirror_dao = new DefaultProjectMirrorDao();

        $mirror_data_mapper = new Git_Mirror_MirrorDataMapper(
            $mirror_dao,
            $this->user_manager,
            $repository_factory,
            $this->project_manager,
            $git_system_event_manager,
            $rc_reader,
            $default_mirror_dao
        );

        $ugroup_manager   = new UGroupManager();
        $normalizer       = new PermissionsNormalizer();
        $fine_grained_dao = new FineGrainedDao();
        $default_factory  = new DefaultFineGrainedPermissionFactory($fine_grained_dao, $ugroup_manager, $normalizer);
        $saver            = new FineGrainedPermissionSaver($fine_grained_dao);
        $factory          = new FineGrainedPermissionFactory(
            $fine_grained_dao,
            $ugroup_manager,
            $normalizer,
            PermissionsManager::instance()
        );

        $replicator = new FineGrainedPermissionReplicator($fine_grained_dao, $default_factory, $saver, $factory);

        return new GitRepositoryManager(
            $repository_factory,
            $git_system_event_manager,
            new GitDao(),
            '/tmp',
            new GitRepositoryMirrorUpdater($mirror_data_mapper, new ProjectHistoryDao()),
            $mirror_data_mapper,
            $replicator
        );
    }

}
