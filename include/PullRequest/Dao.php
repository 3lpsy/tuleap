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

use DataAccessObject;

class Dao extends DataAccessObject
{

    public function searchByPullRequestId($pull_request_id)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);

        $sql = "SELECT *
                FROM plugin_pullrequest_review
                WHERE id = $pull_request_id";

        return $this->retrieve($sql);
    }

    public function searchByReferences($repo_src_id, $sha1_src, $repo_dest_id, $sha1_dest)
    {
        $repo_src_id  = $this->da->quoteSmart($repo_src_id);
        $sha1_src     = $this->da->quoteSmart($sha1_src);
        $repo_dest_id = $this->da->quoteSmart($repo_dest_id);
        $sha1_dest    = $this->da->quoteSmart($sha1_dest);

        $sql = "SELECT *
                FROM plugin_pullrequest_review
                WHERE repository_id = $repo_src_id
                  AND sha1_src = $sha1_src
                  AND repo_dest_id = $repo_dest_id
                  AND sha1_dest = $sha1_dest";

        return $this->retrieve($sql);
    }

    public function searchOpenedBySourceBranch($repository_id, $branch_name)
    {
        $repository_id   = $this->da->escapeInt($repository_id);
        $branch_name     = $this->da->quoteSmart($branch_name);
        $status_review   = $this->da->quoteSmart(PullRequest::STATUS_REVIEW);

        $sql = "SELECT * FROM plugin_pullrequest_review
            WHERE repository_id=$repository_id AND branch_src=$branch_name
              AND status=$status_review";
        return $this->retrieve($sql);
    }

    public function searchOpenedByDestinationBranch($repository_id, $branch_name)
    {
        $repository_id   = $this->da->escapeInt($repository_id);
        $branch_name     = $this->da->quoteSmart($branch_name);
        $status_review   = $this->da->quoteSmart(PullRequest::STATUS_REVIEW);

        $sql = "SELECT * FROM plugin_pullrequest_review
            WHERE repo_dest_id=$repository_id AND branch_dest=$branch_name
              AND status=$status_review";
        return $this->retrieve($sql);
    }

    public function countPullRequestOfRepository($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT COUNT(*) as nb_pull_requests
                FROM plugin_pullrequest_review
                WHERE repository_id = $repository_id OR repo_dest_id = $repository_id";

        return $this->retrieve($sql);
    }

    public function create(
        $repository_id,
        $title,
        $description,
        $user_id,
        $creation_date,
        $branch_src,
        $sha1_src,
        $repo_dest_id,
        $branch_dest,
        $sha1_dest
    ) {
        $repository_id = $this->da->escapeInt($repository_id);
        $title         = $this->da->quoteSmart($title);
        $description   = $this->da->quoteSmart($description);
        $user_id       = $this->da->escapeInt($user_id);
        $creation_date = $this->da->escapeInt($creation_date);
        $branch_src    = $this->da->quoteSmart($branch_src);
        $sha1_src      = $this->da->quoteSmart($sha1_src);
        $repo_dest_id  = $this->da->quoteSmart($repo_dest_id);
        $branch_dest   = $this->da->quoteSmart($branch_dest);
        $sha1_dest     = $this->da->quoteSmart($sha1_dest);

        $sql = "INSERT INTO plugin_pullrequest_review (
                                repository_id,
                                title,
                                description,
                                user_id,
                                creation_date,
                                branch_src,
                                sha1_src,
                                repo_dest_id,
                                branch_dest,
                                sha1_dest
                            ) VALUES (
                                $repository_id,
                                $title,
                                $description,
                                $user_id,
                                $creation_date,
                                $branch_src,
                                $sha1_src,
                                $repo_dest_id,
                                $branch_dest,
                                $sha1_dest
                            )";

        return $this->updateAndGetLastId($sql);
    }

    public function updateSha1Src($pull_request_id, $sha1_src)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $sha1_src        = $this->da->quoteSmart($sha1_src);

        $sql = "UPDATE plugin_pullrequest_review SET sha1_src=$sha1_src WHERE id=$pull_request_id";
        return $this->update($sql);
    }

    public function updateSha1Dest($pull_request_id, $sha1_dest)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $sha1_dest       = $this->da->quoteSmart($sha1_dest);

        $sql = "UPDATE plugin_pullrequest_review SET sha1_dest=$sha1_dest WHERE id=$pull_request_id";
        return $this->update($sql);
    }

    public function getPaginatedPullRequests($repository_id, $limit, $offset)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $limit         = $this->da->escapeInt($limit);
        $offset        = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_review
                WHERE repository_id = $repository_id OR repo_dest_id = $repository_id
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function markAsAbandoned($pull_request_id)
    {
        $pull_request_id  = $this->da->escapeInt($pull_request_id);
        $abandoned_status = $this->da->quoteSmart(PullRequest::STATUS_ABANDONED);

        $sql = "UPDATE plugin_pullrequest_review
                SET status = $abandoned_status
                WHERE id = $pull_request_id";

        return $this->update($sql);
    }

    public function markAsMerged($pull_request_id)
    {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $merged_status   = $this->da->quoteSmart(PullRequest::STATUS_MERGED);

        $sql = "UPDATE plugin_pullrequest_review
                SET status = $merged_status
                WHERE id = $pull_request_id";

        return $this->update($sql);
    }
}
