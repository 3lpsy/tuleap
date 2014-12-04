<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * @group BacklogItemsTest
 */
class BacklogItemsTest extends RestBase {

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    private $backlog_items_datas = array(
        array('I want to' => 'build a new interface', 'Status' => 'To be done', 'Content' => array()),
        array('I want to' => 'finish the story', 'Status' => 'To be done', 'Content' => array(
            array('Summary' => 'Implement the feature', 'Status'=> 'Done'),
            array('Summary' => 'Write tests', 'Status' => 'On going')
        )),
    );

    private $stories_ids = array();

    public function setUp() {
        parent::setUp();
        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            TestDataBuilder::TEST_USER_1_NAME
        );

        $this->createStoriesAndTasks();
    }

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSChildren() {
        $response = $this->getResponse($this->client->options('backlog_items/'.$this->stories_ids[0].'/children'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETChildren() {
        $response      = $this->getResponse($this->client->get('backlog_items/'.$this->stories_ids[0].'/children'));
        $backlog_items = $response->json();
        $this->assertCount(0, $backlog_items);

        $response      = $this->getResponse($this->client->get('backlog_items/'.$this->stories_ids[1].'/children'));
        $backlog_items = $response->json();
        $this->assertCount(2, $backlog_items);

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPATCHChildren() {
        $uri = 'backlog_items/'.$this->stories_ids[1].'/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        // invert order of the two tasks
        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($second_id),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        // assert that the two tasks are in the order
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Write tests");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        // re-invert order of the two tasks
        $response = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($first_id),
                'direction'   => 'after',
                'compared_to' => $second_id
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        // assert that the two tasks are in the order
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPATCHChildrenDuplicateIds() {
        $uri = 'backlog_items/'.$this->stories_ids[1].'/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        $first_task = $backlog_items[0];
        $this->assertEquals($first_task['label'], "Implement the feature");

        $first_id  = $backlog_items[0]['id'];
        $second_id = $backlog_items[1]['id'];

        $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($second_id, $second_id),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPATCHSomeoneElseChildren() {
        $uri = 'backlog_items/'.$this->stories_ids[1].'/children';
        $response      = $this->getResponse($this->client->get($uri));
        $backlog_items = $response->json();

        foreach ($backlog_items as $backlog_item) {
            $this->assertNotEquals($backlog_item['id'], 9999);
        }

        $first_id  = $backlog_items[0]['id'];

        $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array(9999),
                'direction'   => 'before',
                'compared_to' => $first_id
            )
        ))));
    }

    /**
    * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
    */
    public function testGETChildrenWithWrongId() {
        $response      = $this->getResponse($this->client->get('backlog_items/700/children'));
        $this->assertEquals($response->getStatusCode(), 404);
    }

    private function createStoriesAndTasks() {
        foreach ($this->backlog_items_datas as $backlog_item_data) {

            $tasks         = $this->createTasksForStory($backlog_item_data);
            $tracker       = $this->tracker_test_helper->getTrackerRest('story');
            $created_story = $tracker->createArtifact(
                array(
                    $tracker->getSubmitTextValue('I want to', $backlog_item_data['I want to']),
                    $tracker->getSubmitListValue('Status', $backlog_item_data['Status']),
                    $tracker->getSubmitArtifactLinkValue($tasks)
                )
            );

            $this->stories_ids[] = $created_story['id'];
        }
    }

    private function createTasksForStory($story) {
        $created_tasks = array();
        foreach ($story['Content'] as $task) {
            $tracker      = $this->tracker_test_helper->getTrackerRest('task');
            $created_task = $tracker->createArtifact(
                array(
                    $tracker->getSubmitTextValue('Summary', $task['Summary']),
                    $tracker->getSubmitListValue('Status', $task['Status'])
                )
            );

            $created_tasks[] = $created_task['id'];
        }

        return $created_tasks;
    }
}
