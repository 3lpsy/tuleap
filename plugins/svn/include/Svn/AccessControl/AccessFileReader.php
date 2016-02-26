<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\AccessControl;

use Tuleap\Svn\Repository\Repository;

/**
 * Read the content of a .SVNAccessFile
 */
class AccessFileReader {

    private static $FILENAME = ".SVNAccessFile";

    public function readContentBlock(Repository $repository) {
        $blocks = $this->extractBlocksFromAccessFile($repository);

        return $blocks['content'];
    }

    public function readDefaultBlock(Repository $repository) {
        $blocks = $this->extractBlocksFromAccessFile($repository);

        return $blocks['default'];
    }

    private function extractBlocksFromAccessFile(Repository $repository) {
        $blocks = array(
            'default' => '',
            'content' => ''
        );

        $in_default_block = false;
        foreach (file($this->getPath($repository)) as $line) {
            if ($this->isDefaultBlockStarting($line)) {
                $in_default_block = true;
            }

            if ($in_default_block) {
                $blocks['default'] .= $line;
            } else {
                $blocks['content'] .= $line;
            }

            if ($this->isDefaultBlockEnding($line)) {
                $in_default_block = false;
            }
        }

        return $blocks;
    }

    private function isDefaultBlockStarting($line) {
        return strpos($line, '# BEGIN CODENDI DEFAULT') !== false;
    }

    private function isDefaultBlockEnding($line) {
        return strpos($line, '# END CODENDI DEFAULT') !== false;
    }

    private function getPath(Repository $repository) {
        return $repository->getSystemPath() .'/'. self::$FILENAME;
    }
}