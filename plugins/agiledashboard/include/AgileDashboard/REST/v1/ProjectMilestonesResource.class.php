<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use \Tracker_ArtifactFactory;
use \Tracker_ArtifactDao;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \PlanningFactory;
use \Planning_MilestoneFactory;
use \PFUser;
use \Project;
use \Luracast\Restler\RestException;
use \Tuleap\REST\Header;
use \AgileDashboard_Milestone_MilestoneStatusCounter;
use \AgileDashboard_BacklogItemDao;
use \Planning_Milestone;
use \AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectMilestonesResource {
    const MAX_LIMIT = 50;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_Milestone_MilestoneStatusCounter */
    private $status_counter;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct() {
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->planning_factory             = PlanningFactory::build();
        $this->tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->tracker_factory              = TrackerFactory::instance();
        $this->status_counter               = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $this->tracker_artifact_factory
        );
        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->tracker_artifact_factory,
            $this->tracker_form_element_factory,
            $this->tracker_factory,
            $this->status_counter
        );
    }

    /**
     * Get the top milestones of a given project
     */
    public function get(PFUser $user, $project, $limit, $offset) {

        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $all_milestones            = $this->getTopMilestones($user, $project);
        $milestones                = array_slice($all_milestones, $offset, $limit);
        $milestone_representations = array();

        foreach($milestones as $milestone) {
            $milestone_representation = new MilestoneRepresentation();
            $milestone_representation->build(
                $milestone,
                $this->milestone_factory->getMilestoneStatusCount($user, $milestone),
                $this->getBacklogTrackers($milestone)
            );
            $milestone_representations[] = $milestone_representation;
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_milestones));

        return $milestone_representations;
    }

    private function getStrategyFactory() {
        return new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            Tracker_ArtifactFactory::instance(),
            PlanningFactory::build()
        );
    }

    private function getBacklogTrackers(Planning_Milestone $milestone) {
        return $this->getStrategyFactory()->getBacklogStrategy($milestone)->getDescendantTrackers();
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $all_milestones = $this->getTopMilestones($user, $project);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_milestones));
    }

    /**
     * Return all the top milestones of all the plannings of the project
     * @param PFUser $user
     * @param int $project_id
     * @return array Planning_ArtifactMilestone
     */
    private function getTopMilestones(PFUser $user, Project $project) {

        $top_milestones = array();
        $milestones     = $this->milestone_factory->getSubMilestones($user, $this->milestone_factory->getVirtualTopMilestone($user, $project));

        foreach ($milestones as $milestone) {
            $top_milestones[] = $milestone;
        }

        return $top_milestones;
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }
}
?>
