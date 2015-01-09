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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_XMLExporter_ChangesetValuesXMLExporterTest extends TuleapTestCase {

    /** @var Tracker_XMLExporter_ChangesetValueXMLExporterVisitor */
    private $visitor;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue */
    private $int_changeset_value;

    /** @var Tracker_Artifact_ChangesetValue */
    private $float_changeset_value;

    /** @var Tracker_XMLExporter_ChangesetValuesXMLExporter */
    private $values_exporter;

    /** @var Tracker_Artifact */
    private $artifact;

    public function setUp() {
        parent::setUp();
        $this->artifact_xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->visitor         = mock('Tracker_XMLExporter_ChangesetValueXMLExporterVisitor');
        $this->values_exporter = new Tracker_XMLExporter_ChangesetValuesXMLExporter($this->visitor);

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', '*', '*', '*');
        $this->values = array(
            $this->int_changeset_value,
            $this->float_changeset_value
        );

        $this->artifact = mock('Tracker_Artifact');
    }

    public function itCallsTheVisitorForEachChangesetValue() {
        expect($this->visitor)->export()->count(2);
        expect($this->visitor)->export($this->artifact_xml, $this->changeset_xml, $this->artifact, $this->int_changeset_value)->at(0);
        expect($this->visitor)->export($this->artifact_xml, $this->changeset_xml, $this->artifact, $this->float_changeset_value)->at(1);

        $this->values_exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            $this->values
        );
    }
}