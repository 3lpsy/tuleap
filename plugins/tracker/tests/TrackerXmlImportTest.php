<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

require_once 'bootstrap.php';

class TrackerXmlImportTestInstance extends TrackerXmlImport {

    public function getInstanceFromXML($xml, $groupId, $name, $description, $itemname) {
        return parent::getInstanceFromXML($xml, $groupId, $name, $description, $itemname);
    }

    public function getAllXmlTrackers($xml) {
        return parent::getAllXmlTrackers($xml);
    }

    public function buildTrackersHierarchy(array $hierarchy, SimpleXMLElement $xml_tracker, array $mapper) {
        return parent::buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);
    }

    public function setMappingInjector($injector) {
        $this->injector = $injector;
    }
}

class TrackerXmlImportTest extends TuleapTestCase {

    private $tracker_factory;

    private $group_id = 145;

    private $tracker_xml_importer;

    public function setUp() {
        parent::setUp();

        $this->xml_input =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <empty_section />
              <trackers>
                  <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                  </tracker>
                  <tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>
                  <tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                  </tracker>
              </trackers>
              <cardwall/>
              <agiledashboard/>
            </project>');

        $this->group_id = 145;

        $this->xml_tracker1 = new SimpleXMLElement(
                 '<tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                  </tracker>'
        );

        $this->xml_tracker2 = new SimpleXMLElement(
                 '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>'
        );

        $this->xml_tracker3 = new SimpleXMLElement(
                 '<tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                  </tracker>'
        );

        $this->xml_trackers_list = array("T101" => $this->xml_tracker1, "T102" => $this->xml_tracker2, "T103" => $this->xml_tracker3);
        $this->mapping = array(
            "T101" => 444,
            "T102" => 555,
            "T103" => 666
        );

        $this->tracker1 = aTracker()->withId(444)->build();
        $this->tracker2 = aTracker()->withId(555)->build();
        $this->tracker3 = aTracker()->withId(666)->build();

        $this->tracker_factory = mock('TrackerFactory');

        $this->event_manager = mock('EventManager');

        $this->hierarchy_dao = stub('Tracker_Hierarchy_Dao')->updateChildren()->returns(true);

        $this->tracker_xml_importer = partial_mock(
            'TrackerXmlImportTestInstance',
            array(
                'createFromXML'
            ),
            array(
                $this->tracker_factory,
                $this->event_manager,
                $this->hierarchy_dao,
                mock('Tracker_CannedResponseFactory'),
                mock('Tracker_FormElementFactory'),
                mock('Tracker_SemanticFactory'),
                mock('Tracker_RuleFactory'),
                mock('Tracker_ReportFactory'),
                mock('WorkflowFactory'),
                mock('XML_RNGValidator'),
                mock('Tracker_Workflow_Trigger_RulesManager'),
            )
        );

        $GLOBALS['Response'] = new MockResponse();
    }

    public function tearDown() {
        parent::tearDown();
        unset($GLOBALS['Response']);
    }

    public function itReturnsEachSimpleXmlTrackerFromTheXmlInput() {
        $trackers_result = $this->tracker_xml_importer->getAllXmlTrackers($this->xml_input);
        $diff = array_diff($trackers_result, $this->xml_trackers_list);

        $this->assertEqual(count($trackers_result), 3);
        $this->assertTrue(empty($diff));
    }

