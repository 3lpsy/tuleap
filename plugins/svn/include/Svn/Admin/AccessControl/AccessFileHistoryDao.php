<?php
/**
  * Copyright (c) Enalean, 2016. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\Svn\Admin\AccessControl;

use Project;
use Tuleap\Svn\Repository\Repository;
use DataAccessObject;

class AccessFileHistoryDao extends DataAccessObject {

    public function create(AccessFileHistory $access_file) {
        $this->da->startTransaction();

        $version_number = $this->da->escapeInt($access_file->getVersionNumber());
        $repository_id  = $this->da->escapeInt($access_file->getRepository()->getId());
        $content        = $this->da->quoteSmart($access_file->getContent());
        $version_date   = $this->da->escapeInt($access_file->getVersionDate());

        $sql = "INSERT INTO plugin_svn_accessfile_history
                    (version_number, repository_id, content, version_date)
                  VALUES ($version_number, $repository_id, $content, $version_date)";

        $id = $this->updateAndGetLastId($sql);
        if (! $id) {
        }

        $sql = "UPDATE plugin_svn_repositories
                SET accessfile_id = $id
                WHERE id = $repository_id";

        if (! $this->update($sql)) {
            $this->rollBack();
            return null;
        }

        $this->commit();
        return true;
    }

    public function searchByRepositoryId($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id";

        return $this->retrieve($sql);
    }

    public function searchCurrentVersion($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT accessfile.*
                FROM plugin_svn_accessfile_history AS accessfile
                    INNER JOIN plugin_svn_repositories AS repository ON (
                        repository.accessfile_id = accessfile.id
                        AND repository.id = $repository_id
                    )
                ";

        return $this->retrieveFirstRow($sql);
    }

    public function searchLastVersion($repository_id) {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT *
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id
                ORDER BY version_number DESC
                LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }
}