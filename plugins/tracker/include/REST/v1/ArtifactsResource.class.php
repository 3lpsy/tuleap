<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use \Tuleap\REST\ProjectAuthorization;
use \Tuleap\REST\Header;
use Tuleap\REST\AuthenticatedResource;
use \Luracast\Restler\RestException;
use \Tracker_ArtifactFactory;
use \Tracker_Artifact;
use \UserManager;
use \PFUser;
use \Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use \Tracker_FormElementFactory;
use \Tracker_REST_Artifact_ArtifactUpdater;
use \Tracker_REST_Artifact_ArtifactValidator;
use \Tracker_FormElement_InvalidFieldException;
use \Tracker_FormElement_InvalidFieldValueException;
use \Tracker_FormElement_RESTValueByField_NotImplementedException;
use \Tracker_Artifact_Attachment_FileNotFoundException;
use \Tracker_Exception;
use \Tuleap\Tracker\REST\ChangesetCommentRepresentation;
use \Tuleap\Tracker\REST\TrackerReference;
use \Tracker_NoChangeException;
use \TrackerFactory;
use \Tracker_REST_Artifact_ArtifactCreator;
use \Tuleap\Tracker\REST\Artifact\ArtifactReference;
use \Tracker_URLVerification;
use \Tracker_Artifact_Changeset as Changeset;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

class ArtifactsResource extends AuthenticatedResource {
    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;
    const ORDER_ASC      = 'asc';
    const ORDER_DESC     = 'desc';

    const VALUES_FORMAT_COLLECTION = 'collection';
    const VALUES_FORMAT_BY_FIELD   = 'by_field';
    const VALUES_FORMAT_ALL        = 'all';
    const VALUES_DEFAULT           = '';

