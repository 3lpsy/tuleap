<?php
/**
 * Copyright (c) Enalean, 2013, 2014. All Rights Reserved.
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

use BacklogItemReference;
use Tuleap\REST\TokenAuthentication;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use Planning_MilestoneFactory;
use AgileDashboard_BacklogItemDao;
use AgileDashboard_Milestone_Backlog_BacklogStrategyFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemBuilder;
use AgileDashboard_Milestone_MilestoneStatusCounter;
use Tracker_ArtifactDao;
use UserManager;
use Planning_Milestone;
use PFUser;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use Tracker_NoChangeException;
use Tracker_NoArtifactLinkFieldException;
use EventManager;
use URLVerification;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityManager;
use Tracker_Artifact_PriorityHistoryDao;
use PlanningPermissionsManager;

/**
 * Wrapper for milestone related REST methods
 */
class MilestoneResource {

    const MAX_LIMIT = 100;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var MilestoneResourceValidator */
    private $milestone_validator;

    /** @var MilestoneContentUpdater */
    private $milestone_content_updater;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var AgileDashboard_Milestone_Backlog_BacklogStrategyFactory */
    private $backlog_strategy_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    public function __construct() {
        $planning_factory               = PlanningFactory::build();
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $tracker_form_element_factory   = Tracker_FormElementFactory::instance();
        $status_counter                 = new AgileDashboard_Milestone_MilestoneStatusCounter(
            new AgileDashboard_BacklogItemDao(),
            new Tracker_ArtifactDao(),
            $this->tracker_artifact_factory
        );

        $this->milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $this->tracker_artifact_factory,
            $tracker_form_element_factory,
            TrackerFactory::instance(),
            $status_counter,
            new PlanningPermissionsManager()
        );

