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

class Tracker_Artifact_XMLImport {

    /** @var boolean */
    private $send_notifications;

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_ArtifactCreator */
    private $artifact_creator;

    /** @var Tracker_Artifact_Changeset_NewChangesetCreatorBase */
    private $new_changeset_creator;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_Artifact_XMLImport_XMLImportHelper */
    private $xml_import_helper;

    /** @var Tracker_FormElement_Field_List_Bind_Static_ValueDao */
    private $static_value_dao;

    /** @var WrapperLogger */
    private $logger;

    /**
     * @param XML_RNGValidator $rng_validator
     * @param Tracker_ArtifactCreator $artifact_creator
     * @param Tracker_Artifact_Changeset_NewChangesetCreatorBase $new_changeset_creator
     * @param Tracker_FormElementFactory $formelement_factory
     * @param Tracker_Artifact_XMLImport_XMLImportHelper $xml_import_helper
     * @param Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao
     * @param Logger $logger
     * @param boolean $send_notifications
     */
    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactCreator $artifact_creator,
        Tracker_Artifact_Changeset_NewChangesetCreatorBase $new_changeset_creator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Artifact_XMLImport_XMLImportHelper $xml_import_helper,
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao,
        Logger $logger,
        $send_notifications
    ) {

        $this->rng_validator         = $rng_validator;
        $this->artifact_creator      = $artifact_creator;
        $this->new_changeset_creator = $new_changeset_creator;
        $this->formelement_factory   = $formelement_factory;
        $this->xml_import_helper     = $xml_import_helper;
        $this->static_value_dao      = $static_value_dao;
        $this->logger                = new WrapperLogger($logger, 'XML import');
        $this->send_notifications    = $send_notifications;
    }

    public function importFromArchive(Tracker $tracker, Tracker_Artifact_XMLImport_XMLImportZipArchive $archive) {
        $archive->extractFiles();
        $xml = simplexml_load_string($archive->getXML());
        $extraction_path = $archive->getExtractionPath();
        $this->importFromXML($tracker, $xml, $extraction_path);
        $archive->cleanUp();
    }

    public function importFromFile(Tracker $tracker, $xml_file_path) {
        $xml_security = new XML_Security();
        $xml = $xml_security->loadFile($xml_file_path);
        $xml_file_path = "";
        $this->importFromXML(
            $tracker,
            $xml,
            $xml_file_path
        );
    }

    public function importFromXML(Tracker $tracker, SimpleXMLElement $xml_element, $extraction_path) {
        $this->rng_validator->validate($xml_element);
        foreach ($xml_element->artifact as $artifact) {
            $this->importOneArtifactFromXML($tracker, $artifact, $extraction_path);
        }
    }

    /**
     * @param Tracker          $tracker
     * @param SimpleXMLElement $xml_artifact
     * @param string           $extraction_path
     *
     * @return Tracker_Artifact|null The created artifact
     */
    public function importOneArtifactFromXML(Tracker $tracker, SimpleXMLElement $xml_artifact, $extraction_path) {
        try {
            $this->logger->info("Import {$xml_artifact['id']}");

            $files_importer = new Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact($xml_artifact);
            $fields_data_builder = new Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder(
                $this->formelement_factory,
                $this->xml_import_helper,
                $tracker,
                $files_importer,
                $extraction_path,
                $this->static_value_dao,
                $this->logger
            );

            $tracker->getWorkflow()->disable();

            return $this->importOneArtifact($tracker, $xml_artifact, $fields_data_builder);
        } catch (Tracker_Artifact_Exception_CannotCreateInitialChangeset $exception) {
            $this->logger->error("Impossible to create artifact: ".$exception->getMessage());
        } catch (Tracker_Artifact_Exception_EmptyChangesetException $exception) {
            $this->logger->error("Impossible to create artifact, there is no valid data to import for initial changeset: ".$exception->getMessage());
        } catch (Exception $exception) {
            $this->logger->error("Unexpected exception: ".$exception->getMessage());
        }
    }

    /**
     * @return Tracker_Artifact|null The created artifact
     */
    private function importOneArtifact(
        Tracker $tracker,
        SimpleXMLElement $xml_artifact,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $this->logger->push('tv3 #'.(string)$xml_artifact['id']);
        if (count($xml_artifact->changeset) > 0) {
            $changesets      = $this->getSortedBySubmittedOn($xml_artifact->changeset);
            $first_changeset = array_shift($changesets);
            $artifact        = $this->importInitialChangeset($tracker, $first_changeset, $fields_data_builder);
            $this->logger->info("--> new artifact {$artifact->getId()}");
            if (count($changesets)) {
                $this->importRemainingChangesets($artifact, $changesets, $fields_data_builder);
            }
            $this->logger->pop();
            return $artifact;
        }
    }

    private function getSortedBySubmittedOn(SimpleXMLElement $changesets) {
        $changeset_array = array();
        foreach ($changesets as $changeset) {
            $changeset_array[$this->getSubmittedOn($changeset)] = $changeset;
        }
        ksort($changeset_array, SORT_NUMERIC);
        return $changeset_array;
    }

    private function importInitialChangeset(
        Tracker $tracker,
        SimpleXMLElement $xml_changeset,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $fields_data = $fields_data_builder->getFieldsData($xml_changeset->field_change);
        if (count($fields_data) > 0) {
            $artifact = $this->artifact_creator->create(
                $tracker,
                $fields_data,
                $this->getSubmittedBy($xml_changeset),
                $this->getSubmittedOn($xml_changeset),
                $this->send_notifications
            );
            if ($artifact) {
                return $artifact;
            } else {
                return $this->createFakeInitialChangeset($tracker, $xml_changeset);
            }
        }
        throw new Tracker_Artifact_Exception_EmptyChangesetException();
    }

    private function createFakeInitialChangeset(Tracker $tracker, SimpleXMLElement $xml_changeset) {
        $this->logger->warn("Impossible to create artifact with first changeset, create a fake one instead: ".$GLOBALS['Response']->getAndClearRawFeedback());
        return $this->artifact_creator->create(
            $tracker,
            array(),
            $this->getSubmittedBy($xml_changeset),
            $this->getSubmittedOn($xml_changeset),
            $this->send_notifications
        );
    }

    private function importRemainingChangesets(
        Tracker_Artifact $artifact,
        array $xml_changesets,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $count = 0;
        $this->logger->push('tv5 #'.$artifact->getId());
        foreach($xml_changesets as $xml_changeset) {
            try {
                $count++;
                $this->logger->push("changeset $count");
                $initial_comment_body   = '';
                $initial_comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;
                if (isset($xml_changeset->comments) && count($xml_changeset->comments->comment) > 0) {
                    $initial_comment_body   = (string)$xml_changeset->comments->comment[0]->body;
                    $initial_comment_format = (string)$xml_changeset->comments->comment[0]->body['format'];
                }
                $changeset = $this->new_changeset_creator->create(
                    $artifact,
                    $fields_data_builder->getFieldsData($xml_changeset->field_change),
                    $initial_comment_body,
                    $this->getSubmittedBy($xml_changeset),
                    $this->getSubmittedOn($xml_changeset),
                    $this->send_notifications,
                    $initial_comment_format
                );
                if ($changeset) {
                    $this->updateComments($changeset, $xml_changeset);
                } else {
                    $this->logger->warn("Impossible to create changeset $count: ".$GLOBALS['Response']->getAndClearRawFeedback());
                }
                $this->logger->pop();
            } catch (Tracker_NoChangeException $exception) {
                $this->logger->warn("No Change for changeset $count");
                $this->logger->pop();
            } catch (Exception $exception) {
                $this->logger->warn("Unexpected error at changeset $count: ".$exception->getMessage());
                $this->logger->pop();
            }
        }
        $this->logger->pop();
    }

    private function updateComments(Tracker_Artifact_Changeset $changeset, SimpleXMLElement $xml_changeset) {
        if (isset($xml_changeset->comments) && count($xml_changeset->comments->comment) > 1) {
            $all_comments = $xml_changeset->comments->comment;
            for ($i = 1; $i < count($all_comments); ++$i) {
                $changeset->updateComment(
                    (string)$all_comments[$i]->body,
                    $this->getSubmittedBy($all_comments[$i]),
                    (string)$all_comments[$i]->body['format'],
                    $this->getSubmittedOn($all_comments[$i])
                );
            }
        }
    }

    private function getSubmittedBy(SimpleXMLElement $xml_changeset) {
        return $this->xml_import_helper->getUser($xml_changeset->submitted_by);
    }

    private function getSubmittedOn(SimpleXMLElement $xml_changeset) {
        return strtotime((string)$xml_changeset->submitted_on);
    }
}
