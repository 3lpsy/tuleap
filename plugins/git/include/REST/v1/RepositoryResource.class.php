<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

use GitRepositoryFactory;
use Tuleap\Git\REST\v1\GitRepositoryRepresentation;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tuleap\REST\v1\GitRepositoryRepresentationBase;
use Tuleap\REST\AuthenticatedResource;
use GitRepoNotReadableException;
use GitRepoNotFoundException;
use Exception;
use UserManager;
use GitPermissionsManager;
use Git_PermissionsDao;
use Git_SystemEventManager;
use SystemEventManager;
use EventManager;
use PFUser;
use GitRepository;
use GitDao;
use ProjectManager;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_Dao;
use Git_RemoteServer_NotFoundException;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use GitBackendLogger;
use ProjectHistoryDao;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException;
use Tuleap\Git\Exceptions\RepositoryAlreadyInQueueForMigrationException;
use Tuleap\Git\RemoteServer\Gerrit\MigrationHandler;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionSaver;
use UGroupManager;
use Git_Exec;
use Tuleap\Git\CIToken\Manager as CITokenManager;
use Tuleap\Git\CIToken\Dao as CITokenDao;

include_once('www/project/admin/permissions.php');

class RepositoryResource extends AuthenticatedResource {

    const MAX_LIMIT = 50;

    const MIGRATE_PERMISSION_DEFAULT = 'default';
    const MIGRATE_NO_PERMISSION      = 'none';

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var RepositoryRepresentationBuilder */
    private $representation_builder;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /** @var GerritMigrationHandler */
    private $migration_handler;

    /**
     * @var CITokenManager
     */
    private $ci_token_manager;

    public function __construct() {
        $git_dao         = new GitDao();
        $project_manager = ProjectManager::instance();

        $this->repository_factory = new GitRepositoryFactory(
            $git_dao,
            $project_manager
        );

        $this->git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
            $this->repository_factory
        );

