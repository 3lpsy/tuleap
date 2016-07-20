<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
require_once('bootstrap.php');

class Tracker_FormElement_Field_ComputedTest extends TuleapTestCase {
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;

    public function setUp() {
        parent::setUp();
        $this->user  = mock('PFUser');
        $this->dao   = mock('Tracker_FormElement_Field_ComputedDao');
        $this->field = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_Computed',
            array('getProperty', 'getDao')
        );
        stub($this->field)->getProperty('target_field_name')->returns('effort');
        stub($this->field)->getProperty('fast_compute')->returns(0);
        stub($this->field)->getDao()->returns($this->dao);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itComputesDirectValues()
    {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15)
        );

        $child_art = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($this->artifact_factory)->getInstanceFromRow()->returns($child_art);

        $artifact    = stub('Tracker_Artifact')->getId()->returns(233);
        $empty_array = array();
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact, null, $empty_array, false));
    }

    public function itReturnsNullWhenThereAreNoDataBecauseNoDataMeansNoPlotOnChart() {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsEmptyDar();

        $child_art = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($this->artifact_factory)->getInstanceFromRow()->returns($child_art);

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $empty_array = array();
        $this->assertIdentical(null, $this->field->getComputedValue($this->user, $artifact, null, $empty_array, false));
    }

    public function itUseNameForSlowComputeModeWhenNoTargetFieldNameIsDefined() {
        $computed_field = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_Computed',
            array('getProperty', 'getDao', 'getName')
        );

        stub($computed_field)->getProperty('target_field_name')->returns('');
        stub($computed_field)->getProperty('fast_compute')->returns(0);
        stub($computed_field)->getDao()->returns($this->dao);
        stub($computed_field)->getName()->returns('computed_name');

        stub($this->dao)->getFieldValues(array(233), 'computed_name')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15)
        );

        $child_art = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($this->artifact_factory)->getInstanceFromRow()->returns($child_art);

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $empty_array = array();

        $this->assertIdentical(20, $computed_field->getComputedValue($this->user, $artifact, null, $empty_array, false));
    }
}

class Tracker_FormElement_Field_Computed_HasChanges extends TuleapTestCase
{
    private $artifact;
    private $old_value;
    private $field;

