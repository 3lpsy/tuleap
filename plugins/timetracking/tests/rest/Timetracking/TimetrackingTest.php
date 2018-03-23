<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
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

namespace Tuleap\Timetracking\REST;

use Guzzle\Http\Exception\ClientErrorResponseException;
use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

class TimetrackingTest extends RestBase
{
    public function testGetTimesForUserWithDates()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-03-01T00:00:00+01",
                "end_date"   => "2018-03-10T00:00:00+01"
            ])
        );

        $response = $this->getResponse(
            $this->client->get("timetracking?query=$query"),
            TimetrackingDataBuilder::USER_TESTER_NAME
        );

        $times_by_artifact   = $response->json();
        $current_artifact_id = key($times_by_artifact);
        $times               = $times_by_artifact[ $current_artifact_id ];

        $this->assertTrue(count($times_by_artifact) === 1);
        $this->assertTrue(count($times) === 1);
        $this->assertEquals($times[0]['artifact']['id'], $current_artifact_id);
        $this->assertEquals($times[0]['id'], 1);
        $this->assertEquals($times[0]['minutes'], 600);
        $this->assertEquals($times[0]['date'], '2018-03-01');
    }

    public function testExceptionWhenStartDateMissing()
    {
        $query = urlencode(
            json_encode([
                "end_date" => "2018-03-10T00:00:00+01"
            ])
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'Missing start_date entry in the query parameter',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testExceptionWhenEndDateMissing()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-03-01T00:00:00+01"
            ])
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'Missing end_date entry in the query parameter',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testExceptionWhenStartDateGreaterThanEndDate()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-03-10T00:00:00+01",
                "end_date"   => "2018-03-01T00:00:00+01"
            ])
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'end_date must be greater than start_date',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testExceptionWhenDayOffsetLessThanOneDay()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-03-01T00:00:00+01",
                "end_date"   => "2018-03-01T00:00:00+01"
            ])
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'There must be one day offset between the both dates',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testExceptionWhenDatesAreInvalid()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "not a valid date",
                "end_date"   => ""
            ])
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'Please provide valid ISO-8601 dates',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testExceptionWhenDatesAreNotISO8601()
    {
        $query = urlencode(
            json_encode(
                [
                    "start_date" => "2018/01/01",
                    "end_date"   => "2018/01/30"
                ]
            )
        );

        $exception_thrown = false;

        try {
            $this->getResponse(
                $this->client->get("timetracking?query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );
        } catch (ClientErrorResponseException $exception) {
            $response = $exception->getResponse();
            $body     = $response->json();

            $this->assertEquals(400, $response->getStatusCode());
            $this->assertContains(
                'Please provide valid ISO-8601 dates',
                $body['error']['message']
            );

            $exception_thrown = true;
        }

        $this->assertTrue($exception_thrown);
    }

    public function testGetTimesPaginated()
    {
        $query = urlencode(
            json_encode([
                "start_date" => "2018-03-01T00:00:00+01",
                "end_date"   => "2018-03-31T00:00:00+01"
            ])
        );

        $times_ids = [ 1, 2 ];

        for ($offset = 0; $offset <= 1; $offset ++) {
            $response = $this->getResponse(
                $this->client->get("timetracking?limit=1&offset=$offset&query=$query"),
                TimetrackingDataBuilder::USER_TESTER_NAME
            );

            $artifact_times      = $response->json();
            $current_artifact_id = key($artifact_times);
            $times               = $artifact_times[ $current_artifact_id ];

            $this->assertTrue(count($artifact_times) === 1);
            $this->assertTrue(count($times) === 1);
            $this->assertEquals($times[0]['artifact']['id'], $current_artifact_id);
            $this->assertEquals($times[0]['id'], $times_ids[ $offset ]);
        }
    }
}