        $this->gerrit_server_factory  = new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $git_dao,
            $this->git_system_event_manager,
            $project_manager
        );

        $fine_grained_dao     = new FineGrainedDao();
        $fine_grained_updater = new FineGrainedUpdater($fine_grained_dao);

        $default_fine_grained_permission_factory = new DefaultFineGrainedPermissionFactory(
            $fine_grained_dao,
            new UGroupManager()
        );

        $default_fine_grained_permission_saver = new DefaultFineGrainedPermissionSaver($fine_grained_dao);

        $git_permission_manager = new GitPermissionsManager(
            new Git_PermissionsDao(),
            $this->git_system_event_manager,
            $fine_grained_updater,
            $default_fine_grained_permission_saver,
            $default_fine_grained_permission_factory,
            $fine_grained_dao
        );

        $this->representation_builder = new RepositoryRepresentationBuilder(
            $git_permission_manager,
            $this->gerrit_server_factory
        );

        $this->migration_handler = new MigrationHandler(
            $this->git_system_event_manager,
            $this->gerrit_server_factory,
            new Git_Driver_Gerrit_GerritDriverFactory (new GitBackendLogger()),
            new ProjectHistoryDao(),
            new Git_Driver_Gerrit_ProjectCreatorStatus(new Git_Driver_Gerrit_ProjectCreatorStatusDao())
        );

        $this->ci_token_manager = new CITokenManager(new CITokenDao());
    }

    /**
     * Return info about repository if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the repository
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeaders();
    }

    /**
     * @access hybrid
     *
     * @param int $id Id of the repository
     * @return GitRepositoryRepresentation | null
     *
     * @throws 403
     * @throws 404
     */
    public function get($id) {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        $this->sendAllowHeaders();

        return $this->representation_builder->build($user, $repository, GitRepositoryRepresentationBase::FIELDS_ALL);
    }

    /**
     * @url OPTIONS {id}/pull_requests
     *
     * @param int $id Id of the repository
     *
     * @throws 404
     */
    public function optionsPullRequests($id) {
        $this->checkPullRequestEndpointsAvailable();
        $this->sendAllowHeaders();
    }

    /**
     * Get git repository's pull requests
     *
     * User is not able to see a pull request in a git repository where he is not able to READ
     *
     * <pre>
     * /!\ PullRequest REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/pull_requests
     *
     * @access protected
     *
     * @param  int $id     Id of the repository
     * @param  int $limit  Number of elements displayed per page {@from path}
     * @param  int $offset Position of the first element to display {@from path}
     *
     * @return Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation
     *
     * @throws 403
     * @throws 404
     */
    public function getPullRequests($id, $limit = self::MAX_LIMIT, $offset = 0) {
        $this->checkAccess();
        $this->checkPullRequestEndpointsAvailable();
        $this->checkLimit($limit);

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);
        $result     = $this->getPaginatedPullRequests($repository, $limit, $offset);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, $result->total_size);

        return $result;
    }

    /**
     * Post a build status
     *
     * Format: { "status": "S|F|U", "branch": "master", "commit_reference": "0deadbeef", "token": "0000"}
     *
     * <pre>
     * /!\ REST route under construction and subject to changes /!\
     * </pre>
     * @url POST {id}/build_status
     *
     * @access hybrid
     *
     * @param int                       $id            Git repository id
     * @param BuildStatusPOSTRepresentation $build_data BuildStatus {@from body} {@type Tuleap\Git\REST\v1\BuildStatusPOSTRepresentation}
     *
     * @status 201
     * @throws 403
     * @throws 404
     * @throws 400
     */
    protected function postBuildStatus($id, BuildStatusPOSTRepresentation $build_status_data)
    {
        if (! $build_status_data->isStatusValid()) {
            throw new RestException(400, $build_status_data->status . ' is not a valid status.');
        }

        $repository = $this->repository_factory->getRepositoryById($id);

        if (! $repository) {
            throw new RestException(404, 'Repository not found.');
        }

        $repo_ci_token = $this->ci_token_manager->getToken($repository);

        if ($repo_ci_token === null) {
            $repo_ci_token = '';
        }

        if (! \hash_equals($build_status_data->token, $repo_ci_token)) {
            throw new RestException(403, 'Invalid token');
        }

        $git_exec = new Git_Exec($repository->getFullPath(), $repository->getFullPath());

        if (! $git_exec->doesObjectExists($build_status_data->commit_reference)) {
            throw new RestException(404, $build_status_data->commit_reference . ' does not reference a commit.');
        }

        if ($git_exec->getObjectType($build_status_data->commit_reference) !== 'commit') {
            throw new RestException(400, $build_status_data->commit_reference . ' does not reference a commit.');
        }

        $branch_ref = 'refs/heads/' . $build_status_data->branch;
        if (! in_array($branch_ref, $git_exec->getAllBranches())) {
            throw new RestException(400, $build_status_data->branch . ' is not a branch.');
        }

        EventManager::instance()->processEvent(
            REST_GIT_BUILD_STATUS,
            array(
                'repository'       => $repository,
                'branch'           => $build_status_data->branch,
                'commit_reference' => $build_status_data->commit_reference,
                'status'           => $build_status_data->status
            )
        );
    }

    /**
     * Patch Git repository
     *
     * Patch properties of a given Git repository
     *
     * <pre>
     * /!\ This REST route is under construction and subject to changes /!\
     * </pre>
     *
     * <br>
     * To migrate a repository in Gerrit:
     * <pre>
     * {<br>
     * &nbsp;"migrate_to_gerrit": {<br/>
     * &nbsp;&nbsp;"server": 1,<br/>
     * &nbsp;&nbsp;"permissions": "default"<br/>
     * &nbsp;}<br/>
     * }
     * </pre>
     *
     * <br>
     * To disconnect a repository in Gerrit:
     * <pre>
     * {<br>
     * &nbsp;"disconnect_from_gerrit": "read-only"<br/>
     * }
     * </pre>
     *
     * @url PATCH {id}
     * @access protected
     *
     * @param int    $id    Id of the Git repository
     * @param GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit {@from body}{@required false}
     * @param string $disconnect_from_gerrit {@from body}{@required false} {@choice delete,read-only,noop}
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    protected function patchId(
        $id,
        GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit = null ,
        $disconnect_from_gerrit = null
    ) {
        $this->checkAccess();

        $user       = $this->getCurrentUser();
        $repository = $this->getRepository($user, $id);

        if (! $repository->userCanAdmin($user)) {
            throw new RestException(403, 'User is not allowed to migrate repository');
        }

        if ($migrate_to_gerrit && $disconnect_from_gerrit) {
            throw new RestException(403, 'Bad request. You can only migrate or disconnect a Git repository');
        }

        if ($migrate_to_gerrit) {
            $this->migrate($repository, $user, $migrate_to_gerrit);
        }

        if ($disconnect_from_gerrit) {
            $this->disconnect($repository, $disconnect_from_gerrit);
        }


        $this->sendAllowHeaders();
    }

    private function disconnect(GitRepository $repository, $disconnect_from_gerrit) {
        try {
            $this->migration_handler->disconnect($repository, $disconnect_from_gerrit);
        } catch (DeletePluginNotInstalledException $e) {
            throw new RestException(400, 'Gerrit delete plugin not installed.');
        } catch (RepositoryNotMigratedException $e) {
            //Do nothing
        }
    }

    private function migrate(
        GitRepository $repository,
        PFUser $user,
        GitRepositoryGerritMigratePATCHRepresentation $migrate_to_gerrit
    ) {
        $server_id   = $migrate_to_gerrit->server;
        $permissions = $migrate_to_gerrit->permissions;

        if ($permissions !== self::MIGRATE_NO_PERMISSION && $permissions !== self::MIGRATE_PERMISSION_DEFAULT) {
            throw new RestException(
                400,
                'Invalid permission provided. Valid values are ' .
                self::MIGRATE_NO_PERMISSION. ' or ' . self::MIGRATE_PERMISSION_DEFAULT
            );
        }

        try {
            return $this->migration_handler->migrate($repository, $server_id, $permissions, $user);
        } catch (RepositoryCannotBeMigratedException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (Git_RemoteServer_NotFoundException $exception) {
            throw new RestException(400, 'Gerrit server does not exist');
        } catch (RepositoryAlreadyInQueueForMigrationException $exception) {
            //Do nothing
        }
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    private function getRepository(PFUser $user, $id) {
        try {
            $repository = $this->repository_factory->getRepositoryByIdUserCanSee($user, $id);
        } catch (GitRepoNotReadableException $exception) {
            throw new RestException(403, 'Git repository not accessible for user');
        } catch (GitRepoNotFoundException $exception) {
            throw new RestException(404, 'Git repository not found');
        } catch (Exception $exception) {
            throw new RestException(403, 'Project not accessible for user');
        }

        return $repository;
    }

    private function getPaginatedPullRequests(GitRepository $repository, $limit, $offset) {
        $result = null;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY,
            array(
                'version'    => 'v1',
                'repository' => $repository,
                'limit'      => $limit,
                'offset'     => $offset,
                'result'     => &$result
            )
        );

        return $result;
    }

    private function checkPullRequestEndpointsAvailable() {
        $available = false;

        EventManager::instance()->processEvent(
            REST_GIT_PULL_REQUEST_ENDPOINTS,
            array(
                'available' => &$available
            )
        );

        if ($available === false) {
            throw new RestException(404, 'PullRequest plugin not activated');
        }
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGetPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function checkLimit($limit) {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }
}
