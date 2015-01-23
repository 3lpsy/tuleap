<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use UserManager;
use PFUser;
use Tracker_ArtifactFactory;
use AgileDashboard_Milestone_Backlog_BacklogItem;
use Tracker_ArtifactDao;
use Tracker_SlicedArtifactsBuilder;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact;
use TrackerFactory;
use Tracker_SemanticManager;
use Tracker_SemanticCollection;
use Tracker_FormElementFactory;
use Tracker_Semantic_Title;
use Tracker_Semantic_Status;
use AgileDashBoard_Semantic_InitialEffort;

/**
 * Wrapper for Backlog_Items related REST methods
 */
class BacklogItemResource {

    const MAX_LIMIT = 100;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var UserManager */
    private $user_manager;

    /* @var TrackerFactory */
    private $tracker_factory;

    /** @var BacklogItemsUpdater */
    private $artifactlink_updater;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    public function __construct() {
        $this->user_manager = UserManager::instance();

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $this->user_manager
        );

        $this->artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->tracker_factory      = TrackerFactory::instance();
        $this->artifactlink_updater = new ArtifactLinkUpdater($priority_manager);
        $this->resources_patcher    = new ResourcesPatcher(
            $this->artifactlink_updater,
            $this->artifact_factory,
            $priority_manager
        );
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the BacklogItem
     */
    public function options($id) {
        $this->sendAllowHeader();
    }

