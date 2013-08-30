<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once dirname(__FILE__) .'/../bootstrap.php';

class Cardwall_Semantic_CardFieldsTest extends TuleapTestCase {

    public function itExportsInXMLFormat() {
        $tracker  = mock('Tracker');
        $field_1  = stub('Tracker_FormElement_Field_Text')->getId()->returns(102);
        $field_2  = stub('Tracker_FormElement_Field_Text')->getId()->returns(103);
        $semantic = new Cardwall_Semantic_CardFields($tracker);
        $semantic->setFields(array($field_1, $field_2));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_mapping = array('F13' => '102', 'F14' => '103');
        $semantic->exportToXML($root, $array_mapping);

        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportCardwallSemanticCardFields.xml');
        $this->assertEqual((string)$xml['type'], (string)$root->semantic['type']);
        $this->assertEqual((string)$xml->field[0]['REF'], (string)$root->semantic->field[0]['REF']);
        $this->assertEqual((string)$xml->field[1]['REF'], (string)$root->semantic->field[1]['REF']);
    }

}
