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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_BasicTest extends TuleapTestCase {

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();
        $project        = stub('Project')->getId()->returns(1478);
        $this->tracker  = aTracker()->withId(888)->withProject($project)->build();
        $this->user     = aUser()->withId(111)->build();
        $formelement_factory = mock('Tracker_FormElementFactory');
        stub($formelement_factory)->getUsedFieldsForSoap($this->tracker)->returns(array());
        $this->builder  = new Tracker_REST_Artifact_ArtifactRepresentationBuilder($formelement_factory);

        $this->changeset = mock('Tracker_Artifact_Changeset');

        $this->artifact = anArtifact()
            ->withId(12)
            ->withTracker($this->tracker)
            ->withSubmittedBy(777)
            ->withSubmittedOn(6546546554)
            ->withChangesets(array($this->changeset))
            ->build();
    }

    public function itBuildsTheBasicInfo() {
        $representation = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);

        $this->assertEqual($representation->id, 12);
        $this->assertEqual($representation->uri, Tuleap\Tracker\REST\Artifact\ArtifactRepresentation::ROUTE . '/' . 12);
        $this->assertEqual($representation->tracker->id, 888);
        $this->assertEqual($representation->tracker->uri, Tuleap\Tracker\REST\TrackerRepresentation::ROUTE . '/' . 888);
        $this->assertEqual($representation->project->id, 1478);
        $this->assertEqual($representation->project->uri, 'projects/1478');
        $this->assertEqual($representation->submitted_by, 777);
        $this->assertEqual($representation->submitted_on, '2177-06-14T06:09:14+01:00');
        $this->assertEqual($representation->html_url, '/plugins/tracker/?aid=12');
        $this->assertEqual($representation->changesets_uri, Tuleap\Tracker\REST\Artifact\ArtifactRepresentation::ROUTE . '/' . 12 . '/' . Tuleap\Tracker\REST\ChangesetRepresentation::ROUTE);
    }
}

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_FieldsTest extends TuleapTestCase {

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();
        $project        = stub('Project')->getId()->returns(1478);
        $this->tracker  = aTracker()->withId(888)->withProject($project)->build();
        $this->user     = aUser()->withId(111)->build();
        $this->changeset = mock('Tracker_Artifact_Changeset');
        $this->artifact = anArtifact()
            ->withTracker($this->tracker)
            ->withChangesets(array($this->changeset))
            ->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder($this->formelement_factory);
    }

    public function itGetsTheFieldsFromTheFactory() {
        expect($this->formelement_factory)->getUsedFieldsForSoap($this->tracker)->once();
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array());
        $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);
    }

    public function itHasNoValuesWhenThereAreNoFields() {
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array());
        $representation = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);

        $this->assertEqual($representation->values, array());
    }

    public function itDoesntIncludeFieldsTheUserCannotView() {
        $field1 = aMockField()->withId(1)->build();
        $field2 = aMockField()->withId(2)->build();
        $field3 = aMockField()->withId(3)->build();
        stub($field1)->userCanRead($this->user)->returns(false);
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field3)->userCanRead($this->user)->returns(false);

        expect($field1)->getRESTValue($this->user, $this->changeset)->never();
        expect($field2)->getRESTValue($this->user, $this->changeset)->once();
        expect($field3)->getRESTValue($this->user, $this->changeset)->never();

        stub($this->formelement_factory)->getUsedFieldsForSoap($this->tracker)->returns(array($field1, $field2, $field3));

        $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);
    }

    public function itReturnsValuesOnlyForFieldsWithValues() {
        $field1 = aMockField()->withId(1)->build();
        $field2 = aMockField()->withId(2)->build();
        $field3 = aMockField()->withId(3)->build();
        stub($field2)->userCanRead($this->user)->returns(true);
        stub($field2)->getRESTValue()->returns('whatever');

        stub($this->formelement_factory)->getUsedFieldsForSoap($this->tracker)->returns(array($field1, $field2, $field3));

        $representation = $this->builder->getArtifactRepresentationWithFieldValues($this->user, $this->artifact);

        $this->assertEqual($representation->values, array('whatever'));
    }
}

class Tracker_REST_Artifact_ArtifactRepresentationBuilder_ChangesetsTest extends TuleapTestCase {
    /** @var Tracker_Artifact */
    private $artifact;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();

        $this->user     = aUser()->withId(111)->build();
        $this->artifact = anArtifact()
            ->withTracker(aMockTracker()->build())
            ->build();
        $this->builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(mock('Tracker_FormElementFactory'));
    }

    public function itReturnsEmptyArrayWhenNoChanges() {
        $this->artifact->setChangesets(array());

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, 0, 10)->toArray(),
            array()
        );
    }

    public function itBuildsHistoryOutOfChangeset() {
        $changeset1 = mock('Tracker_Artifact_Changeset');
        expect($changeset1)->getRESTValue($this->user)->once();

        $this->artifact->setChangesets(array($changeset1));

        $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, 0, 10)->toArray();
    }

    public function itDoesntExportEmptyChanges() {
        $changeset1 = mock('Tracker_Artifact_Changeset');
        $changeset2 = mock('Tracker_Artifact_Changeset');

        stub($changeset1)->getRESTValue()->returns(null);
        stub($changeset2)->getRESTValue()->returns('whatever');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, 0, 10)->toArray(),
            array('whatever')
        );
    }

    public function itPaginatesResults() {
        $changeset1 = mock('Tracker_Artifact_Changeset');
        $changeset2 = mock('Tracker_Artifact_Changeset');

        stub($changeset1)->getRESTValue()->returns('result 1');
        stub($changeset2)->getRESTValue()->returns('result 2');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, 1, 10)->toArray(),
            array('result 2')
        );
    }

    public function itReturnsTheTotalCountOfResults() {
        $changeset1 = mock('Tracker_Artifact_Changeset');
        $changeset2 = mock('Tracker_Artifact_Changeset');

        stub($changeset1)->getRESTValue()->returns('result 1');
        stub($changeset2)->getRESTValue()->returns('result 2');

        $this->artifact->setChangesets(array($changeset1, $changeset2));

        $this->assertIdentical(
            $this->builder->getArtifactChangesetsRepresentation($this->user, $this->artifact, 1, 10)->totalCount(),
            2
        );
    }
}