    public function itCreatesAllTrackersAndStoresTrackersHierarchy() {
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker1, $this->group_id, 'name10', 'desc12', 'item11')->returns($this->tracker1);
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker2, $this->group_id, 'name20', 'desc22', 'item21')->returns($this->tracker2);
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker3, $this->group_id, 'name30', 'desc32', 'item31')->returns($this->tracker3);

        expect($this->tracker_xml_importer)->createFromXML()->count(3);
        expect($this->hierarchy_dao)->updateChildren(2);

        $result = $this->tracker_xml_importer->import($this->group_id, $this->xml_input);

        $this->assertEqual($result, $this->mapping);
    }

    public function itRaisesAnExceptionIfATrackerCannotBeCreatedAndDoesNotContinue() {
        stub($this->tracker_xml_importer)->createFromXML()->returns(null);

        $this->expectException();
        expect($this->tracker_xml_importer)->createFromXML()->count(1);
        $this->tracker_xml_importer->import($this->group_id, $this->xml_input);
    }

    public function itThrowsAnEventIfAllTrackersAreCreated() {
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker1, $this->group_id, 'name10', 'desc12', 'item11')->returns($this->tracker1);
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker2, $this->group_id, 'name20', 'desc22', 'item21')->returns($this->tracker2);
        stub($this->tracker_xml_importer)->createFromXML($this->xml_tracker3, $this->group_id, 'name30', 'desc32', 'item31')->returns($this->tracker3);

        expect($this->event_manager)->processEvent(
            Event::IMPORT_XML_PROJECT_TRACKER_DONE,
            array(
                'project_id' => $this->group_id,
                'xml_content' => $this->xml_input,
                'mapping' => $this->mapping
            )
        )->once();

        expect($this->tracker_xml_importer)->createFromXML()->count(3);
        $this->tracker_xml_importer->import($this->group_id, $this->xml_input);
    }

    public function itBuildsTrackersHierarchy() {
        $hierarchy = array();
        $expected_hierarchy = array(444 => array(555));
        $mapper = array("T101" => 444, "T102" => 555);
        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $this->xml_tracker2, $mapper);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertIdentical($hierarchy, $expected_hierarchy);
    }

    public function itAddsTrackersHierarchyOnExistingHierarchy() {
        $hierarchy          = array(444 => array(555));
        $expected_hierarchy = array(444 => array(555, 666));
        $mapper             = array("T101" => 444, "T103" => 666);
        $xml_tracker        = new SimpleXMLElement(
                 '<tracker id="T103" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $hierarchy = $this->tracker_xml_importer->buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);

        $this->assertTrue(! empty($hierarchy));
        $this->assertNotNull($hierarchy[444]);
        $this->assertIdentical($expected_hierarchy, $hierarchy);
    }
}

class TrackerXmlImport_InstanceTest extends TuleapTestCase {

    private $tracker_xml_importer;
    private $xml_security;

    public function setUp() {
        parent::setUp();

        $tracker_factory = partial_mock('TrackerFactory', array());

        $this->tracker_xml_importer = new TrackerXmlImportTestInstance(
            $tracker_factory,
            mock('EventManager'),
            mock('Tracker_Hierarchy_Dao'),
            mock('Tracker_CannedResponseFactory'),
            mock('Tracker_FormElementFactory'),
            mock('Tracker_SemanticFactory'),
            mock('Tracker_RuleFactory'),
            mock('Tracker_ReportFactory'),
            mock('WorkflowFactory'),
            mock('XML_RNGValidator'),
            mock('Tracker_Workflow_Trigger_RulesManager')
        );

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown() {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testImport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/TestTracker-1.xml');
        $tracker = $this->tracker_xml_importer->getInstanceFromXML($xml, 0, '', '', '');

        //testing general properties
        $this->assertEqual($tracker->submit_instructions, 'some submit instructions');
        $this->assertEqual($tracker->browse_instructions, 'and some for browsing');

        $this->assertEqual($tracker->getColor(), 'inca_gray');

        //testing default values
        $this->assertEqual($tracker->allow_copy, 0);
        $this->assertEqual($tracker->instantiate_for_new_projects, 1);
        $this->assertEqual($tracker->log_priority_changes, 0);
        $this->assertEqual($tracker->stop_notification, 0);
    }
}

class TrackerFactoryInstanceFromXMLTest extends TuleapTestCase {

