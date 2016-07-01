<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
 *  Data Access Object for Tracker_FormElement_Field 
 */
class Tracker_FormElement_Field_ComputedDao extends Tracker_FormElement_SpecificPropertiesDao {
    
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_field_computed';
    }
    
    public function save($field_id, $row) {
        $field_id  = $this->da->escapeInt($field_id);
        
        $target_field_name = '';
        if (isset($row['target_field_name'])) {
            $target_field_name = $this->da->quoteSmart($row['target_field_name']);
        }

        $fast_compute = 0;
        if (isset($row['fast_compute'])) {
            $fast_compute = $this->da->escapeInt($row['fast_compute']);
        }

        $sql = "REPLACE INTO $this->table_name (field_id, target_field_name, fast_compute)
                VALUES ($field_id, $target_field_name, $fast_compute)";
        return $this->retrieve($sql);
    }
    
    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id   the field id target
     *
     * @return boolean true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id) {
        $from_field_id  = $this->da->escapeInt($from_field_id);
        $to_field_id  = $this->da->escapeInt($to_field_id);
        
        $sql = "REPLACE INTO $this->table_name (field_id, target_field_name, fast_compute)
                SELECT $to_field_id, target_field_name, fast_compute FROM $this->table_name WHERE field_id = $from_field_id";
        return $this->update($sql);
    }

     /**
     * This method will fetch in 1 pass, for a given artifact all linked artifact
     * $target_name field values (values can be either float, int or computed)
     * If it's computed, the caller must continue its journey and call getComputedValue
     *
     * @param Integer[] $source_ids
     * @param String $target_name
     * @return DataAccessResult
     */
    public function getFieldValues(array $source_ids, $target_name)
    {
        $source_ids   = $this->da->escapeIntImplode($source_ids);
        $target_name = $this->da->quoteSmart($target_name);

        $sql = "SELECT linked_art.*, f_compute.formElement_type as type, cv_compute_i.value as int_value, cv_compute_f.`value` as float_value
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f           ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv          ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink     ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art  ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker_field                        f_compute   ON (f_compute.tracker_id = linked_art.tracker_id AND f_compute.name = $target_name AND f_compute.use_it = 1)
                    LEFT JOIN (
                        tracker_changeset_value cs_compute_i
                        INNER JOIN tracker_changeset_value_int cv_compute_i ON (cv_compute_i.changeset_value_id = cs_compute_i.id)
                    ) ON (cs_compute_i.changeset_id = linked_art.last_changeset_id AND cs_compute_i.field_id = f_compute.id)
                    LEFT JOIN (
                        tracker_changeset_value cs_compute_f
                        INNER JOIN tracker_changeset_value_float cv_compute_f ON (cv_compute_f.changeset_value_id = cs_compute_f.id)
                    ) ON (cs_compute_f.changeset_id = linked_art.last_changeset_id AND cs_compute_f.field_id = f_compute.id)
                WHERE parent_art.id IN ($source_ids)";

        return $this->retrieve($sql);
    }

    /**
     * This method will fetch in 1 pass, for a given artifact all linked artifact
     * $target_name field values (values can be either float, int or computed or manual)
     *
     * @param Integer[] $source_ids
     * @param String $target_name
     * @return DataAccessResult
     */
    public function getComputedFieldValues(array $source_ids, $target_name, $field_id, $stop_on_manual_value)
    {
        $source_ids  = $this->da->escapeIntImplode($source_ids);
        $target_name = $this->da->quoteSmart($target_name);
        $field_id    = $this->da->escapeInt($field_id);

        $manual_selection = 'null';
        $manual_condition = "";
        if ($stop_on_manual_value) {
            $manual_selection = "manual.value";

            $manual_condition = "
                LEFT JOIN tracker_field  manual_field
                ON (
                    manual_field.tracker_id = parent_art.tracker_id
                    AND manual_field.name   = $target_name
                    AND manual_field.use_it = 1
                    AND manual_field.id      = $field_id
                )
                LEFT JOIN (
                    tracker_changeset_value value
                    INNER JOIN tracker_changeset_value_computedfield_manual_value manual
                        ON (manual.changeset_value_id = value.id)
                ) ON (
                    value.changeset_id  = parent_art.last_changeset_id
                    AND value.field_id  = manual_field.id
                    AND parent_field.id = $field_id
                )
                ";
        }

        $sql = "SELECT linked_art.id                     AS id,
                    artifact_link_field.formElement_type AS type,
                    integer_value.value                  AS int_value,
                    float_value.value                    AS float_value,
                    $manual_selection                    AS value,
                    linked_art.tracker_id                AS tracker_id,
                    parent_art.id                        AS parent_id,
                    parent_art.last_changeset_id,
                    parent_value.field_id AS parent_value_field_id,
                    value.field_id AS value_field_id,
                    manual_field.id AS maunal_field_id,
                    artifact_link_field.id AS artlink_field_id,
                    parent_field.id  AS parent_field_id
                FROM tracker_artifact parent_art
                INNER JOIN tracker_field parent_field ON (
                    parent_field.tracker_id            = parent_art.tracker_id
                    AND (parent_field.formElement_type = 'art_link' OR parent_field.formElement_type = 'computed')
                    AND parent_field.use_it            = 1
                )
                INNER JOIN tracker_changeset_value parent_value ON (
                    parent_value.changeset_id = parent_art.last_changeset_id
                    AND parent_value.field_id = parent_field.id
                )
                LEFT JOIN tracker_changeset_value_artifactlink      artifact_link_value
                    INNER JOIN tracker_artifact                     linked_art
                        ON (linked_art.id = artifact_link_value.artifact_id)
                ON (
                    artifact_link_value.changeset_value_id = parent_value.id
                    AND parent_value.changeset_id          = parent_art.last_changeset_id
                    AND parent_value.field_id              = parent_field.id
                    AND parent_field.tracker_id            = parent_art.tracker_id
                )
                LEFT JOIN tracker_field                        artifact_link_field
                ON (
                    artifact_link_field.tracker_id = linked_art.tracker_id
                    AND artifact_link_field.name   = $target_name
                    AND artifact_link_field.use_it = 1
                )
                LEFT JOIN tracker_field manual_field
                ON (
                    manual_field.tracker_id = parent_art.tracker_id
                    AND manual_field.name   = $target_name
                    AND manual_field.use_it = 1
                )
                LEFT JOIN (
                    tracker_changeset_value value
                    INNER JOIN tracker_changeset_value_computedfield_manual_value manual
                        ON (manual.changeset_value_id = value.id )
                ) ON (
                    value.changeset_id  = parent_art.last_changeset_id
                    AND value.field_id  = manual_field.id
                )
                LEFT JOIN (
                    tracker_changeset_value integer_changeset
                    INNER JOIN tracker_changeset_value_int integer_value
                        ON (integer_value.changeset_value_id = integer_changeset.id)
                ) ON (
                    integer_changeset.changeset_id = linked_art.last_changeset_id
                    AND integer_changeset.field_id = artifact_link_field.id
                )
                LEFT JOIN (
                    tracker_changeset_value float_changeset
                    INNER JOIN tracker_changeset_value_float float_value
                        ON (float_value.changeset_value_id = float_changeset.id)
                ) ON (
                    float_changeset.changeset_id = linked_art.last_changeset_id
                    AND float_changeset.field_id = artifact_link_field.id
                )
                WHERE parent_art.id IN ($source_ids)
                ORDER BY manual.value DESC";

        return $this->retrieve($sql);
    }

    /**
     * This version leverage on SQL trick with the 2 joins on tracker_changeset
     * (cs_parent_art1 and cs_parent_art2 + the cs_parent_art2.id is NULL)
     * Some references:
     * - Docman_ItemDao::_getItemSearchFromStmt
     * - http://stackoverflow.com/questions/2111384/sql-join-selecting-the-last-records-in-a-one-to-many-relationship
     *
     * This trick needs to be used twice in this query:
     * - First time to select the changeset of the parent artifact (cs_parent_art1)
     * - Second time to select the changesets for each linked artifact (cs_linked_art1)
     *
     * Please note that we are not ranking with the date as this is not discriment enough.
     * If 2 changeset have the same timestamp (for instance with workflow trigger) we end
     * up with 2 matching changeset instead of one.
     * To avoid this situation, we order with changeset.id (unique).
     *
     * Please note however that, ranking by changeset id, can be misleading if we
     * start to introduce changes in the past (the new changeset will have a newer id
     * but it's date might be before). 99% of the time it should be transparent.
     *
     * @param Integer $source_ids
     * @param String  $target_name
     * @param Integer $timestamp
     *
     * @return DataAccessResult
     */
    public function getFieldValuesAtTimestamp(array $source_ids, $target_name, $timestamp, $field_id)
    {
        $source_ids  = $this->da->escapeIntImplode($source_ids);
        $timestamp   = $this->da->escapeInt($timestamp);
        $field_id    = $this->da->escapeInt($field_id);
        $target_name = $this->da->quoteSmart($target_name);

        $sql = "SELECT linked_art.id AS id, artifact_link_field.formElement_type AS type, cv_compute_i.value AS int_value,
                        cv_compute_f.value AS float_value, NULL AS value, linked_art.tracker_id AS tracker_id,
                        parent_art.id AS parent_id, linked_art.*,parent_art.last_changeset_id,
                        artifact_link_field.id               AS artifact_link_field,
                        ''                                  AS value_field_id,
                        f.id                      AS other_field_id
                    FROM tracker_artifact parent_art
                        INNER JOIN tracker_changeset                    cs_parent_art1 ON (cs_parent_art1.artifact_id = parent_art.id AND cs_parent_art1.submitted_on <= $timestamp)
                        LEFT JOIN  tracker_changeset                    cs_parent_art2 ON (cs_parent_art2.artifact_id = parent_art.id AND cs_parent_art1.id < cs_parent_art2.id AND cs_parent_art2.submitted_on <= $timestamp)
                        INNER JOIN tracker_field                        f              ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                        INNER JOIN tracker_changeset_value              cv             ON (cv.changeset_id = cs_parent_art1.id AND cv.field_id = f.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink        ON (artlink.changeset_value_id = cv.id)
                        INNER JOIN tracker_artifact                     linked_art     ON (linked_art.id = artlink.artifact_id)
                        INNER JOIN tracker_changeset                    cs_linked_art1 ON (cs_linked_art1.artifact_id = linked_art.id AND cs_linked_art1.submitted_on <= $timestamp)
                        LEFT JOIN  tracker_changeset                    cs_linked_art2 ON (cs_linked_art2.artifact_id = linked_art.id AND cs_linked_art1.id < cs_linked_art2.id AND cs_linked_art2.submitted_on <= $timestamp)
                        INNER JOIN tracker_field                        artifact_link_field      ON (artifact_link_field.tracker_id = linked_art.tracker_id AND artifact_link_field.name = $target_name AND artifact_link_field.use_it = 1)
                        LEFT JOIN (
                            tracker_changeset_value cs_compute_i
                            INNER JOIN tracker_changeset_value_int cv_compute_i ON (cv_compute_i.changeset_value_id = cs_compute_i.id)
                        ) ON (cs_compute_i.changeset_id = cs_linked_art1.id AND cs_compute_i.field_id = artifact_link_field.id)
                        LEFT JOIN (
                            tracker_changeset_value cs_compute_f
                            INNER JOIN tracker_changeset_value_float cv_compute_f ON (cv_compute_f.changeset_value_id = cs_compute_f.id)
                        ) ON (cs_compute_f.changeset_id = cs_linked_art1.id AND cs_compute_f.field_id = artifact_link_field.id)
                    WHERE parent_art.id IN ($source_ids)
                    AND (cs_parent_art2.id IS NULL AND cs_linked_art2.id IS NULL)
                UNION
                    SELECT parent_art.id AS id, f.formElement_type AS type, NULL AS int_value,
                        NULL AS float_value, manual.value AS value, parent_art.tracker_id AS tracker_id,
                        parent_art.id AS parent_id, parent_art.*, parent_art.last_changeset_id,
                        ''                                   AS artifact_link_field,
                        cv.field_id                          AS value_field_id,
                        f.id                                 AS other_field_id
                     FROM tracker_artifact parent_art
                    INNER JOIN tracker_field               f
                        ON (
                            f.tracker_id = parent_art.tracker_id
                            AND f.formElement_type = 'computed' AND f.use_it = 1
                            AND f.id = $field_id
                            AND f.name = $target_name
                        )
                    INNER JOIN tracker_changeset_value     cv
                        ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_computedfield_manual_value manual
                        ON (manual.changeset_value_id = cv.id AND cv.changeset_id = parent_art.last_changeset_id)
                    WHERE parent_art.id IN ($source_ids) AND value IS NOT NULL
                ORDER BY value DESC";

        return $this->retrieve($sql);
    }

    public function getCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        $sql = "SELECT value FROM tracker_field_computed_cache
                WHERE  artifact_id= $artifact_id
                    AND timestamp = $timestamp
                    AND field_id  = $field_id";

        return $this->retrieveFirstRow($sql);
    }

    public function saveCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp, $value) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        if ($value === null) {
            $sql = "REPLACE INTO tracker_field_computed_cache (artifact_id, field_id, timestamp)
                        VALUES ($artifact_id, $field_id, $timestamp)";
        } else {
            $value = $this->da->quoteSmart($value);
            $sql   = "REPLACE INTO tracker_field_computed_cache (artifact_id, field_id, timestamp, value)
                    VALUES ($artifact_id, $field_id, $timestamp, $value)";
        }

        return $this->update($sql);
    }

}