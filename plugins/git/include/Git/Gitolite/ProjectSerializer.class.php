<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

class Git_Gitolite_ProjectSerializer {

    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    /**
     * @var Git_Gitolite_ConfigPermissionsSerializer
     */
    private $permissions_serializer;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /** @var Logger */
    private $logger;

    public function __construct(
            Logger $logger,
            GitRepositoryFactory $repository_factory,
            Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
            Git_GitRepositoryUrlManager $url_manager) {
        $this->logger = $logger;
        $this->repository_factory = $repository_factory;
        $this->permissions_serializer = $permissions_serializer;
        $this->url_manager = $url_manager;
    }

    /**
     * Save on filesystem all permission configuration for a project
     *
     * @param Project $project
     */
    public function dumpProjectRepoConf(Project $project) {
        $this->logger->debug("Dumping project repo conf for: " . $project->getUnixName());

        $project_config = '';
        foreach ($this->repository_factory->getAllRepositoriesOfProject($project) as $repository) {

            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . "...");
            $project_config .= $this->fetchReposConfig($project, $repository);
            $this->logger->debug("Fetching Repo Configuration: " . $repository->getName() . ": done");
        }

        return $project_config;
    }

    protected function fetchReposConfig(Project $project, GitRepository $repository) {
        $repo_full_name   = $this->repoFullName($repository, $project->getUnixName());
        $repo_config  = 'repo '. $repo_full_name . PHP_EOL;
        $repo_config .= $this->fetchMailHookConfig($project, $repository);
        $repo_config .= $this->permissions_serializer->getForRepository($repository);
        $description = preg_replace( "%\s+%", ' ', $repository->getDescription());
        $repo_config .= "$repo_full_name = \"$description\"".PHP_EOL;

        return $repo_config. PHP_EOL;
    }

    public function repoFullName(GitRepository $repo, $unix_name) {
        require_once GIT_BASE_DIR.'/PathJoinUtil.php';
        return unixPathJoin(array($unix_name, $repo->getFullName()));
    }

    /**
     * Returns post-receive-email hook config in gitolite format
     *
     * @param Project $project
     * @param GitRepository $repository
     */
    public function fetchMailHookConfig($project, $repository) {
        $conf  = '';
        $conf .= ' config hooks.showrev = "';
        $conf .= $repository->getPostReceiveShowRev($this->url_manager);
        $conf .= '"';
        $conf .= PHP_EOL;
        if ($repository->getNotifiedMails() && count($repository->getNotifiedMails()) > 0) {
            $conf .= ' config hooks.mailinglist = "'. implode(', ', $repository->getNotifiedMails()). '"';
        } else {
            $conf .= ' config hooks.mailinglist = ""';
        }
        $conf .= PHP_EOL;
        if ($repository->getMailPrefix() != GitRepository::DEFAULT_MAIL_PREFIX) {
            $conf .= ' config hooks.emailprefix = "'. $repository->getMailPrefix() .'"';
            $conf .= PHP_EOL;
        }
        return $conf;
    }
}
