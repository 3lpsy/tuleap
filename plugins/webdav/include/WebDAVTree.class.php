<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This is the WebDAV server tree it implements Sabre_DAV_ObjectTree to rewrite some methods
 */
class WebDAVTree extends Sabre_DAV_ObjectTree {

    /**
     * Tests if the release destination is a package
     * we allow moving releases only within the same project
     *
     * @param WebDAVFRSRelease $release
     * @param mixed $destination
     *
     * @return boolean
     */
    function releaseCanBeMoved($release, $destination) {
        return (($destination instanceof WebDAVFRSPackage)
        && ($release->getProject()->getGroupId() == $destination->getProject()->getGroupId()));
    }

    /**
     * Tests if the file destination is a release
     * we allow moving files only within the same project
     *
     * @param WebDAVFRSFile $file
     * @param mixed $destination
     *
     * @return boolean
     */
    function fileCanBeMoved($file, $destination) {
        return (($destination instanceof WebDAVFRSRelease)
        && ($file->getProject()->getGroupId() == $destination->getProject()->getGroupId()));
    }

    /**
     * Tests if the node can be moved or not
     *
     * @param mixed $source
     * @param mixed $destination
     *
     * @return boolean
     */
    function canBeMoved($source, $destination) {
        return(($source instanceof WebDAVFRSRelease && $this->releaseCanBeMoved($source, $destination))
        || ($source instanceof WebDAVFRSFile && $this->fileCanBeMoved($source, $destination)));
    }

    /**
     * We don't allow copying
     *
     * @param String $sourcePath
     * @param String $destinationPath
     *
     * @return void
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Tree#copy($sourcePath, $destinationPath)
     */
    public function copy($sourcePath, $destinationPath) {

        // This feature may be implemented in the future (mybe not).
        throw new Sabre_DAV_Exception_NotImplemented($GLOBALS['Language']->getText('plugin_webdav_common', 'copy'));

    }

    /**
     * This method moves nodes from location to another
     *
     * @return void
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Tree#move($sourcePath, $destinationPath)
     */
    public function move($sourcePath, $destinationPath) {

        list($sourceDir, $sourceName) = Sabre_DAV_URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = Sabre_DAV_URLUtil::splitPath($destinationPath);

        if ($sourceDir === $destinationDir) {
            $renameable = $this->getNodeForPath($sourcePath);
            $renameable->setName($destinationName);
        } else {
            $source = $this->getNodeForPath($sourcePath);
            $destination = $this->getNodeForPath($destinationDir);

            if ($this->canBeMoved($source, $destination)) {
                $source->move($destination);
            } else {
                throw new Sabre_DAV_Exception_MethodNotAllowed($GLOBALS['Language']->getText('plugin_webdav_common', 'move_error'));
            }
        }

    }

}

?>