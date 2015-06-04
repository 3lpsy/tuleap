<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group KanbanTests
 */
class KanbanColumnsTest extends RestBase {
    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONSKanbanColumns() {
        $response = $this->getResponse($this->client->options('kanban_columns'));
        $this->assertEquals(array('OPTIONS', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPATCHKanbanColumns() {
        $url = 'kanban_columns/'. REST_TestDataBuilder::KANBAN_ONGOING_COLUMN_ID.'?kanban_id='. REST_TestDataBuilder::KANBAN_ID;

        $response = $this->getResponse($this->client->patch(
            $url,
            null,
            json_encode(array(
                "wip_limit" => 200
            ))
        ));

        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('kanban/'. REST_TestDataBuilder::KANBAN_ID));
        $kanban   = $response->json();

        $this->assertEquals($kanban['columns'][1]['limit'], 200);
    }
}