    public function testGetInstanceFromXmlGeneratesRulesFromDependencies() {

        $data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tracker />
XML;
        $xml = new SimpleXMLElement($data);
        $xml->addChild('cannedResponses');
        $xml->addChild('formElements');

        $groupId     = 15;
        $name        = 'the tracker';
        $description = 'tracks stuff';
        $itemname    = 'the item';

        $rule_factory = mock('Tracker_RuleFactory');
        $tracker      = mock('Tracker');

        $tracker_xml_importer = new TrackerXmlImportTestInstance(
            stub('TrackerFactory')->getInstanceFromRow()->returns($tracker),
            mock('EventManager'),
            mock('Tracker_Hierarchy_Dao'),
            mock('Tracker_CannedResponseFactory'),
            mock('Tracker_FormElementFactory'),
            mock('Tracker_SemanticFactory'),
            $rule_factory,
            mock('Tracker_ReportFactory'),
            mock('WorkflowFactory'),
            mock('XML_RNGValidator'),
            mock('Tracker_Workflow_Trigger_RulesManager')
        );

        //create data passed
        $dependencies = $xml->addChild('dependencies');
        $rule = $dependencies->addChild('rule');
        $rule->addChild('source_field')->addAttribute('REF', 'F1');
        $rule->addChild('target_field')->addAttribute('REF', 'F2');
        $rule->addChild('source_value')->addAttribute('REF', 'F3');
        $rule->addChild('target_value')->addAttribute('REF', 'F4');

        //create data expected
        $expected_xml = new SimpleXMLElement($data);
        $expected_rules = $expected_xml->addChild('rules');
        $list_rules = $expected_rules->addChild('list_rules');
        $expected_rule = $list_rules->addChild('rule');
        $expected_rule->addChild('source_field')->addAttribute('REF', 'F1');
        $expected_rule->addChild('target_field')->addAttribute('REF', 'F2');
        $expected_rule->addChild('source_value')->addAttribute('REF', 'F3');
        $expected_rule->addChild('target_value')->addAttribute('REF', 'F4');

        //this is where we check the data has been correctly transformed
        stub($rule_factory)->getInstanceFromXML($expected_rules, array(), $tracker)->once();

        $tracker_xml_importer->getInstanceFromXML($xml,$groupId, $name, $description, $itemname);
    }

}

class Tracker_FormElementFactoryForXMLTests extends Tracker_FormElementFactory {
    private $mapping = array();
    public function __construct($mapping) {
        $this->mapping = $mapping;
    }

    public function getInstanceFromXML($tracker, $elem, &$xmlMapping) {
        $xmlMapping = $this->mapping;
    }
}

class TrackerXmlImport_TriggersTest extends TuleapTestCase {

    private $xml_input;
    private $group_id = 145;
    private $tracker_factory;
    private $event_manager;
    private $hierarchy_dao;
    private $tracker_xml_importer;
    private $trigger_rulesmanager;
    private $xmlFieldMapping;

    public function setUp() {
        parent::setUp();

        $this->xml_input = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project>
                <empty_section />
                <trackers>
                    <tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                        <name>t10</name>
                        <item_name>t11</item_name>
                        <description>t12</description>
                        <formElements>
                            <formElement type="sb" ID="F1685" rank="4" required="1">
                                <name>status</name>
                                <label><![CDATA[Status]]></label>
                                <bind type="static" is_rank_alpha="0">
                                    <items>
                                        <item ID="V2059" label="To be done" is_hidden="0"/>
                                        <item ID="V2060" label="On going" is_hidden="0"/>
                                        <item ID="V2061" label="Done" is_hidden="0"/>
                                        <item ID="V2062" label="Canceled" is_hidden="0"/>
                                        <item ID="V2063" label="Functional review" is_hidden="0"/>
                                        <item ID="V2064" label="Code review" is_hidden="0"/>
                                    </items>
                                    <default_values>
                                        <value REF="V2059"/>
                                    </default_values>
                                </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                    <tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                        <name>t20</name>
                        <item_name>t21</item_name>
                        <description>t22</description>
                        <formElements>
                            <formElement type="sb" ID="F1741" rank="0" required="1">
                              <name>status</name>
                              <label><![CDATA[Status]]></label>
                              <bind type="static" is_rank_alpha="0">
                                <items>
                                  <item ID="V2116" label="To be done" is_hidden="0"/>
                                  <item ID="V2117" label="On going" is_hidden="0"/>
                                  <item ID="V2118" label="Done" is_hidden="0"/>
                                  <item ID="V2119" label="Canceled" is_hidden="0"/>
                                </items>
                                <decorators>
                                  <decorator REF="V2117" r="102" g="102" b="0"/>
                                </decorators>
                                <default_values>
                                  <value REF="V2116"/>
                                </default_values>
                              </bind>
                            </formElement>
                        </formElements>
                    </tracker>
                    <triggers>
                        <trigger_rule>
                          <triggers>
                            <trigger>
                              <field_id REF="F1685"/>
                              <field_value_id REF="V2060"/>
                            </trigger>
                          </triggers>
                          <condition>at_least_one</condition>
                          <target>
                            <field_id REF="F1741"/>
                            <field_value_id REF="V2117"/>
                          </target>
                        </trigger_rule>
                        <trigger_rule>
                          <triggers>
                            <trigger>
                              <field_id REF="F1685"/>
                              <field_value_id REF="V2061"/>
                            </trigger>
                          </triggers>
                          <condition>all_of</condition>
                          <target>
                            <field_id REF="F1741"/>
                            <field_value_id REF="V2118"/>
                          </target>
                        </trigger_rule>
                    </triggers>
                </trackers>
                <cardwall/>
                <agiledashboard/>
            </project>'
        );

