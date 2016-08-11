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

namespace Tuleap\PullRequest;

use GitRepositoryFactory;
use GitRepository;
use UserManager;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;

class PullRequestCreator
{

    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * @var Dao
     */
    private $pull_request_dao;


    public function __construct(
        Factory $pull_request_factory,
        Dao $pull_request_dao,
        PullRequestMerger $pull_request_merger
    ) {
        $this->pull_request_factory = $pull_request_factory;
        $this->pull_request_dao     = $pull_request_dao;
        $this->pull_request_merger  = $pull_request_merger;
    }

    public function generatePullRequest(GitRepository $repository_src, $branch_src, GitRepository $repository_dest, $branch_dest, \PFUser $creator)
    {
        if (! $repository_src || ! $repository_dest) {
            return false;
        }

        if ($repository_src->getId() != $repository_dest->getId() && $repository_src->getParentId() != $repository_dest->getId()) {
            throw new \Exception('Pull requests can only target the same repository or its parent.');
        }

        if ($repository_dest->isMigratedToGerrit()) {
            throw new PullRequestRepositoryMigratedOnGerritException();
        }

        $executor       = new GitExec($repository_src->getFullPath(), $repository_src->getFullPath());
        $sha1_src       = $executor->getBranchSha1("refs/heads/$branch_src");
        $repo_dest_id   = $repository_dest->getId();
        $repo_src_id    = $repository_src->getId();

        if ($repo_src_id == $repo_dest_id) {
            $sha1_dest = $executor->getBranchSha1("refs/heads/$branch_dest");
        } else {
            $this->setUpRemote($executor, $repo_dest_id, $repository_dest->getFullPath());
            $sha1_dest = $executor->getBranchSha1("$repo_dest_id/$branch_dest");
        }

        if ($sha1_src === $sha1_dest) {
            throw new PullRequestCannotBeCreatedException();
        }

        $this->checkIfPullRequestAlreadyExists($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest);

        $commit_message = $executor->getCommitMessage($sha1_src);
        $first_line     = array_shift($commit_message);
        $other_lines    = implode("\n", $commit_message);

        $pull_request = new PullRequest(
            0,
            $first_line,
            $other_lines,
            $repo_src_id,
            $creator->getId(),
            time(),
            $branch_src,
            $sha1_src,
            $repo_dest_id,
            $branch_dest,
            $sha1_dest
        );

        $merge_status = $this->pull_request_merger->detectMergeabilityStatus($executor, $pull_request, $pull_request->getSha1Src(), $repository_src);
        $pull_request->setMergeStatus($merge_status);

        return $this->pull_request_factory->create($creator, $pull_request, $repository_src->getProjectId());
    }

    private function checkIfPullRequestAlreadyExists($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest)
    {
        $row = $this->pull_request_dao->searchByReferences($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest)->getRow();

        if ($row) {
            throw new PullRequestAlreadyExistsException();
        }
    }

    private function setUpRemote(GitExec $executor, $repo_id, $repo_path)
    {
        if (! $executor->remoteExists($repo_id)) {
            $executor->addRemote($repo_id, $repo_path);
        }
        $executor->fetchRemote($repo_id);
    }
}
