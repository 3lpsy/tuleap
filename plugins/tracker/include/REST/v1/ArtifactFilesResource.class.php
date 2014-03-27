<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use \Luracast\Restler\RestException;
use \Tracker_Artifact_Attachment_TemporaryFileManager             as FileManager;
use \Tracker_Artifact_Attachment_TemporaryFileManagerDao          as FileManagerDao;
use \Tracker_Artifact_Attachment_FileNotFoundException            as FileNotFoundException;
use \Tuleap\Tracker\REST\Artifact\FileDataRepresentation          as FileDataRepresentation;
use \Tracker_Artifact_Attachment_PermissionDeniedOnFieldException as PermissionDeniedOnFieldException;
use \Tuleap\REST\Exceptions\LimitOutOfBoundsException;
use \Tuleap\REST\Header;
use \UserManager;
use \PFUser;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \Tracker_FileInfoFactory;
use \Tracker_FileInfoDao;

class ArtifactFilesResource {

    const DEFAULT_LIMIT = 1048576; // 1Mo

    /** @var PFUser */
    private $user;

    /** @var Tracker_Artifact_Attachment_TemporaryFileManager */
    private $file_manager;

    public function __construct() {
        $this->user          = UserManager::instance()->getCurrentUser();
        $artifact_factory    = Tracker_ArtifactFactory::instance();
        $formelement_factory = Tracker_FormElementFactory::instance();
        $fileinfo_factory    = new Tracker_FileInfoFactory(
            new Tracker_FileInfoDao(),
            $formelement_factory,
            $artifact_factory
        );
        $this->file_manager = new FileManager(
            $this->user,
            new FileManagerDao(),
            $fileinfo_factory
        );
    }

    /**
     * @url OPTIONS {id}
     */
    protected function optionsId($id) {
        $this->sendAllowHeadersForArtifactFilesId();

        $this->getAttachedFileContent($id, 0, 0);
    }

    /**
     * Get a chunk of given file
     *
     * Get a chunk of given attached file
     *
     * @url GET {id}
     * @param int $id     Id of the file
     * @param int $offset Where to start to read the file
     * @param int $limit  How much to read the file
     *
     * @return \Tuleap\Tracker\REST\Artifact\FileDataRepresentation
     *
     * @throws 401
     * @throws 403
     * @throws 404
     * @throws 406
     */
    protected function getId($id, $offset = 0, $limit = self::DEFAULT_LIMIT) {
        $this->checkLimitValue($limit);

        $chunk = $this->getAttachedFileContent($id, $offset, $limit);
        $size  = $this->getAttachedFileSize($id);

        $this->sendAllowHeadersForArtifactFilesId();
        $this->sendPaginationHeaders($limit, $offset, $size);

        $file_data_representation = new FileDataRepresentation();

        return $file_data_representation->build($chunk);
    }

    /**
     * @throws 403
     * @throws 404
     */
    private function getAttachedFileContent($id, $offset, $limit) {
        try {
            return $this->file_manager->getAttachedFileChunk($id, $this->user, $offset, $limit);

        } catch (PermissionDeniedOnFieldException $e) {
            throw new RestException(403);
        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }

    /**
     * @throws 406
     */
    private function checkLimitValue($limit) {
        if ($limit > self::DEFAULT_LIMIT) {
            throw new LimitOutOfBoundsException(self::DEFAULT_LIMIT);
        }
    }

    private function sendAllowHeadersForArtifactFilesId() {
        Header::allowOptionsGet();
        Header::sendMaxFileChunkSizeHeaders(self::DEFAULT_LIMIT);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, FileManager::getMaximumChunkSize());
    }

    /**
     * @throws 404
     */
    private function getAttachedFileSize($id) {
        try {
            return $this->file_manager->getAttachedFileSize($id);

        } catch (FileNotFoundException $e) {
            throw new RestException(404);
        }
    }
}
