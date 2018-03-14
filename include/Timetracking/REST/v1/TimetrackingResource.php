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

namespace Tuleap\Timetracking\REST\v1;

use DateTime;
use EventManager;
use Exception;
use Luracast\Restler\RestException;
use StandardPasswordHandler;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\REST\UserManager;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeRetriever;
use User_LoginManager;
use User_PasswordExpirationChecker;

class TimetrackingResource extends AuthenticatedResource
{
    /**
     * @var UserManager
     */
    private $rest_user_manager;

    /**
     * @var TimetrackingRepresentationBuilder
     */
    private $representation_builder;

    public function __construct()
    {
        $this->representation_builder = new TimetrackingRepresentationBuilder(
            new TimeRetriever(
                new TimeDao(),
                new PermissionsRetriever(
                    new TimetrackingUgroupRetriever(
                        new TimetrackingUgroupDao()
                    )
                )
            )
        );

        $user_manager = \UserManager::instance();

        $this->rest_user_manager = new UserManager(
            $user_manager,
            new User_LoginManager(
                EventManager::instance(),
                $user_manager,
                new User_PasswordExpirationChecker(),
                new StandardPasswordHandler()
            )
        );
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get Timetracking times
     *
     * Get the times in all projects for the current user and a given time period
     *
     * <br><br>
     * Notes on the query parameter
     * <ol>
     *  <li>You have to specify a start_date and an end_date</li>
     *  <li>One day minimum between the two dates</li>
     *  <li>end_date must be greater than start_date</li>
     *  <li>Dates must be in ISO date format</li>
     * </ol>
     *
     * Example of query:
     * <br><br>
     * {
     *   "start_date": "2018-03-01T00:00:00+01",
     *   "end_date"  : "2018-03-31T00:00:00+01"
     * }
     * @url GET
     * @access protected
     *
     * @param string $query JSON object of search criteria properties {@from query}
     *
     * @return array {@type TimetrackingRepresentation}
     * @throws RestException
     */
    protected function get($query)
    {
        $this->checkAccess();

        $this->sendAllowHeaders();

        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());

        try {
            $start_date = $query_parameter_parser->getString($query, 'start_date');
            $end_date   = $query_parameter_parser->getString($query, 'end_date');
        } catch (QueryParameterException $ex) {
            throw new RestException(400, $ex->getMessage());
        }

        $this->checkTimePeriodIsValid($start_date, $end_date);

        $current_user = $this->rest_user_manager->getCurrentUser();

        return $this->representation_builder->buildAllRepresentationsForUser($current_user, $start_date, $end_date);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }

    private function checkTimePeriodIsValid($start_date, $end_date)
    {
        $period_start = DateTime::createFromFormat(DateTime::ISO8601, $start_date);
        $period_end   = DateTime::createFromFormat(DateTime::ISO8601, $end_date);

        if (! $period_start || ! $period_end) {
            throw new RestException(400, "Please provide valid ISO-8601 dates");
        }

        $period_length = $period_start->diff($period_end);

        if ($period_length->days < 1) {
            throw new RestException(400, 'There must be one day offset between the both dates');
        }
        if ($period_start > $period_end) {
            throw new RestException(400,"end_date must be greater than start_date");
        }
    }
}