        $this->backlog_strategy_factory = new AgileDashboard_Milestone_Backlog_BacklogStrategyFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $planning_factory
        );

        $this->backlog_item_collection_factory = new AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory(
            new AgileDashboard_BacklogItemDao(),
            $this->tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->milestone_factory,
            $planning_factory,
            new AgileDashboard_Milestone_Backlog_BacklogItemBuilder()
        );

        $this->milestone_validator = new MilestoneResourceValidator(
            $planning_factory,
            $this->tracker_artifact_factory,
            $tracker_form_element_factory,
            $this->backlog_strategy_factory,
            $this->milestone_factory,
            $this->backlog_item_collection_factory
        );

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            $this->tracker_artifact_factory
        );

        $this->artifactlink_updater      = new ArtifactLinkUpdater($priority_manager);
        $this->milestone_content_updater = new MilestoneContentUpdater($tracker_form_element_factory, $this->artifactlink_updater);
        $this->resources_patcher         = new ResourcesPatcher(
            $this->artifactlink_updater,
            $this->tracker_artifact_factory,
            $priority_manager
        );

        $this->event_manager = EventManager::instance();
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Put children in a given milestone
     *
     * Put the new children of a given milestone.
     *
     * @url PUT {id}/milestones
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of the new milestones {@from body}
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    protected function putSubmilestones($id, array $ids) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        try {
            $this->milestone_validator->validateSubmilestonesFromBodyContent($ids, $milestone, $user);
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (SubMilestoneAlreadyHasAParentException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ElementCannotBeSubmilestoneException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (UserCannotReadSubMilestoneException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (UserCannotReadSubMilestoneException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (SubMilestoneDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        try {
            $this->artifactlink_updater->update(
                $ids,
                $milestone->getArtifact(),
                $user,
                new FilterValidSubmilestones(
                    $this->milestone_factory,
                    $milestone
            ));
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        }

        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * Get milestone
     *
     * Get the definition of a given the milestone
     *
     * Please note that the following fields are deprecated in favor of their
     * counterpart in 'resources':
     * <ul>
     *     <li>sub_milestones_uri</li>
     *     <li>backlog_uri</li>
     *     <li>content_uri</li>
     *     <li>cardwall_uri</li>
     *     <li>burndown_uri</li>
     * </ul>
     *
     * @url GET {id}
     *
     * @param int $id Id of the milestone
     *
     * @return Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation
     *
     * @throws 403
     * @throws 404
     */
    protected function getId($id) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $this->sendAllowHeadersForMilestone($milestone);

        $milestone_representation = new MilestoneRepresentation();
        $milestone_representation->build(
            $milestone,
            $this->milestone_factory->getMilestoneStatusCount(
                $user,
                $milestone
            ),
            $this->getBacklogTrackers($milestone),
            $this->milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user)
        );

        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_REST_GET_MILESTONE,
            array(
                'version'                  => 'v1',
                'user'                     => $user,
                'milestone'                => $milestone,
                'milestone_representation' => &$milestone_representation,
            )
        );

        return $milestone_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        Header::allowOptionsGet();
    }

    /**
     * @url OPTIONS {id}/milestones
     *
     * @param int $id ID of the milestone
     *
     * @throws 403
     * @throws 404
     */
    public function optionsMilestones($id) {
        $this->sendAllowHeaderForSubmilestones();
    }

    /**
     * Get sub-milestones
     *
     * Get the sub-milestones of a given milestone.
     * A sub-milestone is a decomposition of a milestone (for instance a Release has Sprints as submilestones)
     *
     * @url GET {id}/milestones
     *
     * @param int    $id Id of the milestone
     * @param string $order  In which order milestones are fetched. Default is asc {@from path}{@choice asc,desc}
     *
     * @return Array {@type Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getMilestones($id, $order = 'asc') {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $this->sendAllowHeaderForSubmilestones();

        $event_manager     = $this->event_manager;
        $milestone_factory = $this->milestone_factory;
        $strategy_factory  = $this->backlog_strategy_factory;

        $milestones = array_map(
            function (Planning_Milestone $milestone) use ($user, $event_manager, $milestone_factory, $strategy_factory) {
                $milestone_representation = new MilestoneRepresentation();
                $milestone_representation->build(
                    $milestone,
                    $milestone_factory->getMilestoneStatusCount(
                        $user,
                        $milestone
                    ),
                    $strategy_factory->getBacklogStrategy($milestone)->getDescendantTrackers(),
                    $milestone_factory->userCanChangePrioritiesInMilestone($milestone, $user)
                );

                $event_manager->processEvent(
                    AGILEDASHBOARD_EVENT_REST_GET_MILESTONE,
                    array(
                        'user'                     => $user,
                        'milestone'                => $milestone,
                        'milestone_representation' => &$milestone_representation,
                        'version'                  => 'v1'

                    )
                );

                return $milestone_representation;
            },
            $this->milestone_factory->getSubMilestones($user, $milestone)
        );

        if ($order === 'desc') {
            return array_reverse($milestones);
        }

        return $milestones;
    }

    /**
     * Get content
     *
     * Get the backlog items of a given milestone
     *
     * @url GET {id}/content
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getContent($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $milestone                           = $this->getMilestoneById($this->getCurrentUser(), $id);
        $backlog_items                       = $this->getMilestoneContentItems($milestone);
        $backlog_items_representations       = array();
        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaderForContent();
        $this->sendPaginationHeaders($limit, $offset,  count($backlog_items_representations));

        return array_slice($backlog_items_representations, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    public function optionsContent($id) {
        $this->sendAllowHeaderForContent();
    }

    /**
     * Put content in a given milestone
     *
     * Put the new content of a given milestone.
     *
     * @url PUT {id}/content
     *
     * @param int $id    Id of the milestone
     * @param array $ids Ids of backlog items {@from body}
     *
     * @throws 400
     * @throws 404
     */
    protected function putContent($id, array $ids) {
        $current_user = $this->getCurrentUser();
        $milestone    = $this->getMilestoneById($current_user, $id);

        try {
            $this->milestone_validator->validateArtifactsFromBodyContent($ids, $milestone, $current_user);
            $this->milestone_content_updater->updateMilestoneContent($ids, $current_user, $milestone);
        } catch (ArtifactDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsNotInBacklogTrackerException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        }

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, $id, $milestone->getProject()->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaderForContent();
    }

    /**
     * Partial re-order of milestone content relative to one element
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
     * Will remove element id 34 from milestone 56 content and add it to current milestone content
     *
     * @url PATCH {id}/content
     *
     * @param int                                                $id     Id of the milestone
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation $order  Order of the children {@from body}
     * @param array                                              $add    Ids to add/move to milestone content  {@from body}
     *
     * @throw 400
     * @throw 404
     * @throw 409
     */
    protected function patchContent($id, OrderRepresentation $order = null, array $add = null) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        try {
            if ($add) {
                $this->resources_patcher->startTransaction();
                $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
                if (count($to_add)) {
                    $linked_artifact_ids = $this->milestone_validator->getValidatedArtifactsIdsToAddOrRemoveFromContent($user, $milestone, array(), $to_add);
                    $this->artifactlink_updater->updateArtifactLinks($user, $milestone->getArtifact(), $to_add, array());
                }
                $this->resources_patcher->commit();
            }
        } catch (ArtifactDoesNotExistException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsNotInBacklogTrackerException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing
        } catch (Tracker_NoArtifactLinkFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
            return;
        }

        try {
            if ($order) {
                $order->checkFormat($order);
                $this->milestone_validator->canOrderContent($user, $milestone, $order);
                $this->resources_patcher->updateArtifactPriorities($order, $id, $milestone->getProject()->getId());
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (OrderIdOutOfBoundException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
        $this->sendAllowHeaderForContent();
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    public function optionsBacklog($id) {
        $this->sendAllowHeaderForBacklog();
    }

    /**
     * Get backlog
     *
     * Get the backlog items of a given milestone that can be planned in a sub-milestone
     *
     * @url GET {id}/backlog
     *
     * @param int $id     Id of the milestone
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 403
     * @throws 404
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $user          = $this->getCurrentUser();
        $milestone     = $this->getMilestoneById($user, $id);
        $backlog_items = $this->getMilestoneBacklogItems($user, $milestone);

        $backlog_item_representation_factory = new BacklogItemRepresentationFactory();
        $backlog_items_representation        = array();

        foreach ($backlog_items as $backlog_item) {
            $backlog_items_representation[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaderForBacklog();
        $this->sendPaginationHeaders($limit, $offset, count($backlog_items_representation));

        return array_slice($backlog_items_representation, $offset, $limit);
    }

    /**
     * Update backlog items priorities
     *
     * The array of ids given as argument will:
     * <ul>
     *  <li>update the priorities according to order in given array</li>
     * </ul>
     * <br />
     * <strong>WARNING:</strong> PUT will NOT add/remove element in backlog.
     * Remove from backlog doesn't make sense but add might be useful to deal
     * with inconsistent items. You can have a look at PATCH {id}/backlog for
     * add.
     *
     * @url PUT {id}/backlog
     *
     * @param int   $id  Id of the milestone
     * @param array $ids Ids of backlog items {@from body}{@type int}
     *
     * @throw 400
     * @throw 404
     */
    protected function putBacklog($id, array $ids) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        try {
            $this->milestone_validator->validateArtifactIdsAreInOpenAndUnplannedMilestone($ids, $milestone, $user);
        } catch (ArtifactIsNotInOpenAndUnplannedBacklogItemsException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            $this->artifactlink_updater->setOrderWithHistoryChangeLogging($ids, $id, $milestone->getProject()->getId());
        } catch (ItemListedTwiceException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $this->sendAllowHeaderForBacklog();
    }

    /**
     * Partial re-order of milestone backlog relative to one element.
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * },
     * "add": {
     *   [123]
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
     * Will remove element id 34 from milestone 56 backlog and add it to current backlog
     *
     * @url PATCH {id}/backlog
     *
     * @param int                                                $id    Id of the milestone Item
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation $order Order of the children {@from body}
     * @param array                                              $add    Ids to add/move to milestone backlog {@from body}
     *
     * @throw 400
     * @throw 404
     * @throw 409
     */
    protected function patchBacklog($id, OrderRepresentation $order = null, array $add = null) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);

        $to_add = array();
        try {
            if ($add) {
                $this->resources_patcher->startTransaction();
                $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
                if (count($to_add)) {
                    $valid_to_add = $this->milestone_validator->validateArtifactIdsCanBeAddedToBacklog($to_add, $milestone, $user);
                    $this->addMissingElementsToBacklog($milestone, $user, $valid_to_add);
                }
                $this->resources_patcher->commit();
            }
        } catch (Tracker_NoChangeException $exception) {
            // nothing to do
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            if ($order) {
                $order->checkFormat($order);
                $this->milestone_validator->validateArtifactIdsAreInOpenAndUnplannedMilestone(
                    $this->filterOutAddedElements($order, $to_add),
                    $milestone,
                    $user
                );

                $this->resources_patcher->updateArtifactPriorities($order, $id, $milestone->getProject()->getId());
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (ArtifactIsNotInOpenAndUnplannedBacklogItemsException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function addMissingElementsToBacklog(Planning_Milestone $milestone, PFUser $user, array $to_add) {
        if (count($to_add) > 0) {
            $this->artifactlink_updater->updateArtifactLinks($user, $milestone->getArtifact(), $to_add, array());
        }
    }

    private function filterOutAddedElements(OrderRepresentation $order, array $to_add = null) {
        $ids_to_validate = array_merge($order->ids, array($order->compared_to));
        if (is_array($to_add)) {
            return array_diff($ids_to_validate, $to_add);
        } else {
            return $ids_to_validate;
        }
    }

    /**
     * Add an item to the backlog of a milestone
     *
     * Add an item to the backlog of a milestone
     *
     * The item must  be of the allowed types (defined in the planning configuration).
     * The body of the request should be of the form :
     * {
     *      "artifact" : {
     *          "id" : 458
     *      }
     * }
     *
     * @url POST {id}/backlog
     *
     * @param int                  $id   Id of the milestone
     * @param BacklogItemReference $item Reference of the Backlog Item {@from body} {@type BacklogItemReference}
     *
     * @throw 400
     * @throw 403
     * @throw 404
     */
    protected function postBacklog($id, BacklogItemReference $item) {
        $user        = $this->getCurrentUser();
        $milestone   = $this->getMilestoneById($user, $id);

        $item_id  = $item->getArtifactId();
        $artifact = $this->getBacklogItemAsArtifact($user, $item_id);

        $allowed_trackers = $this->backlog_strategy_factory->getBacklogStrategy($milestone)->getDescendantTrackers();
        if (! $this->milestone_validator->canBacklogItemBeAddedToMilestone($artifact, $allowed_trackers)) {
            throw new RestException(400, "Item of type '".$artifact->getTracker()->getName(). "' cannot be added.");
        }

        try {
            $this->milestone_content_updater->appendElementToMilestoneBacklog($item_id, $user, $milestone);
        } catch (Tracker_NoChangeException $e) {
        }

        $this->sendAllowHeaderForBacklog();
    }

    private function getBacklogItemAsArtifact($user, $artifact_id) {
        $artifact = $this->tracker_artifact_factory->getArtifactById($artifact_id);

        if (! $artifact) {
            throw new RestException(400, 'Item does not exist');
        }

        if (! $artifact->userCanView()) {
            throw new  RestException(403, 'Cannot link this item');
        }

        return $artifact;
    }

    /**
     * Carwall options
     *
     * @url OPTIONS {id}/cardwall
     *
     * @param int $id Id of the milestone
     *
     * @throws 403
     * @throws 404
     */
    public function optionsCardwall($id) {
        $this->sendAllowHeadersForCardwall();
    }

    /**
     * Get a Cardwall
     *
     * @url GET {id}/cardwall
     *
     * @param int $id Id of the milestone
     *
     *
     *
     * @throws 403
     * @throws 404
     */
    protected function getCardwall($id) {
        $cardwall = null;
        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_REST_GET_CARDWALL,
            array(
                'version'   => 'v1',
                'milestone' => $this->getMilestoneById($this->getCurrentUser(), $id),
                'cardwall'  => &$cardwall
            )
        );

        return $cardwall;
    }

    /**
     * Options Burdown data
     *
     * @url OPTIONS {id}/burndown
     *
     * @param int $id Id of the milestone
     *
     * @return \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     */
    public function optionsBurndown($id) {
        $this->sendAllowHeadersForBurndown();
    }

    /**
     * Get Burdown data
     *
     * @url GET {id}/burndown
     *
     * @param int $id Id of the milestone
     *
     * @return \Tuleap\Tracker\REST\Artifact\BurndownRepresentation
     */
    public function getBurndown($id) {
        $auth = new TokenAuthentication();
        $auth->__isAllowed();

        $burndown = null;
        $this->event_manager->processEvent(
            AGILEDASHBOARD_EVENT_REST_GET_BURNDOWN,
            array(
                'version'   => 'v1',
                'user'      => $this->getCurrentUser(),
                'milestone' => $this->getMilestoneById($this->getCurrentUser(), $id),
                'burndown'  => &$burndown
            )
        );
        return $burndown;
    }

    private function getMilestoneById(PFUser $user, $id) {
        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $id);

        if (! $milestone) {
            throw new RestException(404);
        }

        ProjectAuthorization::userCanAccessProject($user, $milestone->getProject(), new URLVerification());

        if (! $milestone->getArtifact()->userCanView()) {
            throw new RestException(403);
        }

        return $milestone;
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    private function getMilestoneBacklogItems(PFUser $user, $milestone) {
        return $this->backlog_item_collection_factory->getUnplannedOpenCollection(
            $user,
            $milestone,
            $this->backlog_strategy_factory->getBacklogStrategy($milestone),
            false
        );
    }

    private function getMilestoneContentItems($milestone) {
        return $this->backlog_item_collection_factory->getAllCollection(
            $this->getCurrentUser(),
            $milestone,
            $this->backlog_strategy_factory->getSelfBacklogStrategy($milestone),
            ''
        );
    }

    private function getBacklogTrackers(Planning_Milestone $milestone) {
        return $this->backlog_strategy_factory->getBacklogStrategy($milestone)->getDescendantTrackers();
    }

    private function checkContentLimit($limit) {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    private function sendAllowHeaderForContent() {
        Header::allowOptionsGetPutPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaderForBacklog() {
        Header::allowOptionsGetPutPostPatch();
    }

    private function sendAllowHeaderForSubmilestones() {
        Header::allowOptionsGetPut();
    }

    private function sendAllowHeadersForMilestone($milestone) {
        $date = $milestone->getLastModifiedDate();
        Header::allowOptionsGet();
        Header::lastModified($date);
    }

    private function sendAllowHeadersForCardwall(){
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForBurndown(){
        Header::allowOptionsGet();
    }
}