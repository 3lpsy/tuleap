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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group MilestonesTest
 */
class MilestonesContentTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSContent() {
        $response = $this->getResponse($this->client->options('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETContent() {
        $response = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));

        $content_items = $response->json();

        $this->assertCount(4, $content_items);

        $first_content_item = $content_items[0];
        $this->assertArrayHasKey('id', $first_content_item);
        $this->assertEquals($first_content_item['label'], "First epic");
        $this->assertEquals($first_content_item['status'], "Open");
        $this->assertEquals($first_content_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_1_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $second_content_item = $content_items[1];
        $this->assertArrayHasKey('id', $second_content_item);
        $this->assertEquals($second_content_item['label'], "Second epic");
        $this->assertEquals($second_content_item['status'], "Closed");
        $this->assertEquals($second_content_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_2_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_2_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $third_content_item = $content_items[2];
        $this->assertArrayHasKey('id', $third_content_item);
        $this->assertEquals($third_content_item['label'], "Third epic");
        $this->assertEquals($third_content_item['status'], "Closed");
        $this->assertEquals($third_content_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_3_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_3_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $fourth_content_item = $content_items[3];
        $this->assertArrayHasKey('id', $fourth_content_item);
        $this->assertEquals($fourth_content_item['label'], "Fourth epic");
        $this->assertEquals($fourth_content_item['status'], "Open");
        $this->assertEquals($fourth_content_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTContent() {
        $response_put = $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID.','.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(2, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "First epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_1_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Fourth epic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));
    }

    public function testPUTContentWithSameValueAsPreviouslyReturns200() {
        $response_put = $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID.','.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);
        $this->assertEquals($response_put->json(), array());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTContentWithoutPermission() {
        $response_put = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID.','.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 403);
        $this->assertEquals($response_put->json(), array());
    }

    public function testPUTContentOnlyOneElement() {
        $response_put = $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content'));
        $backlog_items = $response_get->json();

        $this->assertCount(1, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Fourth epic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'uri' => 'artifacts/'.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID, 'tracker' => array('id' => REST_TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.REST_TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $this->getResponse($this->client->put('milestones/'.REST_TestDataBuilder::RELEASE_ARTIFACT_ID.'/content', null, '['.REST_TestDataBuilder::EPIC_1_ARTIFACT_ID.','.REST_TestDataBuilder::EPIC_4_ARTIFACT_ID.']'));
    }
}
