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
 * I convert the xml changeset data into data structure in order to create changeset in one artifact
 */
class Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder {

    const FIELDTYPE_STRING            = 'string';
    const FIELDTYPE_TEXT              = 'text';
    const FIELDTYPE_INT               = 'int';
    const FIELDTYPE_FLOAT             = 'float';
    const FIELDTYPE_DATE              = 'date';
    const FIELDTYPE_PERMS_ON_ARTIFACT = 'permissions_on_artifact';
    const FIELDTYPE_ATTACHEMENT       = 'file';
    const FIELDTYPE_OPENLIST          = 'open_list';
    const FIELDTYPE_LIST              = 'list';

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    /** @var string */
    private $extraction_path;

    /** @var Tracker_Artifact_XMLImport_XMLImportFieldStrategy[] */
    private $strategies;

    /** @var Logger */
    private $logger;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Artifact_XMLImport_XMLImportHelper $xml_import_helper,
        Tracker $tracker,
        Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer,
        $extraction_path,
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao,
        Logger $logger
    ) {
        $this->formelement_factory  = $formelement_factory;
        $this->tracker              = $tracker;
        $this->files_importer       = $files_importer;
        $this->extraction_path      = $extraction_path;
        $this->logger               = $logger;

        $alphanum_strategy = new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAlphanumeric();
        $this->strategies  = array(
            self::FIELDTYPE_PERMS_ON_ARTIFACT => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyPermissionsOnArtifact(),
            self::FIELDTYPE_ATTACHEMENT => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment(
                $this->extraction_path,
                $this->files_importer,
                $this->logger
            ),
            self::FIELDTYPE_OPENLIST => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyOpenList(),
            self::FIELDTYPE_STRING   => $alphanum_strategy,
            self::FIELDTYPE_TEXT     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyText(),
            self::FIELDTYPE_INT      => $alphanum_strategy,
            self::FIELDTYPE_FLOAT    => $alphanum_strategy,
            self::FIELDTYPE_DATE     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyDate(),
            self::FIELDTYPE_LIST     => new Tracker_Artifact_XMLImport_XMLImportFieldStrategyList($static_value_dao, $xml_import_helper)
        );
    }

    /**
     * @return array
     */
    public function getFieldsData(SimpleXMLElement $xml_field_change) {
        $data = array();

        if (! $xml_field_change->field_change) {
            return $data;
        }

        foreach ($xml_field_change->field_change as $field_change) {
            $field = $this->formelement_factory->getUsedFieldByName(
                $this->tracker->getId(),
                (string) $field_change['field_name']
            );

            if ($field) {
                $this->appendValidValue($data, $field, $field_change);
            } else {
                $this->logger->debug("Skipped unknown/unused field ".(string) $field_change['field_name']);
            }
        }
        return $data;
    }

    private function appendValidValue(array &$data, Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        try {
            $submitted_value = $this->getFieldData($field, $field_change);
            if ($field->validateField($this->createFakeArtifact(), $submitted_value)) {
                $data[$field->getId()] = $submitted_value;
            } else {
                $this->logger->warn("Skipped invalid value ".(string)$submitted_value." for field ".$field->getName());
            }
        } catch(Tracker_Artifact_XMLImport_Exception_NoAttachementsException $exception) {
            $this->logger->warn("Skipped invalid value for field ".$field->getName().': '.$exception->getMessage());
        } catch(Tracker_Artifact_XMLImport_Exception_ArtifactLinksAreIgnoredException $exception) {
            return;
        }
    }

    /**
     * A fake artifact is needed for validateField to work
     *
     * An artifact is needed by List type of field to do Workflow check
     * But as workflow is disabled we don't care
     *
     * @return Tracker_Artifact
     */
    private function createFakeArtifact() {
        return new Tracker_Artifact(-1, -1, -1, -1, -1);
    }

    private function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        $type = (string)$field_change['type'];

        if (! isset($this->strategies[$type])) {
            throw new Tracker_Artifact_XMLImport_Exception_ArtifactLinksAreIgnoredException();
        }
        return $this->strategies[$type]->getFieldData($field, $field_change);
    }
}
