<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once('bootstrap.php');


class Tracker_FormElement_Field_List_Bind_JsonFormatTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->bind = partial_mock('Tracker_FormElement_Field_List_Bind4Tests', array('getAllValues'));

        $this->v1 = mock('Tracker_FormElement_Field_List_BindValue');
        $this->v2 = mock('Tracker_FormElement_Field_List_BindValue');
    }

    public function itDelegatesFormattingToValues() {
        expect($this->v1)->fetchFormattedForJson()->once();
        expect($this->v2)->fetchFormattedForJson()->once();

        stub($this->bind)->getAllValues()->returns(array($this->v1, $this->v2));

        $this->bind->fetchFormattedForJson();
    }

    public function itFormatsValuesForJson() {
        stub($this->v1)->fetchFormattedForJson()->returns('whatever 1');
        stub($this->v1)->getId()->returns(700);

        stub($this->v2)->fetchFormattedForJson()->returns('whatever 2');
        stub($this->v2)->getId()->returns(300);

        stub($this->bind)->getAllValues()->returns(array($this->v1, $this->v2));

        $this->assertIdentical(
            $this->bind->fetchFormattedForJson(),
            array(
                700 => 'whatever 1',
                300 => 'whatever 2',
            )
        );
    }

    public function itSendsAnEmptyArrayInJSONFormatWhenNoValues() {
        stub($this->bind)->getAllValues()->returns(array());
        $this->assertIdentical(
            $this->bind->fetchFormattedForJson(),
            array()
        );
    }
}

class Tracker_FormElement_Field_List_Bind4Tests extends Tracker_FormElement_Field_List_Bind {
    protected function getSoapBindingList() {

    }

    public function exportToXml(SimpleXMLElement $root, &$xmlMapping, $fieldID) {

    }

    public function fetchAdminEditForm() {

    }

    public function fetchRawValue($value) {

    }

    public function fetchRawValueFromChangeset($changeset) {

    }

    public function fixOriginalValueIds(array $value_mapping) {

    }

    public function formatChangesetValue($value) {

    }

    public function formatChangesetValueForCSV($value) {

    }

    public function formatChangesetValueWithoutLink($value) {

    }

    public function formatCriteriaValue($value_id) {

    }

    public function formatMailCriteriaValue($value_id) {

    }

    public function getAllValues() {

    }

    public function getBindValues($bindvalue_ids = null) {

    }

    public function getBindtableSqlFragment() {

    }

    public function getChangesetValues($changeset_id) {

    }

    public function getCriteriaFrom($criteria_value) {

    }

    public function getCriteriaWhere($criteria) {

    }

    public function getDao() {

    }

    public function getFieldData($soap_value, $is_multiple) {

    }

    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value) {

    }

    public function getQueryFrom($changesetvalue_table = ''){

    }

    public function getQueryGroupby() {

    }

    public function getQueryOrderby() {

    }

    public function getQuerySelect() {

    }

    public function getQuerySelectAggregate($functions) {

    }

    public function getValue($value_id) {

    }

    public function getValueDao() {

    }

    public function getValueFromRow($row) {

    }

    public static function fetchAdminCreateForm($field) {

    }

    public function getType() {

    }

}

?>
