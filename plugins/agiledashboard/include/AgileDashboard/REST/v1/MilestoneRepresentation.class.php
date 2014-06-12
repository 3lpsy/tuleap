<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use \Tuleap\Project\REST\ProjectReference;
use \Planning_Milestone;
use \Tuleap\Tracker\REST\TrackerReference;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;
use \Tuleap\Tracker\REST\Artifact\BurndownRepresentation;
use \Tuleap\REST\JsonCast;

use \AgileDashboard_MilestonesCardwallRepresentation;

/**
 * Representation of a milestone
 */
class MilestoneRepresentation {

    const ROUTE = 'milestones';

    /**
     * @var int
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $label;

    /**
     * @var int
     */
    public $submitted_by;

    /**
     * @var String
     */
    public $submitted_on;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var String
     */
    public $start_date;

    /**
     * @var String
     */
    public $end_date;

    /**
     * @var int
     */
    public $number_days_since_start;

    /**
     * @var int
     */
    public $number_days_until_end;

    /**
     * @var float
     */
    public $capacity;

    /**
     * @var float
     */
    public $remaining_effort;

    /**
     * @var string
     */
    public $status_value;

    /**
     * @var string
     */
    public $semantic_status;

    /**
     * @var \Tuleap\AgileDashboard\REST\v1\MilestoneParentReference | null
     */
    public $parent;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     */
    public $artifact;

    /**
     * @var string
     */
    public $sub_milestones_uri;

    /**
     * @var string
     */
    public $backlog_uri;

    /**
     * @var string
     */
    public $content_uri;

    /**
     * @var string
     */
    public $cardwall_uri = null;

    /**
     * @var string
     */
    public $burndown_uri = null;

    /**
     * @var string Date, when the last modification occurs
     */
    public $last_modified_date;

    /**
     * @var array
     */
    public $status_count;

    /**
     * @var array
     */
    public $resources = array(
        'milestones' => null,
        'backlog'    => null,
        'content'    => null,
        'cardwall'   => null,
        'burndown'   => null,
    );

    public function build(Planning_Milestone $milestone, array $status_count, array $backlog_trackers) {
        $this->id               = JsonCast::toInt($milestone->getArtifactId());
        $this->uri              = self::ROUTE . '/' . $this->id;
        $this->label            = $milestone->getArtifactTitle();
        $this->status_value     = $milestone->getArtifact()->getStatus();
        $this->semantic_status  = $milestone->getArtifact()->getSemanticStatusValue();
        $this->submitted_by     = JsonCast::toInt($milestone->getArtifact()->getFirstChangeset()->getSubmittedBy());
        $this->submitted_on     = JsonCast::toDate($milestone->getArtifact()->getFirstChangeset()->getSubmittedOn());
        $this->capacity         = JsonCast::toFloat($milestone->getCapacity());
        $this->remaining_effort = JsonCast::toFloat($milestone->getRemainingEffort());

        $this->project = new ProjectReference();
        $this->project->build($milestone->getProject());

        $this->artifact = new ArtifactReference();
        $this->artifact->build($milestone->getArtifact());

        $this->start_date = null;
        if ($milestone->getStartDate()) {
            $this->start_date              = JsonCast::toDate($milestone->getStartDate());
            $this->number_days_since_start = JsonCast::toInt($milestone->getDaysSinceStart());
        }

        $this->end_date = null;
        if ($milestone->getEndDate()) {
            $this->end_date              = JsonCast::toDate($milestone->getEndDate());
            $this->number_days_until_end = JsonCast::toInt($milestone->getDaysUntilEnd());
        }

        $this->parent = null;
        $parent       = $milestone->getParent();
        if ($parent) {
            $this->parent = new MilestoneParentReference();
            $this->parent->build($parent);
        }

        $this->sub_milestones_uri = $this->uri . '/'. self::ROUTE;
        $this->backlog_uri        = $this->uri . '/'. BacklogItemRepresentation::BACKLOG_ROUTE;
        $this->content_uri        = $this->uri . '/'. BacklogItemRepresentation::CONTENT_ROUTE;
        $this->last_modified_date = JsonCast::toDate($milestone->getLastModifiedDate());
        if($status_count) {
            $this->status_count = $status_count;
        }

        $milestone_tracker = new TrackerReference();
        $milestone_tracker->build($milestone->getPlanning()->getPlanningTracker());
        $this->resources['milestones'] = array(
            'uri'    => $this->uri . '/'. self::ROUTE,
            'accept' => array(
                'trackers' => array(
                    $milestone_tracker
                )
            )
        );
        $this->resources['backlog'] = array(
            'uri'    => $this->uri . '/'. BacklogItemRepresentation::BACKLOG_ROUTE,
            'accept' => array(
                'trackers' => $this->getTrackersRepresentation($backlog_trackers)
            )
        );
        $this->resources['content'] = array(
            'uri'    => $this->uri . '/'. BacklogItemRepresentation::CONTENT_ROUTE,
            'accept' => array(
                'trackers' => $this->getContentTrackersRepresentation($milestone)
            )
        );
    }

    private function getContentTrackersRepresentation(Planning_Milestone $milestone) {
        return $this->getTrackersRepresentation(
            $milestone->getPlanning()->getBacklogTrackers()
        );
    }

    private function getTrackersRepresentation(array $trackers) {
        $trackers_representation = array();
        foreach ($trackers as $tracker) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($tracker);
            $trackers_representation[] = $tracker_reference;
        }
        return $trackers_representation;
    }

    public function enableCardwall() {
        $this->cardwall_uri = $this->uri . '/'. AgileDashboard_MilestonesCardwallRepresentation::ROUTE;
        $this->resources['cardwall'] = array(
            'uri' => $this->cardwall_uri
        );
    }

    public function enableBurndown() {
        $this->burndown_uri = $this->uri . '/'. BurndownRepresentation::ROUTE;
        $this->resources['burndown'] = array(
            'uri' => $this->burndown_uri
        );
    }
}
