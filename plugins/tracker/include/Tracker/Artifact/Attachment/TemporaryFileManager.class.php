<?php

/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Manage temporary uploaded files
 */
class Tracker_Artifact_Attachment_TemporaryFileManager {

    const TEMP_FILE_PREFIX = 'rest_attachement_temp_';
    const TEMP_FILE_NB_MAX = 5;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManagerDao
     */
    private $dao;

    /**
     * @var Tracker_FileInfoFactory
     */
    private $file_info_factory;

    public function __construct(
        PFUser $user,
        Tracker_Artifact_Attachment_TemporaryFileManagerDao $dao,
        Tracker_FileInfoFactory $file_info_factory
    ) {
        $this->user              = $user;
        $this->dao               = $dao;
        $this->file_info_factory = $file_info_factory;
    }

    /**
     * Does the temporary file exists on filesystem
     *
     * @return Boolean
     */
    public function exists($attachment_name) {
        return file_exists($this->getPath($attachment_name));
    }

    /**
     * Return full path to the file on filesystem
     *
     * @return String
     */
    public function getPath($attachment_name) {
        return Config::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . $this->getUserTemporaryFilePrefix() . $attachment_name;
    }

    /**
     * Provision a new temporary file for user if possible and return it's UUID
     *
     * @return String
     * @throws Tracker_Artifact_Attachment_MaxFilesException
     */
    public function getUniqueFileName() {
        if ($this->isOverUserTemporaryFileLimit()) {
            throw new Tracker_Artifact_Attachment_MaxFilesException('Temporary attachment limits: ' . self::TEMP_FILE_NB_MAX . ' files max.');
        }
        $prefix = $this->getUserTemporaryFilePrefix();
        $file_path = tempnam(Config::get('codendi_cache_dir'), $prefix);
        return substr(basename($file_path), strlen($prefix));
    }

    /**
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     * @throws Tracker_Artifact_Attachment_CannotCreateException
     * @throws Tracker_Artifact_Attachment_MaxFilesException
     */
    public function save($name, $description, $mimetype) {
        $user_id   = $this->user->getId();
        $tempname  = $this->getUniqueFileName();
        $timestamp = $_SERVER['REQUEST_TIME'];

        $id = $this->dao->create($user_id, $name, $description, $mimetype, $timestamp, $tempname);

        if (!$id) {
            throw new Tracker_Artifact_Attachment_CannotCreateException();
        }

        $number_of_chunks = 0;
        $filesize = 0;

        return new Tracker_Artifact_Attachment_TemporaryFile($id, $name, $tempname, $description, $timestamp, $number_of_chunks, $this->user->getId(), $filesize, $mimetype);
    }

    /**
     * Get chunk of a file
     *
     * @param int    $attachment_id
     * @param PFUser $current_user
     * @param int    $offset
     * @param int    $size
     *
     * @return \Tracker_Artifact_Attachment_PermissionDeniedOnFieldException
     *
     * @throws Tracker_Artifact_Attachment_PermissionDeniedOnFieldException
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getAttachedFileChunk($attachment_id, PFUser $current_user, $offset, $size) {
        $file_info = $this->file_info_factory->getById($attachment_id);

        if ($file_info && $file_info->fileExists()) {
            $field = $file_info->getField();

            if ($field->userCanRead($current_user)) {
                return $file_info->getContent($offset, $size);

            } else {
                throw new Tracker_Artifact_Attachment_PermissionDeniedOnFieldException('Permission denied: you cannot access this field');
            }
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * Returns encoded content chunk of file
     *
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @param int $offset Where to start reading
     * @param int $size   How much to read
     *
     * @return string Base64 encoded content
     *
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getTemporaryFileChunk($file, $offset, $size) {
        $temporary_name = $file->getTemporaryName();

        if ($this->exists($temporary_name)) {
            return base64_encode(file_get_contents($this->getPath($temporary_name), false, NULL, $offset, $size));
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * Append some content (base64 encoded) to the file
     *
     * @param String $content
     * @param Tracker_Artifact_Attachment_TemporaryFile $file
     * @param int $offset
     *
     * @return boolean
     * @throws Tracker_Artifact_Attachment_InvalidPathException
     * @throws Tracker_Artifact_Attachment_InvalidOffsetException
     */
    public function appendChunk($content, Tracker_Artifact_Attachment_TemporaryFile $file, $offset) {
        $current_offset = $file->getCurrentChunkOffset();

        if ($current_offset + 1 !== (int) $offset) {
            throw new Tracker_Artifact_Attachment_InvalidOffsetException();
        }

        if ($this->exists($file->getTemporaryName())) {
            $bytes_written = file_put_contents($this->getPath($file->getTemporaryName()), base64_decode($content), FILE_APPEND);

        } else {
            throw new Tracker_Artifact_Attachment_InvalidPathException('Invalid temporary file path');
        }

        $size = exec('stat -c %s ' . $this->getPath($file->getTemporaryName()));
        $file->setSize($size);

        return $bytes_written && $this->dao->updateFileInfo($file->getId(), $offset, $_SERVER['REQUEST_TIME'], $size);
    }