    /**
     * Get backlog item
     *
     * Get a backlog item representation
     *
     * @url GET {id}
     *
     * @param int $id Id of the Backlog Item
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function get($id) {
        $current_user = $this->getCurrentUser();
        $artifact     = $this->getArtifact($id);
        $backlog_item = $this->getBacklogItem($current_user, $artifact);

        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();
        $backlog_item_representation         = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);

        $this->sendAllowHeader();

        return $backlog_item_representation;
    }

    private function getBacklogItem(PFUser $current_user, Tracker_Artifact $artifact) {
        $semantic_manager = new Tracker_SemanticManager($artifact->getTracker());
        $semantics        = $semantic_manager->getSemantics();

        $artifact     = $this->updateArtifactTitleSemantic($current_user, $artifact, $semantics);
        $backlog_item = new AgileDashboard_Milestone_Backlog_BacklogItem($artifact);
        $backlog_item = $this->updateBacklogItemStatusSemantic($current_user, $artifact, $backlog_item, $semantics);
        $backlog_item = $this->updateBacklogItemInitialEffortSemantic($current_user, $artifact, $backlog_item, $semantics);

        return $backlog_item;
    }

    private function updateArtifactTitleSemantic(PFUser $current_user, Tracker_Artifact $artifact, Tracker_SemanticCollection $semantics) {
        $semantic_title = $semantics[Tracker_Semantic_Title::NAME];
        $title_field    = $semantic_title->getField();

        if ($title_field && $title_field->userCanRead($current_user)) {
            $artifact->setTitle($title_field->getRESTValue($current_user, $artifact->getLastChangeset())->value);
        }

        return $artifact;
    }

    private function updateBacklogItemStatusSemantic(PFUser $current_user, Tracker_Artifact $artifact, AgileDashboard_Milestone_Backlog_BacklogItem $backlog_item, Tracker_SemanticCollection $semantics) {
        $semantic_status = $semantics[Tracker_Semantic_Status::NAME];

        if ($semantic_status && $semantic_status->getField()->userCanRead($current_user)) {
            $label = $semantic_status->getNormalizedStatusLabel($artifact);

            if ($label) {
                $backlog_item->setStatus($label);
            }
        }

        return $backlog_item;
    }

    private function updateBacklogItemInitialEffortSemantic(PFUser $current_user, Tracker_Artifact $artifact, AgileDashboard_Milestone_Backlog_BacklogItem $backlog_item, Tracker_SemanticCollection $semantics) {
        $semantic_initial_effort = $semantics[AgileDashBoard_Semantic_InitialEffort::NAME];
        $initial_effort_field    = $semantic_initial_effort->getField();

        if ($initial_effort_field && $initial_effort_field->userCanRead($current_user)) {
            $backlog_item->setInitialEffort($initial_effort_field->getFullRESTValue($current_user, $artifact->getLastChangeset())->value);
        }

        return $backlog_item;
    }

    /**
     * Get children
     *
     * Get the children of a given Backlog Item
     *
     * @url GET {id}/children
     *
     * @param int $id     Id of the Backlog Item
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     * @throws 406
     */
    protected function getChildren($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $current_user                        = $this->getCurrentUser();
        $artifact                            = $this->getArtifact($id);
        $backlog_items_representations       = array();
        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();

        $sliced_children = $this->getSlicedArtifactsBuilder()->getSlicedChildrenArtifactsForUser($artifact, $this->getCurrentUser(), $limit, $offset);

        foreach ($sliced_children->getArtifacts() as $child) {
            $backlog_item                    = $this->getBacklogItem($current_user, $child);
            $backlog_items_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaderForChildren();
        $this->sendPaginationHeaders($limit, $offset, $sliced_children->getTotalSize());

        return $backlog_items_representations;
    }

    /**
     * Partial re-order of backlog items plus update of children
     *
     * Define the priorities of some children of a given Backlog Item
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * <br>
     * Add example:
     * <pre>
     * "add": [
     *   {
     *     "id": 34
     *     "remove_from": 56
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Will remove element id 34 from 56 children and add it to current backlog_items children
     *
     * @url PATCH {id}/children
     *
     * @param int                                                   $id    Id of the Backlog Item
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation    $order Order of the children {@from body}
     * @param array                                                 $add   Ids to add to backlog_items content  {@from body}
     *
     * @throws 400
     * @throws 404
     * @throws 409
     */
    protected function patch($id, OrderRepresentation $order = null, array $add = null) {

        $artifact = $this->getArtifact($id);
        $user     = $this->getCurrentUser();

        try {
            $indexed_children_ids = $this->getChildrenArtifactIds($user, $artifact);

            if ($add) {
                $this->resources_patcher->startTransaction();
                $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
                if (count($to_add)) {
                    $validator = new PatchAddRemoveValidator(
                       $indexed_children_ids,
                       new PatchAddBacklogItemsValidator(
                           $this->artifact_factory,
                           $this->tracker_factory->getPossibleChildren($artifact->getTracker()),
                           $id
                       )
                   );
                   $backlog_items_ids = $validator->validate($id, array(), $to_add);

                   $this->artifactlink_updater->updateArtifactLinks($user, $artifact, $backlog_items_ids, array());
                   $indexed_children_ids = array_flip($backlog_items_ids);
                }
                $this->resources_patcher->commit();
            }

            if ($order) {
                $order->checkFormat($order);
                $order_validator = new OrderValidator($indexed_children_ids);
                $order_validator->validate($order);

                $this->resources_patcher->updateArtifactPriorities($order);
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (OrderIdOutOfBoundException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (ArtifactCannotBeChildrenOfException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getArtifact($id) {
        $artifact     = $this->artifact_factory->getArtifactById($id);
        $current_user = $this->getCurrentUser();

        if (! $artifact) {
            throw new RestException(404, 'Backlog Item not found');
        } else if (! $artifact->userCanView($current_user)) {
            throw new RestException(403, 'You cannot access to this backlog item');
        }

        return $artifact;
    }

    private function getChildrenArtifactIds(PFUser $user, Tracker_Artifact $artifact) {
        $linked_artifacts_index = array();
        foreach ($artifact->getChildrenForUser($user) as $artifact) {
            $linked_artifacts_index[$artifact->getId()] = true;
        }
        return $linked_artifacts_index;
    }

    /**
     * @url OPTIONS {id}/children
     *
     * @param int $id Id of the BacklogItem
     *
     * @throws 404
     */
    public function optionsChildren($id) {
        $this->sendAllowHeaderForChildren();
    }

    private function getSlicedArtifactsBuilder() {
        return new Tracker_SlicedArtifactsBuilder(new Tracker_ArtifactDao());
    }

    private function checkContentLimit($limit) {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    private function sendAllowHeader() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeaderForChildren() {
        Header::allowOptionsGetPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function getCurrentUser() {
        return $this->user_manager->getCurrentUser();
    }
}
