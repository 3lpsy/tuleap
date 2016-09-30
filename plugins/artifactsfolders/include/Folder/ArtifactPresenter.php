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

namespace Tuleap\ArtifactsFolders\Folder;

use DateTime;
use PFUser;
use Tracker_Artifact;
use UserHelper;

class ArtifactPresenter
{
    public $id;
    public $html_url;
    public $xref;
    public $tracker_label;
    public $project_label;
    public $status;
    public $title;
    public $submitter;
    public $last_modified_date;
    public $assignees;

    public function build(PFUser $current_user, Tracker_Artifact $artifact)
    {
        $this->id            = $artifact->getId();
        $this->html_url      = $artifact->getUri();
        $this->xref          = $artifact->getXRef();
        $this->tracker_label = $artifact->getTracker()->getName();
        $this->project_label = $artifact->getTracker()->getProject()->getUnconvertedPublicName();
        $this->status        = $artifact->getStatus();
        $this->title         = $artifact->getTitle();
        $this->submitter     = false;

        $submitter = $artifact->getSubmittedByUser();
        if ($submitter) {
            $this->submitter = $this->getUserPresenter($submitter);
        }

        $date                     = new DateTime('@' . $artifact->getLastUpdateDate());
        $this->last_modified_date = $date->format($GLOBALS['Language']->getText('system', 'datefmt'));

        $this->assignees = array();
        foreach ($artifact->getAssignedTo($current_user) as $assignee) {
            $this->assignees[] = $this->getUserPresenter($assignee);
        }

        if (! $this->status) {
            $this->status = '';
        }
    }

    private function getUserPresenter(PFUser $user)
    {
        $user_helper = UserHelper::instance();

        return array(
            'url'          => $user_helper->getUserUrl($user),
            'display_name' => $this->getDisplayName($user)
        );
    }

    private function getDisplayName(PFUser $user)
    {
        if ($user->isAnonymous()) {
            return $user->getEmail();
        }

        return UserHelper::instance()->getDisplayNameFromUser($user);
    }
}
