<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
Mock::generatePartial('Tracker_ReportFactory',
                      'Tracker_ReportFactoryTestVersion',
                      array('getCriteriaFactory', 'getRendererFactory', 'getCommentDao'));
Mock::generate('Tracker_Report_CriteriaFactory');
Mock::generate('Tracker_Report_RendererFactory');

class Tracker_ReportFactoryTest extends TuleapTestCase {

    /** @var XML_Security */
    protected $xml_security;

    public function setUp() {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();

        $this->additional_criteria_factory = new Tuleap\Tracker\Report\AdditionalCriteria\AdditionalCriteriaFactory();
        $this->comment_dao                 = mock('Tuleap\Tracker\Report\AdditionalCriteria\CommentDao');
    }

    public function tearDown() {
        $this->xml_security->disableExternalLoadOfEntities();
        foreach ($_SESSION as $key => $nop) {
            unset($_SESSION[$key]);
        }

        parent::tearDown();
    }

    //testing CannedResponse import
    public function testImport() {
        $repo = new Tracker_ReportFactoryTestVersion();
        $crit = new MockTracker_Report_CriteriaFactory();
        $repo->setReturnReference('getCriteriaFactory', $crit);
        $rend = new MockTracker_Report_RendererFactory();
        $repo->setReturnReference('getRendererFactory', $rend);

        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $reports = array();
        foreach ($xml->reports->report as $report) {
            $empty_array = array();
            $reports[] = $repo->getInstanceFromXML($report, $empty_array, 0);
        }

        //general settings
        $this->assertEqual($reports[0]->name, 'Default');
        $this->assertEqual($reports[0]->description, 'The system default artifact report');
        $this->assertEqual($reports[0]->is_default, 0);

        $additional_critera = $reports[0]->getAdditionalCriteriaForXmlImport();
        $this->assertIsA($additional_critera[0], Tracker_Report_AdditionalCriterion::class);
        $this->assertEqual($additional_critera[0]->getKey(), 'comment');
        $this->assertEqual($additional_critera[0]->getValue(), 'test');

        //default values
        $this->assertEqual($reports[0]->is_query_displayed, 1);
        $this->assertEqual($reports[0]->is_in_expert_mode, 0);
    }
}

class Tracker_ReportFactory_SOAPExportTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->tracker_id = 12;
        $this->tracker = aTracker()->withId($this->tracker_id)->build();
        $this->user_id    = 32;
        $this->user = aUser()->withId($this->user_id)->build();

        $this->report_factory = partial_mock('Tracker_ReportFactory', array('getReportsByTrackerId'));
    }

    public function itTransformTheReportIntoASoapResponse() {
        $report = mock('Tracker_Report');
        expect($report)->exportToSoap()->once();

        stub($this->report_factory)->getReportsByTrackerId($this->tracker_id, $this->user_id)->returns(
            array(
                100 => $report
            )
        );
        $this->report_factory->exportToSoap($this->tracker, $this->user);
    }

    public function itReturnsTheSOAPVersionOfTheReport() {
        $soap_of_one_report = array('id' => 100);
        stub($this->report_factory)->getReportsByTrackerId()->returns(
            array(
                100 => stub('Tracker_Report')->exportToSoap()->returns($soap_of_one_report)
            )
        );
        $soap_response = $this->report_factory->exportToSoap($this->tracker, $this->user);
        $this->assertEqual($soap_response, array($soap_of_one_report));
    }
}
?>
