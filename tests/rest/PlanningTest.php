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
 * @group PlanningTests
 */
class PlanningTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOptionsPlannings() {
        $response = $this->getResponse($this->client->options('projects/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetPlanningsContainsAReleasePlanning() {
        $response = $this->getResponse($this->client->get('projects/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings'));

        $plannings = $response->json();

        $this->assertCount(2, $plannings);

        $release_planning = $plannings[0];
        $this->assertArrayHasKey('id', $release_planning);
        $this->assertEquals($release_planning['label'], "Release Planning");
        $this->assertEquals($release_planning['project'], array('id' => REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));
        $this->assertArrayHasKey('id', $release_planning['milestone_tracker']);
        $this->assertArrayHasKey('uri', $release_planning['milestone_tracker']);
        $this->assertRegExp('%^trackers/[0-9]+$%', $release_planning['milestone_tracker']['uri']);
        $this->assertCount(1, $release_planning['backlog_trackers']);
        $this->assertEquals($release_planning['milestones_uri'], 'plannings/'.$release_planning['id'].'/milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestone() {
        $response = $this->getResponse($this->client->get($this->getMilestonesUri()));

        $this->assertCount(1, $response->json());

        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function getMilestonesUri() {
        $response_plannings = $this->getResponse($this->client->get('projects/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings'))->json();
        return $response_plannings[0]['milestones_uri'];
    }
}