    const EMPTY_NATURE = '';

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_REST_Artifact_ArtifactRepresentationBuilder */
    private $builder;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct()
    {
        $this->tracker_factory     = TrackerFactory::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->builder             = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            $this->formelement_factory,
            $this->artifact_factory,
            new NatureDao()
        );
    }

    /**
     * Get artifact
     *
     * Get the content of a given artifact. In addition to the artifact representation,
     * it sets Last-Modified header with the last update date of the element.
     * <br/><br/>
     * The "values_format" parameter allows you to choose how the artifact's values will be
     * formatted. When no "values_format" is provided, "collection" is chosen by default. Using "all"
     * will return both formats at the same time.
     * <br/><br/>
     * <pre>
     * /!\ Only alphanumeric fields are returned when choosing the "by_field" format! /!\
     * </pre>
     * <br/><br/>
     * Example with "collection":
     * <pre>
     * "values": [<br/>
     *   {<br/>
     *     "field_id": 1369,<br/>
     *     "type": "string",<br/>
     *     "label": "Title",<br/>
     *     "value": "Lorem ipsum dolor sit amet"<br/>
     *   },<br/>
     *   {<br/>
     *     "field_id": 1368,<br/>
     *     "type": "int",<br/>
     *     "label": "Remaining Effort",<br/>
     *     "value": 1447<br/>
     *   }<br/>
     * ]<br/>
     * </pre>
     * <br/><br/>
     * Example with "by_field":
     * <pre>
     * "values_by_field": {<br/>
     *   "title": {<br/>
     *     "field_id": 1369,<br/>
     *     "type": "string",<br/>
     *     "label": "Title",<br/>
     *     "value": "Lorem ipsum dolor sit amet"<br/>
     *   },<br/>
     *   "remaining_effort": {<br/>
     *     "field_id": 1368,<br/>
     *     "type": "int",<br/>
     *     "label": "Remaining Effort",<br/>
     *     "value": 1447<br/>
     *   }<br/>
     * }<br/>
     * </pre>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int    $id            Id of the artifact
     * @param string $values_format The format of the value {@from query} {@choice ,collection,by_field,all}
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public function getId($id, $values_format = self::VALUES_DEFAULT) {
        $this->checkAccess();

        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);
        $this->sendAllowHeadersForArtifact();
        $this->sendLastModifiedHeader($artifact);
        $this->sendETagHeader($artifact);

        if ($values_format === self::VALUES_DEFAULT || $values_format === self::VALUES_FORMAT_COLLECTION) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValues($user, $artifact);
        } elseif ($values_format === self::VALUES_FORMAT_BY_FIELD) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValuesByFieldValues($user, $artifact);
        } elseif ($values_format === self::VALUES_FORMAT_ALL) {
            $representation = $this->builder->getArtifactRepresentationWithFieldValuesInBothFormat($user, $artifact);
        }

        return $representation;
    }

    /**
     * Get possible natures for an artifact
     *
     * @url GET {id}/links
     * @access hybrid
     *
     * @param int $id Id of the artifact
     *
     * @return Tuleap\Tracker\REST\v1\ArtifactLinkRepresentation
     *
     */
    public function getArtifactLinkNatures($id)
    {
        $this->checkAccess();

        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        $artifact_link_representation = new ArtifactLinkRepresentation();
        $artifact_link_representation->build($artifact);

        $this->sendAllowHeadersForLinkNatures();
        return $artifact_link_representation;
    }

    /**
     * @url OPTIONS {id}/links
     *
     * @param int $id Id of the artifact
     */
    public function optionsArtifactLinkNatures($id)
    {
        $this->sendAllowHeadersForLinkNatures();
    }

    /**
     * Get all artifacts linked by nature
     *
     * Get all the artifacts linked by nature.
     * If no nature is provided, it will search linked artifacts witn no nature.
     *
     * @url GET {id}/linked_artifacts
     *
     * @access hybrid
     *
     * @param int    $id        Id of the artifact
     * @param string $direction The artifact link direction {@from query} {@choice forward,reverse}
     * @param string $nature    The artifact link nature to filter {@from query}
     * @param int    $limit     Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int    $offset    Position of the first element to display {@from path}{@min 0}
     *
     * @return Tuleap\Tracker\REST\v1\ArtifactLinkRepresentation
     *
     */
    public function getLinkedArtifacts(
        $id,
        $direction,
        $nature = self::EMPTY_NATURE,
        $limit  = 10,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkAccess();

        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        $linked_artifacts = $this->builder->getArtifactRepresentationCollection(
            $user,
            $artifact,
            $nature,
            $direction,
            $offset,
            $limit
        );

        $this->sendAllowHeadersForLinkedArtifacts();
        Header::sendPaginationHeaders($limit, $offset, $linked_artifacts->getTotalSize(), self::MAX_LIMIT);

        return array(
            self::VALUES_FORMAT_COLLECTION => $linked_artifacts->getArtifacts()
        );
    }

    /**
     * @url OPTIONS {id}/linked_artifacts
     *
     * @param int $id Id of the artifact
     */
    public function optionsLinkedArtifacts($id)
    {
        $this->sendAllowHeadersForLinkedArtifacts();
    }

    /**
     * @url OPTIONS {id}/changesets
     *
     * @param int $id Id of the artifact
     */
    public function optionsArtifactChangesets($id) {
        $this->sendAllowHeadersForChangesets();
    }

    /**
     * Get changesets
     *
     * Get the changesets of a given artifact
     *
     * @url GET {id}/changesets
     * @access hybrid
     *
     * @param int    $id     Id of the artifact
     * @param string $fields Whether you want to fetch all fields or just comments {@from path}{@choice all,comments}
     * @param int    $limit  Number of elements displayed per page {@from path}{@min 1}
     * @param int    $offset Position of the first element to display {@from path}{@min 0}
     * @param string $order  By default the changesets are returned by Changeset Id ASC. Set this parameter to either ASC or DESC {@from path}{@choice asc,desc}
     * @return array {@type Tuleap\Tracker\REST\ChangesetRepresentation}
     */
    public function getArtifactChangesets(
        $id,
        $fields = Changeset::FIELDS_ALL,
        $limit  = 10,
        $offset = self::DEFAULT_OFFSET,
        $order  = self::ORDER_ASC
    ) {
        $this->checkAccess();
        $user          = UserManager::instance()->getCurrentUser();
        $artifact      = $this->getArtifactById($user, $id);
        $reverse_order = (bool) (strtolower($order) === self::ORDER_DESC);
        $changesets    = $this->builder->getArtifactChangesetsRepresentation($user, $artifact, $fields, $offset, $limit, $reverse_order);

        $this->sendAllowHeadersForChangesets($artifact);
        Header::sendPaginationHeaders($limit, $offset, $changesets->totalCount(), self::MAX_LIMIT);
        return $changesets->toArray();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the artifact
     */
    public function optionsId($id) {
        $this->sendAllowHeadersForArtifact();
    }

    /**
     * Update artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>You will get an error (400) if there are no changes in submitted document</li>
     *  <li>You can re-use the same document provided by /artifacts/:id route
     *      section. Even if it contains more data. The extra data/info will be ignored</li>
     *  <li>You don't need to set all 'values' of the artifact, you can restrict to the modified ones</li>
     *  <li>Examples:</li>
     *    <ol>
     *      <li>To update a file field, the value must be an array of the ids of the attachment you want to keep attached together with the new ones you want to attach.
     *          Each new file must correspond to valid /artifact_temporary_files/:id resource.
     *          A user can only add their own temporary files</li>
     *      <li>To empty a file field of its content, the value should be empty (value: []).</li>
     *    </ol>
     * </ol>
     *
     * @url PUT {id}
     * @param string                          $id        Id of the artifact
     * @param array                           $values    Artifact fields values {@from body} {@type \Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation}
     * @param ChangesetCommentRepresentation  $comment   Comment about update {body, format} {@from body}
     *
     */
    protected function putId($id, array $values, ChangesetCommentRepresentation $comment = null) {
        $user     = UserManager::instance()->getCurrentUser();
        $artifact = $this->getArtifactById($user, $id);

        $this->sendAllowHeadersForArtifact($artifact);
        try {
            $updater = new Tracker_REST_Artifact_ArtifactUpdater(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                )
            );
            $updater->update($user, $artifact, $values, $comment);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        $this->sendLastModifiedHeader($artifact);
        $this->sendETagHeader($artifact);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptionsPost();
    }

    /**
     * Create artifact
     *
     * Things to take into account:
     * <ol>
     *  <li>You don't need to set all "values" of the artifact, you can pass only the ones you want to add,
     *      together with those that are required (depends on a given tracker's configuration).
     *  </li>
     *  <li>
     *    <pre>
     *    /!\ Only alphanumeric fields are taken into account when providing the "values_by_field" format! /!\
     *    </pre>
     *    <br/><br/>
     *    You can create an artifact with the "values_by_field" format.
     *    Example:
     *    <pre>
     *    {<br/>
     *      "tracker": {"id": 29},<br/>
     *      "values_by_field": {<br/>
     *        "title": {<br/>
     *          "value": "Lorem ipsum dolor sit amet"<br/>
     *        },<br/>
     *        "remaining_effort": {<br/>
     *          "value": 75<br/>
     *        }<br/>
     *      }<br/>
     *    }<br/>
     *    </pre>
     *    <br/><br/>
     *  </li>
     *  <li>Submitting with both "values" and "values_by_field" will result in an error.</li>
     *
     *  <li>Note on files:
     *  To attach a file on a file field, the value must contain the ids of the attachements you want to add.
     *    Example:
     *    <pre>
     *    {<br/>
     *      "field_id": 101,<br/>
     *      "value": [41, 42]<br/>
     *    }<br/>
     *    </pre>
     *    <br/><br/>
     *  Note that 41 and 42 ids are provided by /artifact_temporary_files routes.
     *  A user can only add their own temporary files.
     *  To create a temporary file, use POST on /artifact_temporary_files.
     *  </li>
     *  <li>Full Example:
     *  <pre>
     *  {<br/>
     *    "tracker": {"id" : 54},<br/>
     *    "values": [<br/>
     *      {"field_id": 1806, "value" : "my new artifact"},<br/>
     *      {"field_id": 1841, "bind_value_ids" : [254,598,148]}<br/>
     *    ]<br/>
     *  }<br/>
     *  </pre>
     *  </li>
     * </ol>
     *
     * @url POST
     * @param TrackerReference $tracker   Id of the artifact {@from body}
     * @param array  $values              Artifact fields values {@from body} {@type \Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation}
     * @param array  $values_by_field     Artifact fields values indexed by field {@from body}
     * @status 201
     * @return ArtifactReference
     */
    protected function post(TrackerReference $tracker, array $values = array(), array $values_by_field = array()) {
        $this->options();

        if (! empty($values) && ! empty($values_by_field)) {
            throw new RestException(400, 'Not able to deal with both formats at the same time');
        }

        try {
            $user    = UserManager::instance()->getCurrentUser();
            $creator = new Tracker_REST_Artifact_ArtifactCreator(
                new Tracker_REST_Artifact_ArtifactValidator(
                    $this->formelement_factory
                ),
                $this->artifact_factory,
                $this->tracker_factory
            );

            $artifact_reference = null;

            if (! empty($values)) {
                $artifact_reference = $creator->create($user, $tracker, $values);
            } elseif (! empty($values_by_field)) {
                $artifact_reference = $creator->createWithValuesIndexedByFieldName($user, $tracker, $values_by_field);
            } else {
                throw new RestException(400, "No valid data are provided");
            }

            $this->sendLastModifiedHeader($artifact_reference->getArtifact());
            $this->sendETagHeader($artifact_reference->getArtifact());
            $this->sendLocationHeader($artifact_reference->uri);
            return $artifact_reference;
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_RESTValueByField_NotImplementedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(500, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
    }

    /**
     * @param int $id
     *
     * @return Tracker_Artifact
     * @throws Project_AccessProjectNotFoundException 404
     * @throws Project_AccessException 403
     * @throws RestException 404
     */
    private function getArtifactById(PFUser $user, $id) {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if ($artifact) {
            if (! $artifact->userCanView($user)) {
                throw new RestException(403);
            }

            ProjectAuthorization::userCanAccessProject($user, $artifact->getTracker()->getProject(), new Tracker_URLVerification());
            return $artifact;
        }
        throw new RestException(404);
    }

    private function sendAllowHeadersForChangesets() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLinkNatures() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForLinkedArtifacts() {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForArtifact() {
        Header::allowOptionsGetPut();
    }

    private function sendLastModifiedHeader(Tracker_Artifact $artifact) {
        Header::lastModified($artifact->getLastUpdateDate());
    }

    private function sendETagHeader(Tracker_Artifact $artifact) {
        Header::eTag($artifact->getVersionIdentifier());
    }

    private function sendLocationHeader($uri) {
        $uri_with_api_version = '/api/v1/' . $uri;

        Header::Location($uri_with_api_version);
    }
}
