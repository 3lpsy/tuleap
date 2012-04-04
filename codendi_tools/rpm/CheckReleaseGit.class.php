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
class CheckReleaseGit {
    //put your code here
    public function getVersionList($ls_remote_output) {
        $lines    = explode('\n', $ls_remote_output);
        $versions = array();
        foreach ($lines as $line) {
            $parts      = explode('/', $line);
            $versions[] = array_pop($parts);
        }
        return $versions;
    }

    public function maxVersion($versions) {
        return array_reduce($versions, array($this, 'max'));
    }
    
    public function max($v1, $v2) {
        return version_compare($v1, $v2, '>') ? $v1 : $v2;
    }
}

?>
