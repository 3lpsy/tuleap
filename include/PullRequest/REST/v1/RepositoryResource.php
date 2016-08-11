<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\GitExec;
use GitRepositoryFactory;
use GitRepository;
use ProjectManager;
use UserManager;
use PFUser;
use GitDao;
use ReferenceManager;

class RepositoryResource
{

    /** @var Tuleap\PullRequest\Dao */
    private $pull_request_dao;

    /** @var Tuleap\PullRequest\Factory */
    private $pull_request_factory;

    /** @var GitRepositoryFactory */
    private $git_repository_factory;

    /** @var UserManager */
    private $user_manager;

    public function __construct()
    {
        $this->pull_request_dao     = new PullRequestDao();
        $this->pull_request_factory = new PullRequestFactory($this->pull_request_dao, ReferenceManager::instance());
        $this->git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );
        $this->user_manager = UserManager::instance();
    }

    public function getPaginatedPullRequests(GitRepository $repository, $limit, $offset)
    {
        $result   = $this->pull_request_dao->getPaginatedPullRequests($repository->getId(), $limit, $offset);
        $user     = $this->user_manager->getCurrentUser();

        $total_size = (int) $this->pull_request_dao->foundRows();
        $collection = array();
        foreach ($result as $row) {
            $pull_request      = $this->pull_request_factory->getInstanceFromRow($row);

            $repository_src  = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
            $repository_dest = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());

            $executor                  = new GitExec($repository_src->getFullPath(), $repository_src->getFullPath());
            $pr_representation_factory = new PullRequestRepresentationFactory($executor);

            $pull_request_representation = $pr_representation_factory->getPullRequestRepresentation($pull_request, $repository_src, $repository_dest, $user);
            $collection[] = $pull_request_representation;
        }

        $representation = new RepositoryPullRequestRepresentation();
        $representation->build(
            $collection,
            $total_size
        );

        return $representation;
    }
}