    public function setUp()
    {
        $this->artifact  = mock('Tracker_Artifact');
        $this->old_value = mock('Tuleap\Tracker\Artifact\ChangesetValueComputed');

        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('getNumeric', 'getDeprecationRetriever'));
    }


    public function itDetectsChangeWhenBackToAutocompute()
    {
        stub($this->old_value)->getNumeric()->returns(1.0);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itDetectsChangeWhenBackToManualValue()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        $submitted_value = array(
            'manual_value'    => '123',
            'is_autocomputed' => false
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itDetectsChangeWhenBackToAutocomputeWhenManualValueIs0()
    {
        stub($this->old_value)->getNumeric()->returns(0.0);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $submitted_value));
    }

    public function itHasChangesWhenANewManualValueIsSet()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => 5
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesWhenANewManualValueIsEqualToOldChangesetValue()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => 7
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesIfYouAreStillInAutocomputedMode()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        $new_value = array(
            'is_autocomputed' => '1',
            'manual_value'    => ''
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itHasNotChangesWhenANewManualIsAStringAndValueIsEqualToOldChangesetValue()
    {
        stub($this->old_value)->getNumeric()->returns(7.0);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => '7'
        );

        $this->assertFalse($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itCanAdd0ToManualValueFromAutocomputed()
    {
        stub($this->old_value)->getNumeric()->returns(null);
        $new_value = array(
            'is_autocomputed' => '',
            'manual_value'    => '0'
        );

        $this->assertTrue($this->field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function itIsNotSubmitableIfItsALegacyField()
    {
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(true);

        $this->assertFalse($this->field->isSubmitable());
    }

    public function itIsSubmitableIfItsNotALegacyField()
    {
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);

        $this->assertTrue($this->field->isSubmitable());
    }

    public function itIsNotUpdatableIfItsALegacyField()
    {
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(true);

        $this->assertFalse($this->field->isUpdateable());
    }

    public function itIsUpdatableIfItsNotALegacyField()
    {
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);

        $this->assertTrue($this->field->isUpdateable());
    }
}

class Tracker_FormElement_Field_Computed_BackToAutoComputed extends TuleapTestCase
{
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;
    private $artifact;

    public function setUp()
    {
        parent::setUp();
        $this->user       = mock('PFUser');
        $this->dao        = mock('Tracker_FormElement_Field_ComputedDao');
        $this->manual_dao = mock('Tuleap\Tracker\DAO\ComputedDao');
        $this->field      = $this->getComputedField();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);

        $this->artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
    }

    private function getComputedField()
    {
        $field = partial_mock(
            'Tracker_FormElement_Field_Computed',
            array(
                'getProperty',
                'getDao',
                'getName',
                'getId',
                'getValueDao',
                'getStandardCalculationMode',
                'getFieldEmptyMessage',
                'getStopAtManualSetFieldMode'
            )
        );
        stub($field)->getProperty('target_field_name')->returns('effort');
        stub($field)->getProperty('fast_compute')->returns(0);
        stub($field)->getDao()->returns($this->dao);
        stub($field)->getValueDao()->returns($this->manual_dao);
        stub($field)->getId()->returns(23);
        stub($field)->getName()->returns('effort');
        return $field;
    }

    public function tearDown()
    {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itCallsStandardCalculWhenFieldsAreIntAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int'),
            array('id' => 750, 'type' => 'int')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenFieldsAreComputedAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenAComputedValueIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itCallsStandardCalculWhenIntFieldsAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5)
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itReturnsNullWhenNoManualValueIsSetAndNoChildrenExists()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(null);

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));

        $result = $this->field->getComputedValueWithNoStopOnManualValue($artifact);

        $this->assertIdentical(null, $result);
    }

    public function itCalculatesAutocomputedAndintFieldsEvenIfAParentIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5),
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed', 'value' => '6')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => 12));
        expect($this->field)->getStopAtManualSetFieldMode()->once();
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itReturnsComputedValuesAndIntValuesWhenBothAreOneSameChildrenLevel()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 10),
            array('id' => 751, 'type' => 'int', 'int_value' => 5),
            array('id' => 766, 'type' => 'computed', 'value' => '6'),
            array('id' => 777, 'type' => 'computed', 'value' => '6')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset()->returns(array('value' => null));
        expect($this->field)->getStandardCalculationMode()->once();

        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }
}

class Tracker_FormElement_Field_Computed_DoNoCountTwiceTest extends TuleapTestCase {
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;
    private $artifact;

    public function setUp(){
        parent::setUp();
        $this->user       = mock('PFUser');
        $this->dao        = mock('Tracker_FormElement_Field_ComputedDao');
        $this->manual_dao = mock('Tuleap\Tracker\DAO\ComputedDao');
        $this->field      = $this->getComputedField();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);

