<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_ChangesetValue_String_RESTTest extends TuleapTestCase {

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function itReturnsTheRESTValue() {
        $field = stub('Tracker_FormElement_Field_String')->getName()->returns('field_string');
        $user  = aUser()->withId(101)->build();

        $changeset = new Tracker_Artifact_ChangesetValue_String(111, $field, true, 'myxedemic enthymematic', 'text');
        $representation = $changeset->getRESTValue($user, $changeset);

        $this->assertEqual($representation->value, 'myxedemic enthymematic');
    }
}