    /**
     * @return Tracker_Artifact_Attachment_TemporaryFile[]
     */
    public function getUserTemporaryFiles() {
        return $this->dao->getUserTemporaryFiles($this->user->getId())->instanciateWith(array($this, 'getInstanceFromRow'));
    }

    private function isOverUserTemporaryFileLimit() {
        return count($this->getUserTemporaryFiles()) > (self::TEMP_FILE_NB_MAX - 1);
    }

    private function getUserTemporaryFilePrefix() {
        return self::TEMP_FILE_PREFIX . $this->user->getId() . '_';
    }

    public function getAttachedFileSize($id) {
        $file_info = $this->file_info_factory->getById($id);

        if ($file_info && $file_info->fileExists()) {
            return $file_info->getFilesize();
        }

        throw new Tracker_Artifact_Attachment_FileNotFoundException();
    }

    /**
     * @throws Tracker_Artifact_Attachment_ChunkTooBigException
     */
    public function validateChunkSize($content) {
        $chunk_size = strlen(base64_decode($content));

        if ($chunk_size > self::getMaximumChunkSize()) {
            throw new Tracker_Artifact_Attachment_ChunkTooBigException();
        }
    }

    /**
     * @throws Tracker_Artifact_Attachment_TemporaryFileTooBigException
     */
    public function validateTemporaryFileSize($file, $content) {
        $chunk_size = strlen(base64_decode($content));
        $file_size  = (int) exec('stat -c %s ' . $this->getPath($file->getTemporaryName()));
        $total_size = $chunk_size + $file_size;

        if ($total_size > self::getMaximumTemporaryFileSize()) {
            throw new Tracker_Artifact_Attachment_TemporaryFileTooBigException();
        }
    }

    /**
     * Max chunk size : 1 Mo = 1048576 bytes
     */
    public static function getMaximumChunkSize() {
        return 1048576;
    }

    /**
     * Max chunk size : 64 Mo = 67108864 bytes
     */
    public static function getMaximumTemporaryFileSize() {
        return Config::get('sys_max_size_upload');
    }

    /**
     * @return \Tracker_Artifact_Attachment_TemporaryFile
     * @throws Tracker_Artifact_Attachment_FileNotFoundException
     */
    public function getFile($id) {
        $row = $this->dao->getTemporaryFile($id);

        if (! $row) {
            throw new Tracker_Artifact_Attachment_FileNotFoundException();
        }

        return $this->getInstanceFromRow($row);
    }

    public function isFileIdTemporary($id) {
        return $this->dao->doesFileExist($id);
    }

    public function removeTemporaryFile(Tracker_Artifact_Attachment_TemporaryFile $file) {
        $this->removeTemporaryFileInDB($file->getId());
        $this->removeTemporaryFileFomFileSystem($file);
    }

    private function removeTemporaryFileInDB($id) {
        $this->dao->delete($id);
    }

    private function removeTemporaryFileFomFileSystem(Tracker_Artifact_Attachment_TemporaryFile $temporary_file) {
        $temporary_file_name = $temporary_file->getTemporaryName();
        $temporary_file_path = $this->getPath($temporary_file_name);

        if ($this->exists($temporary_file_name)) {
            unlink($temporary_file_path);
        }
    }

    public function getInstanceFromRow($row) {
        return new Tracker_Artifact_Attachment_TemporaryFile(
            $row['id'],
            $row['filename'],
            $row['tempname'],
            $row['description'],
            $row['last_modified'],
            $row['offset'],
            $row['submitted_by'],
            $row['filesize'],
            $row['filetype']
        );
    }
}

?>