        $this->artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
    }

    private function getComputedField() {
        $field = partial_mock('Tracker_FormElement_Field_Computed',array('getProperty', 'getDao', 'getName', 'getId'));
        stub($field)->getProperty('target_field_name')->returns('effort');
        stub($field)->getProperty('fast_compute')->returns(0);
        stub($field)->getDao()->returns($this->dao);
        return $field;
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itComputesRecursively()
    {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_722 = array('id' => 722, 'type' => 'int', 'int_value' => 5);

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_722
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_722)->returns($this->getArtifact(722));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $empty_array = array();
        $this->assertEqual(25, $this->field->getComputedValue($this->user, $this->artifact, null, $empty_array, false));
    }

    public function itDoesntMakeLoopInGraph()
    {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_752 = array('id' => 752, 'type' => 'int', 'int_value' => 10);
        $row_753 = array('id' => 753, 'type' => 'int', 'int_value' => 10);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_777 = array('id' => 777, 'type' => 'computed');
        $row_233 = array('id' => 233, 'type' => 'computed');

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766,
            $row_777
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_752,
            $row_233
        );
        stub($this->dao)->getFieldValues(array(777), 'effort')->returnsDar(
            $row_753,
            $row_766
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_752)->returns($this->getArtifact(752));
        stub($this->artifact_factory)->getInstanceFromRow($row_753)->returns($this->getArtifact(753));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));
        stub($this->artifact_factory)->getInstanceFromRow($row_777)->returns($this->getArtifact(777));
        stub($this->artifact_factory)->getInstanceFromRow($row_233)->returns($this->artifact);

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $empty_array = array();
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $this->artifact, null, $empty_array, false));
    }

    public function itDoesntCountTwiceTheFinalData()
    {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_777 = array('id' => 777, 'type' => 'computed');

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766,
            $row_777
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_750
        );
        stub($this->dao)->getFieldValues(array(777), 'effort')->returnsDar(
            $row_751
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));
        stub($this->artifact_factory)->getInstanceFromRow($row_777)->returns($this->getArtifact(777));

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $empty_array = array();
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $this->artifact, null, $empty_array, false));
    }

    private function getArtifact($id) {
        $artifact = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
        stub($artifact)->getId()->returns($id);
        return $artifact;
    }
}

class Tracker_FormElement_Field_Compute_FastComputeTest extends TuleapTestCase
{
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;

