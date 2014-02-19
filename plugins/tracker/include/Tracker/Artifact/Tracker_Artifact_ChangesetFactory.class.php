<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Artifact_ChangesetFactory {
    /** Tracker_Artifact_ChangesetDao */
    private $dao;

    /** @var Tracker_Artifact_ChangesetJsonFormatter */
    private $json_formatter;

    public function __construct(Tracker_Artifact_ChangesetDao $dao, Tracker_Artifact_ChangesetJsonFormatter $json_formatter) {
        $this->dao = $dao;
        $this->json_formatter = $json_formatter;
    }

    /**
     * Return a changeset
     *
     * @param Tracker_Artifact $artifact
     * @param Integer $changeset_id
     * @return Tracker_Artifact_Changeset|null
     */
    public function getChangeset(Tracker_Artifact $artifact, $changeset_id) {
        $row = $this->dao->searchByArtifactIdAndChangesetId($artifact->getId(), $changeset_id)->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * Return all the changesets of an artifact
     *
     * @param Tracker_Artifact $artifact
     * @return Tracker_Artifact_Changeset[]
     */
    public function getChangesetsForArtifact(Tracker_Artifact $artifact) {
        $changesets = array();
        foreach ($this->dao->searchByArtifactId($artifact->getId()) as $row) {
            $changesets[$row['id']] = $this->getChangesetFromRow($artifact, $row);
        }
        return $changesets;
    }

    /**
     * Return the last changeset at a given timestamp
     *
     * @param Tracker_Artifact $artifact
     * @param int $timestamp
     * @return Tracker_Artifact_Changeset | null
     */
    public function getChangesetForArtifactAtTimestamp(Tracker_Artifact $artifact, $timestamp) {
        $row = $this->dao->searchLastChangesetAtDate($artifact->getId(), $timestamp)->getRow();
        if ($row) {
            return $this->getChangesetFromRow($artifact, $row);
        }
        return null;
    }

    /**
     * Get all changesets in a format ready for json conversion
     *
     * @param Tracker_Artifact $artifact
     * @param Integer $changeset_id
     * @return array
     */
    public function getNewChangesetsFormattedForJson(Tracker_Artifact $artifact, $changeset_id) {
        $changesets = array();
        foreach ($this->dao->searchChangesetNewerThan($artifact->getId(), $changeset_id) as $row) {
            $changesets[] = $this->json_formatter->format($this->getChangesetFromRow($artifact, $row));
        }
        return $changesets;
    }

    private function getChangesetFromRow(Tracker_Artifact $artifact, $row) {
        return new Tracker_Artifact_Changeset(
            $row['id'],
            $artifact,
            $row['submitted_by'],
            $row['submitted_on'],
            $row['email']
        );
    }
}

?>
