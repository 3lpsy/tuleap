<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Git_Command_Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;
use PFUser;
use GitRepository;
use GitRepositoryFactory;
use ForgeConfig;
use User;

class PullRequestCloser
{
    /**
     * @var Factory
     */
    private $pull_request_factory;

    /**
     * @var PullRequestMerger
     */
    private $pull_request_merger;

    public function __construct(Factory $factory, PullRequestMerger $pull_request_merger)
    {
        $this->pull_request_factory = $factory;
        $this->pull_request_merger  = $pull_request_merger;
    }

    public function abandon(PullRequest $pull_request)
    {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_ABANDONED) {
            return;
        }

        if ($status === PullRequest::STATUS_MERGED) {
            throw new PullRequestCannotBeAbandoned('This pull request has already been merged, it can no longer be abandoned');
        }
        $this->pull_request_factory->markAsAbandoned($pull_request);
    }

    public function doMerge(
        GitRepository $repository_dest,
        PullRequest $pull_request,
        PFUser $user
    ) {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_MERGED) {
            return;
        }

        if ($status === PullRequest::STATUS_ABANDONED) {
            throw new PullRequestCannotBeMerged(
                'This pull request has already been abandoned, it can no longer be merged'
            );
        }

        $this->pull_request_merger->doMergeIntoDestination($pull_request, $repository_dest, $user);

        $this->pull_request_factory->markAsMerged($pull_request);
    }

    public function fastForwardMerge(
        GitRepository $repository_src,
        GitRepository $repository_dest,
        PullRequest $pull_request
    ) {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_MERGED) {
            return;
        }

        if ($status === PullRequest::STATUS_ABANDONED) {
            throw new PullRequestCannotBeMerged(
                'This pull request has already been abandoned, it can no longer be merged'
            );
        }

        $temporary_name       = $this->getUniqueRandomDirectory();
        $executor             = new GitExec($temporary_name);

        try {
            $executor->init();
            $executor->fetchNoHistory($repository_dest->getFullPath(), $pull_request->getBranchDest());
            $executor->fetch($repository_src->getFullPath(), $pull_request->getBranchSrc());
            $executor->fastForwardMerge($pull_request->getSha1Src());
            $executor->push(escapeshellarg('file://' . $repository_dest->getFullPath()) . ' HEAD:' . escapeshellarg($pull_request->getBranchDest()));
        } catch (Git_Command_Exception $exception) {
            throw new PullRequestCannotBeMerged(
                'This Pull Request cannot be merged. It seems that the attempted merge is not fast-forward'
            );
        }

        $this->cleanTemporaryRepository($temporary_name);

        $this->pull_request_factory->markAsMerged($pull_request);
    }

    private function getUniqueRandomDirectory()
    {
        $tmp = ForgeConfig::get('codendi_cache_dir');

        return exec("mktemp -d -p $tmp pr_XXXXXX");
    }

    private function cleanTemporaryRepository($temporary_name)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $temporary_name,
                FileSystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $filename => $file_information) {
            if ($file_information->isDir()) {
                rmdir($filename);
            } else {
                unlink($filename);
            }
        }

        rmdir($temporary_name);
    }
}