    public function setUp()
    {
        parent::setUp();
        $this->user       = mock('PFUser');
        $this->dao        = mock('Tracker_FormElement_Field_ComputedDao');
        $this->manual_dao = mock('Tuleap\Tracker\DAO\ComputedDao');
        $this->field      = TestHelper::getPartialMock(
            'Tracker_FormElement_Field_Computed',
            array(
                'getProperty',
                'getDao',
                'getName',
                'getId',
                'getValueDao',
                'getDeprecationRetriever',
                'getStandardCalculationMode'
            )
        );
        stub($this->field)->getName()->returns('effort');
        stub($this->field)->getProperty('fast_compute')->returns(1);
        stub($this->field)->getDao()->returns($this->dao);
        stub($this->field)->getValueDao()->returns($this->manual_dao);
        stub($this->field)->getId()->returns(23);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown()
    {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }

    public function itComputesDirectValues()
    {
        expect($this->dao)->getComputedFieldValues()->once();
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDisplaysEmptyWhenFieldsAreAutocomputedAndNoValuesAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset($changeset, 233)->returns(array('value' => null));
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        expect($this->field)->getStandardCalculationMode()->once();
        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }

    public function itDisplaysComputedValuesWhenComputedChildrenAreSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, false)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'value' => 10),
            array('id' => 777, 'type' => 'computed', 'value' => 5)
        );

        $artifact  = stub('Tracker_Artifact')->getId()->returns(233);
        $changeset = stub($artifact)->getLastChangeset()->returns(mock('Tracker_Artifact_Changeset'));
        $changeset = stub($changeset)->getId()->returns(101);

        stub($this->manual_dao)->getManuallySetValueForChangeset($changeset, 233)->returns(array('value' => null));
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        expect($this->field)->getStandardCalculationMode()->once();
        $this->field->getComputedValueWithNoStopOnManualValue($artifact);
    }


    public function itMakesOneDbCallPerGraphDepth()
    {
        expect($this->dao)->getComputedFieldValues()->count(2);
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 752, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766),
            array('id' => 753, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDoesntMakeLoopInGraph()
    {
        expect($this->dao)->getComputedFieldValues()->count(3);
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 752, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766),
            array('id' => 753, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777),
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    /**
     * This use case highlights the case where a Release have 2 backlog elements
     * and 2 sprints. The backlog elements are also presents in the sprints backlog
     * each backlog element should be counted only once.
     */
    public function itDoesntCountTwiceTheFinalData()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233),
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233),
            array('id' => 777, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766, 777), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766),
            array('id' => 766, 'type' => 'computed', 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itStopsWhenAManualValueIsSet()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233)
        );
        stub($this->dao)->getComputedFieldValues(array(766), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'value' => 4, 'parent_id' => 766),
            array('id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766),
            array('id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(4, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itCanAddManuallySetValuesAndComputedValues()
    {
        stub($this->dao)->getComputedFieldValues(array(233), 'effort', 23, true)->returnsDar(
            array('id' => 766, 'type' => 'computed', 'parent_id' => 233, 'value' => 4.7500),
            array('id' => 777, 'type' => 'computed', 'parent_id' => null)
        );
        stub($this->dao)->getComputedFieldValues(array(777), 'effort', 23, true)->returnsDar(
            array('id' => 750, 'type' => 'float', 'float_value' => 5.2500, 'parent_id' => 777),
            array('id' => 751, 'type' => 'float', 'float_value' => 15, 'parent_id' => 777)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
        $this->assertEqual(25, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDeterminesWhenFastComputeIsUsed()
    {
        $field_not_fast_compute = partial_mock('Tracker_FormElement_Field_Computed', array('getProperty'));
        stub($field_not_fast_compute)->getProperty('fast_compute')->returns('0');
        $this->assertFalse($field_not_fast_compute->useFastCompute());

        $field_fast_compute = partial_mock('Tracker_FormElement_Field_Computed', array('getProperty'));
        stub($field_fast_compute)->getProperty('fast_compute')->returns('1');
        $this->assertTrue($field_fast_compute->useFastCompute());
    }
}

class Tracker_FormElement_Field_Computed_getSoapValueTest extends TuleapTestCase
{
    private $field;

    public function setUp()
    {
        parent::setUp();
        $id          = $tracker_id = $parent_id = $description = $use_it = $scope = $required = $notifications = $rank = '';
        $name        = 'foo';
        $label       = 'Foo Bar';
        $this->field = partial_mock('Tracker_FormElement_Field_Computed',
            array('getComputedValue', 'userCanRead', 'getDao', 'getComputedValueWithNoLabel'),
            array(
                $id,
                $tracker_id,
                $parent_id,
                $name,
                $label,
                $description,
                $use_it,
                $scope,
                $required,
                $notifications,
                $rank
            )
        );
        stub($this->field)->getDao()->returns(mock('Tracker_FormElement_Field_ComputedDao'));

        $this->artifact  = anArtifact()->build();
        $this->user      = aUser()->build();
        $this->changeset = mock('Tracker_Artifact_Changeset');
        stub($this->changeset)->getArtifact()->returns($this->artifact);
    }

    public function itReturnsNullIfUserCannotAccessField()
    {
        expect($this->field)->userCanRead($this->user)->once();
        stub($this->field)->userCanRead()->returns(false);
        $this->assertIdentical($this->field->getSoapValue($this->user, $this->changeset), null);
    }

    public function itUsedTheComputedFieldValue()
    {
        stub($this->field)->userCanRead()->returns(true);

        stub($this->field)->getComputedValueWithNoLabel()->returns(9.0);
        expect($GLOBALS['Language'])->getText()->never();

        $this->assertIdentical(
            $this->field->getSoapValue($this->user, $this->changeset),
            array(
                'field_name'  => 'foo',
                'field_label' => 'Foo Bar',
                'field_value' => array('value' => '9')
            )
        );
    }

    public function itAcceptsAnEmptyComputedFieldValue()
    {
        stub($this->field)->userCanRead()->returns(true);

        $empty_computed_field = 'empty_computed_field';
        stub($GLOBALS['Language'])->getText()->returns($empty_computed_field);
        stub($this->field)->getComputedValueWithNoLabel()->returns($empty_computed_field);

        $this->assertIdentical(
            $this->field->getSoapValue($this->user, $this->changeset),
            array(
                'field_name'  => 'foo',
                'field_label' => 'Foo Bar',
                'field_value' => array('value' => $empty_computed_field)
            )
        );
    }

    public function itReturnsManualValueIfExisting()
    {
        stub($this->field)->userCanRead()->returns(true);

        expect($this->field)->getComputedValue($this->user, $this->artifact)->never();
        expect($GLOBALS['Language'])->getText()->never();
        $expected_value  = 20;
        stub($this->field)->getComputedValueWithNoLabel()->returns($expected_value);

        $this->assertIdentical(
            $this->field->getSoapValue($this->user, $this->changeset),
            array(
                'field_name'  => 'foo',
                'field_label' => 'Foo Bar',
                'field_value' => array('value' => (string) $expected_value)
            )
        );
    }
}

class Tracker_FormElement_Field_Computed_FieldValidationTest extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('isRequired', 'userCanUpdate'));
    }

    public function itExpectsAnArray()
    {
        $this->assertFalse($this->field->validateValue('String'));
        $this->assertFalse($this->field->validateValue(1));
        $this->assertFalse($this->field->validateValue(1.1));
        $this->assertFalse($this->field->validateValue(true));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true)));
    }

    public function itExpectsAtLeastAValueOrAnAutocomputedInformation()
    {
        $this->assertFalse($this->field->validateValue(array()));
        $this->assertFalse($this->field->validateValue(array('v1' => 1)));
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED)));
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL)));
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED
        )));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1)));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true)));
    }

    public function itExpectsAFloatOrAIntAsManualValue()
    {
        $this->assertFalse($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 'String')));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1.1)));
        $this->assertTrue($this->field->validateValue(array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 0)));
    }

    public function itCanNotAcceptAManualValueWhenAutocomputedIsEnabled()
    {
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        )));
        $this->assertTrue($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => 1,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        )));
        $this->assertFalse($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        )));
        $this->assertTrue($this->field->validateValue(array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        )));
    }

    public function itIsValidWhenTheFieldIsRequiredAndIsAutocomputed()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenTheFieldIsNotRequiredAndIsAutocomputed()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '',
            'is_autocomputed' => true
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenTheFieldIsRequiredAndHasAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => '11'
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsNotValidWhenTheFieldIsRequiredAndDoesntHaveAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(true);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => ''
        );

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsNotValidWhenTheFieldIsNotRequiredAndDoesntHaveAManualValue()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
            'manual_value'    => ''
        );

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }

    public function itIsValidWhenNoValuesAreSubmitted()
    {
        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->field)->isRequired()->returns(false);
        stub($this->field)->userCanUpdate()->returns(true);
        $submitted_value = array(
        );

        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($artifact, $submitted_value)
        );
    }
}

class Tracker_FormElement_Field_Computed_RESTValueTest extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $field;

    public function setUp()
    {
        parent::setUp();
        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('getDeprecationRetriever'));

        $deprecation_retriever = mock('Tuleap\Tracker\Deprecation\DeprecationRetriever');
        stub($this->field)->getDeprecationRetriever()->returns($deprecation_retriever);
        stub($deprecation_retriever)->isALegacyField()->returns(false);
    }

    public function itReturnsValueWhenCorrectlyFormatted()
    {
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => true
        );
        $this->assertClone($value, $this->field->getFieldDataFromRESTValue($value));
    }

    public function itRejectsDataWhenAutocomputedIsDisabledAndNoManualValueIsProvided()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false
        );
        $this->field->getFieldDataFromRESTValue($value);
    }

    public function itRejectsDataWhenAutocomputedIsDisabledAndManualValueIsNull()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => false,
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => null
        );
        $this->field->getFieldDataFromRESTValue($value);
    }

    public function itRejectsDataWhenValueIsSet()
    {
        $this->expectException('Tracker_FormElement_InvalidFieldValueException');
        $value = array(
            'value' => 1
        );
        $this->field->getFieldDataFromRESTValue($value);
    }
}
