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

namespace Tuleap\Language\Gettext;

class POTEntryCollection
{
    /** @var string */
    private $domain;
    /** @var POTEntry[] */
    private $entries;

    public function __construct($domain)
    {
        $this->domain  = $domain;
        $this->entries = array();
    }

    public function add($domain, POTEntry $entry)
    {
        if ($domain === $this->domain) {
            $this->entries[] = $entry;
        }
    }

    /**
     * @return POTEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }
}