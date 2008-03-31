<?php
/* 
 * Copyright (c) The CodeX Team, Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for UserPreferences 
 */
class UserPreferencesDao extends DataAccessObject {
    /**
     * Constructs the UserPreferencesDao
     * @param $da instance of the DataAccess class
     */
    function UserPreferencesDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
     * Search user preferences by user id and preference name
     * @param int $user_id
     * @param string $preference_name
     * @return DataAccessResult
     */
    function & search($user_id, $preference_name) {
        $sql = sprintf("SELECT * FROM user_preferences WHERE user_id = %d AND preference_name = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name));
        return $this->retrieve($sql);
    }

    /**
     * Set a preference for the user
     *
     * @param int $user_id
     * @param string $preference_name
     * @param string $preference_value
     * @return boolean
     */
    function set($user_id, $preference_name, $preference_value) {
        $sql = sprintf("INSERT INTO user_preferences (user_id, preference_name, preference_value) VALUES (%d, %s, %s)
                        ON DUPLICATE KEY UPDATE preference_value = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name),
            $this->da->quoteSmart($preference_value),
            $this->da->quoteSmart($preference_value));
        return $this->update($sql);
    }
    
    /**
     * Delete a preference
     */
    function delete($user_id, $preference_name) {
        $sql = sprintf("DELETE FROM user_preferences WHERE user_id = %d AND preference_name = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name));
        return $this->update($sql);
    }
}


?>