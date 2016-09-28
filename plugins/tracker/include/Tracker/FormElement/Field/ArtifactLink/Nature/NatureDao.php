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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use DataAccessObject;

class NatureDao extends DataAccessObject {

    public function create($shortname, $forward_label, $reverse_label) {
        $nature        = $this->getNatureByShortname($shortname);
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $this->da->startTransaction();

        if ($nature->count() > 0) {
            $this->rollBack();
            throw new UnableToCreateNatureException(
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_same_name_error',
                    $shortname
                )
            );
        }

        $sql = "INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label)
                VALUES ($shortname, $forward_label, $reverse_label)";

        if (! $this->update($sql)) {
            $this->rollBack();
            return false;
        }

        $this->commit();
        return true;
    }

    public function getNatureByShortname($shortname) {
        $shortname     = $this->da->quoteSmart($shortname);

        $sql = "SELECT * FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";

        return $this->retrieve($sql);
    }

    public function edit($shortname, $forward_label, $reverse_label) {
        $shortname     = $this->da->quoteSmart($shortname);
        $forward_label = $this->da->quoteSmart($forward_label);
        $reverse_label = $this->da->quoteSmart($reverse_label);

        $sql = "UPDATE plugin_tracker_artifactlink_natures
                   SET forward_label = $forward_label, reverse_label = $reverse_label
                WHERE shortname = $shortname";

        return $this->update($sql);
    }

    public function delete($shortname) {
        $this->deleteNatureInTableColumns($shortname);

        $shortname = $this->da->quoteSmart($shortname);

        $sql = "DELETE FROM plugin_tracker_artifactlink_natures WHERE shortname = $shortname";

        return $this->update($sql);
    }

    private function deleteNatureInTableColumns($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "DELETE FROM tracker_report_renderer_table_columns WHERE artlink_nature = $shortname";

        return $this->update($sql);
    }

    public function isOrHasBeenUsed($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT nature
                  FROM tracker_changeset_value_artifactlink
                 WHERE nature = $shortname
                 LIMIT 1";

        $row = $this->retrieve($sql)->getRow();

        return (bool)$row['nature'];
    }

    public function searchAllUsedNatureByProject($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT DISTINCT nature
                  FROM tracker_changeset_value_artifactlink
                 WHERE group_id = $project_id
                 ORDER BY nature ASC";

        return $this->da->query($sql);
    }

    public function searchAll() {
        $sql = "SELECT DISTINCT shortname, forward_label, reverse_label, IF(cv.nature IS NULL, 0, 1) as is_used
                           FROM plugin_tracker_artifactlink_natures AS n
                      LEFT JOIN tracker_changeset_value_artifactlink AS cv ON n.shortname = cv.nature
                       ORDER BY shortname ASC";

        return $this->retrieve($sql);
    }

    public function getFromShortname($shortname) {
        $shortname = $this->da->quoteSmart($shortname);

        $sql = "SELECT DISTINCT shortname, forward_label, reverse_label, IF(cv.nature IS NULL, 0, 1) as is_used
                  FROM plugin_tracker_artifactlink_natures AS n
             LEFT JOIN tracker_changeset_value_artifactlink AS cv ON n.shortname = cv.nature
                 WHERE shortname = $shortname";

        return $this->retrieveFirstRow($sql);
    }

    public function searchForwardNatureShortNamesForGivenArtifact($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id )
                    INNER JOIN tracker                              AS t          ON t.id = parent_art.tracker_id
                WHERE parent_art.id  = $artifact_id";

        return $this->retrieve($sql);
    }

    public function searchReverseNatureShortNamesForGivenArtifact($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id )
                    INNER JOIN tracker                              AS t          ON t.id = parent_art.tracker_id
                WHERE linked_art.id  = $artifact_id";

        return $this->retrieve($sql);
    }

    public function getForwardLinkedArtifactIds($artifact_id, $nature, $limit, $offset)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $nature      = $this->da->quoteSmart($nature);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS artlink.artifact_id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id )
                    INNER JOIN tracker                              AS t          ON t.id = parent_art.tracker_id
                WHERE parent_art.id  = $artifact_id
                    AND IFNULL(artlink.nature, '') = $nature
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieveIds($sql);
    }

    public function getReverseLinkedArtifactIds($artifact_id, $nature, $limit, $offset)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $nature      = $this->da->quoteSmart($nature);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS parent_art.id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id )
                    INNER JOIN tracker                              AS t          ON t.id = parent_art.tracker_id
                WHERE linked_art.id  = $artifact_id
                    AND IFNULL(artlink.nature, '') = $nature
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieveIds($sql);
    }

}