        $this->triggers = new SimpleXMLElement('<triggers>
                            <trigger_rule>
                              <triggers>
                                <trigger>
                                  <field_id REF="F1685"/>
                                  <field_value_id REF="V2060"/>
                                </trigger>
                              </triggers>
                              <condition>at_least_one</condition>
                              <target>
                                <field_id REF="F1741"/>
                                <field_value_id REF="V2117"/>
                              </target>
                            </trigger_rule>
                            <trigger_rule>
                              <triggers>
                                <trigger>
                                  <field_id REF="F1685"/>
                                  <field_value_id REF="V2061"/>
                                </trigger>
                              </triggers>
                              <condition>all_of</condition>
                              <target>
                                <field_id REF="F1741"/>
                                <field_value_id REF="V2118"/>
                              </target>
                            </trigger_rule>
                        </triggers>');

        $this->tracker1 = aMockTracker()->withId(444)->build();
        stub($this->tracker1)->testImport()->returns(true);

        $this->tracker2 = aMockTracker()->withId(555)->build();
        stub($this->tracker2)->testImport()->returns(true);

        $this->tracker_factory = mock('TrackerFactory');
        stub($this->tracker_factory)->validMandatoryInfoOnCreate()->returns(true);
        stub($this->tracker_factory)->getInstanceFromRow()->returnsAt(0, $this->tracker1);
        stub($this->tracker_factory)->getInstanceFromRow()->returnsAt(1, $this->tracker2);
        stub($this->tracker_factory)->saveObject()->returnsAt(0, 444);
        stub($this->tracker_factory)->saveObject()->returnsAt(1, 555);

        $this->event_manager = mock('EventManager');

        $this->hierarchy_dao = stub('Tracker_Hierarchy_Dao')->updateChildren()->returns(true);

        $this->xmlFieldMapping = array(
            'F1685' => '',
            'F1741' => '',
            'V2060' => '',
            'V2061' => '',
            'V2117' => '',
            'V2118' => '',
        );

        $this->trigger_rulesmanager = mock('Tracker_Workflow_Trigger_RulesManager');

        $this->tracker_xml_importer = new TrackerXmlImport(
            $this->tracker_factory,
            $this->event_manager,
            $this->hierarchy_dao,
            mock('Tracker_CannedResponseFactory'),
            new Tracker_FormElementFactoryForXMLTests($this->xmlFieldMapping),
            mock('Tracker_SemanticFactory'),
            mock('Tracker_RuleFactory'),
            mock('Tracker_ReportFactory'),
            mock('WorkflowFactory'),
            mock('XML_RNGValidator'),
            $this->trigger_rulesmanager
        );
     }

     public function itDelegatesToRulesManager() {
         expect($this->trigger_rulesmanager)->createFromXML($this->triggers, $this->xmlFieldMapping)->once();
         $this->tracker_xml_importer->import($this->group_id, $this->xml_input);
     }
}
