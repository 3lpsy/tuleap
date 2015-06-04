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
class ProjectBacklogV2Test extends RestBase {

    protected $base_url  = 'http://localhost:8089/api/v2';

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSBacklog() {
        $response = $this->getResponse($this->client->options(
                'projects/'.REST_TestDataBuilder::PROJECT_PUBLIC_ID.'/backlog'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETBacklogNoItems() {
        $response = $this->getResponse($this->client->get('projects/'.REST_TestDataBuilder::PROJECT_PUBLIC_ID.'/backlog?limit=0.0&offset=0'));

        $backlog = $response->json();

        $this->assertCount(0, $backlog['content']);
        $this->assertCount(0, $backlog['accept']['trackers']);

        $this->assertEquals($response->getStatusCode(), 200);
    }
}
