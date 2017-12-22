<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Timesheeting\Admin;

use Tracker;

class TimesheetingUgroupRetriever
{

    /**
     * @var TimesheetingUgroupDao
     */
    private $dao;

    public function __construct(TimesheetingUgroupDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array
     */
    public function getWriterIdsForTracker(Tracker $tracker)
    {
        $ugroup_rows = $this->dao->getWriters($tracker->getId());

        $ugroup_ids = array();
        foreach ($ugroup_rows as $ugroup_row) {
            $ugroup_ids[] = $ugroup_row['ugroup_id'];
        }

        return $ugroup_ids;
    }
}