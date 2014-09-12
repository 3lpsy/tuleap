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

/**
 * I am a Template Method to create a new changeset (update of an artifact)
 */
abstract class Tracker_Artifact_Changeset_NewChangesetCreatorBase extends Tracker_Artifact_Changeset_ChangesetCreatorBase {

    /** @var Tracker_Artifact_ChangesetDao */
    protected $changeset_dao;

    /** @var Tracker_Artifact_Changeset_CommentDao */
    protected $changeset_comment_dao;

    /** @var EventManager */
    protected $event_manager;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Artifact_ChangesetDao $changeset_dao,
        Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        ReferenceManager $reference_manager
    ) {
        parent::__construct($fields_validator, $formelement_factory, $artifact_factory);
        $this->changeset_dao         = $changeset_dao;
        $this->changeset_comment_dao = $changeset_comment_dao;
        $this->event_manager         = $event_manager;
        $this->reference_manager     = $reference_manager;
    }

    /**
     * Update an artifact (means create a new changeset)
     *
     * @param array   $fields_data       Artifact fields values
     * @param string  $comment           The comment (follow-up) associated with the artifact update
     * @param PFUser  $submitter         The user who is doing the update
     * @param boolean $send_notification true if a notification must be sent, false otherwise
     * @param string  $comment_format    The comment (follow-up) type ("text" | "html")
     *
     * @throws Tracker_Exception In the validation
     * @throws Tracker_NoChangeException In the validation
     * @return Tracker_Artifact_Changeset|Boolean The new changeset if update is done without error, false otherwise
     */
    public function create(
        Tracker_Artifact $artifact,
        array $fields_data,
        $comment,
        PFUser $submitter,
        $submitted_on,
        $send_notification,
        $comment_format
    ) {
        $comment = trim($comment);

        $email = null;
        if ($submitter->isAnonymous()) {
            $email = $submitter->getEmail();
        }

        $this->validateNewChangeset($artifact, $fields_data, $comment, $submitter, $email);

        $previous_changeset = $artifact->getLastChangeset();

        /*
         * Post actions were run by validateNewChangeset but they modified a
         * different set of $fields_data in the case of massChange or soap requests;
         * we run them again for the current $fields_data
         */
        $artifact->getWorkflow()->before($fields_data, $submitter, $artifact);

        $changeset_id = $this->changeset_dao->create($artifact->getId(), $submitter->getId(), $email, $submitted_on);
        if(! $changeset_id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'unable_update'));
            return false;
        }

        $this->storeComment($artifact, $comment, $submitter, $submitted_on, $comment_format, $changeset_id);

        $this->storeFieldsValues($artifact, $previous_changeset, $fields_data, $submitter, $changeset_id);

        $new_changeset = new Tracker_Artifact_Changeset(
            $changeset_id,
            $artifact,
            $submitter->getId(),
            $submitted_on,
            $email
        );
        $artifact->addChangeset($new_changeset);

        $this->saveArtifactAfterNewChangeset(
            $artifact,
            $fields_data,
            $submitter,
            $new_changeset,
            $previous_changeset
        );

        $this->event_manager->processEvent(TRACKER_EVENT_ARTIFACT_POST_UPDATE, array('artifact' => $artifact));

        if ($send_notification) {
            $artifact->getChangeset($changeset_id)->notify();
        }

        return $new_changeset;
    }

    /**
     * @return void
     */
    protected abstract function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id
    );

    private function storeFieldsValues(Tracker_Artifact $artifact, $previous_changeset, array $fields_data, PFUser $submitter, $changeset_id) {
        $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
        foreach ($used_fields as $field) {
            $this->saveNewChangesetForField($field, $artifact, $previous_changeset, $fields_data, $submitter, $changeset_id);
        }
    }

    private function storeComment(
        Tracker_Artifact $artifact,
        $comment,
        PFUser $submitter,
        $submitted_on,
        $comment_format,
        $changeset_id
    ) {
        $comment_format = Tracker_Artifact_Changeset_Comment::checkCommentFormat($comment_format);

        $comment_added = $this->changeset_comment_dao->createNewVersion($changeset_id, $comment, $submitter->getId(), $submitted_on, 0, $comment_format);
        if (! $comment_added) {
            return;
        }

        $this->reference_manager->extractCrossRef(
            $comment,
            $artifact->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $artifact->getTracker()->getGroupID(),
            $submitter->getId(),
            $artifact->getTracker()->getItemName()
        );
    }

    private function validateNewChangeset(
        Tracker_Artifact $artifact,
        array $fields_data,
        $comment,
        PFUser $submitter,
        $email
    ) {
        if ($submitter->isAnonymous() && ($email == null || $email == '')) {
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'email_required');
            throw new Tracker_Exception($message);
        }

        if (! $this->fields_validator->validate($artifact, $fields_data)) {
            $message = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'fields_not_valid');
            throw new Tracker_Exception($message);
        }

        $last_changeset = $artifact->getLastChangeset();

        if (! $comment && ! $last_changeset->hasChanges($fields_data)) {
            throw new Tracker_NoChangeException($artifact->getId(), $artifact->getXRef());
        }

        $workflow = $artifact->getWorkflow();
        $fields_data = $this->field_initializator->process($artifact, $fields_data);

        if ($workflow) {
            /*
             * We need to run the post actions to validate the data
             */
            $workflow->before($fields_data, $submitter, $artifact);
            $workflow->checkGlobalRules($fields_data, $this->formelement_factory);
            //$GLOBALS['Language']->getText('plugin_tracker_artifact', 'global_rules_not_valid');
        }

        return true;
    }
}
