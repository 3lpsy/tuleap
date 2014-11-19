<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * I'm responsible to create system events with the right parameters
 */
class Git_SystemEventManager {

    /** @var SystemEventManager */
    private $system_event_manager;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    public function __construct(SystemEventManager $system_event_manager, GitRepositoryFactory $repository_factory) {
        $this->system_event_manager = $system_event_manager;
        $this->repository_factory   = $repository_factory;
    }

    public function queueRepositoryUpdate(GitRepository $repository) {
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_UPDATE::NAME,
                $repository->getId(),
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueRepositoryDeletion(GitRepository $repository) {
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_DELETE::NAME,
                $repository->getProjectId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId(),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        } else {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_LEGACY_REPO_DELETE::NAME,
                $repository->getProjectId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId(),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_ROOT
            );
        }
    }

    public function queueRemoteProjectDeletion(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            $repository->getId(). SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRemoteProjectReadOnly(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME,
            $repository->getId(). SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRepositoryFork(GitRepository $old_repository, GitRepository $new_repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REPO_FORK::NAME,
            $old_repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $new_repository->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGitShellAccess(GitRepository $repository, $type) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_LEGACY_REPO_ACCESS::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $type,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_ROOT
        );
    }

    public function queueMigrateToGerrit(GitRepository $repository, $remote_server_id, $gerrit_template_id) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id . SystemEvent::PARAMETER_SEPARATOR . $gerrit_template_id,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGerritReplicationKeyUpdate(Git_RemoteServer_GerritServer $server) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            $server->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueUserRenameUpdate($old_user_name, IHaveAnSSHKey $new_user) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_USER_RENAME::NAME,
            $old_user_name . SystemEvent::PARAMETER_SEPARATOR . $new_user->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGrokMirrorManifest(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GROKMIRROR_MANIFEST::NAME,
            $repository->getId(),
            SystemEvent::PRIORITY_LOW,
            SystemEvent::OWNER_APP
        );
    }

    public function isRepositoryMigrationToGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_MIGRATION::NAME, $repository->getId());
    }

    public function isProjectDeletionOnGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME, $repository->getId());
    }

    public function isProjectSetReadOnlyOnGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME, $repository->getId());
    }

    public function getTypes() {
        return array(
            SystemEvent_GIT_REPO_UPDATE::NAME,
            SystemEvent_GIT_REPO_DELETE::NAME,
            SystemEvent_GIT_REPO_FORK::NAME,
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME,
            SystemEvent_GIT_GROKMIRROR_MANIFEST::NAME,
            SystemEvent_GIT_USER_RENAME::NAME
        );
    }

    /**
     * Note: for a newly developed feature, it would be better to have a dedicated
     * queue for root event but
     * - The events below are meant to disapear when legacy backend will be removed
     * - This mean that for all new platforms there would be a new empty pane (git root
     *   events)
     * So it's better to make them run in the default queue like before
     */
    public function getTypesForDefaultQueue() {
        if ($this->repository_factory->hasGitShellRepositories()) {
            return array(
                SystemEvent_GIT_LEGACY_REPO_ACCESS::NAME,
                SystemEvent_GIT_LEGACY_REPO_DELETE::NAME,
            );
        }
        return array();
    }
}
