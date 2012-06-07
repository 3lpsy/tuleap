<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Base interface to display a content pane in the agiledashboard next to a
 * milestone
 */
interface AgileDashboard_Pane {

    /**
     * @return string eg: 'cardwall'
     */
    public function getIdentifier();

    /**
     * @return string eg: 'Card Wall'
     */
    public function getTitle();

    /**
     * @return string eg: '<table>...</table>'
     */
    public function getContent();

    /**
     * @return bool
     */
    public function isActive();
}
?>
