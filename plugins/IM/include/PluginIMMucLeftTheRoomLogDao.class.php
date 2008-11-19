<?php
/**
 * Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * PluginIMMucLeftTheRoomLogDao
 */

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for MucLeftTheRoomLog 
 */
class PluginIMMucLeftTheRoomLogDao extends DataAccessObject {
    /**
    * Constructs the PluginIMMucLeftTheRoomLogDao
    * @param $da instance of the DataAccess class
    */
    function PluginIMMucLeftTheRoomLogDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Searches 'left the room' event logs by muc room name 
    * @return DataAccessResult
    */
    function & searchByMucName($muc_name) {
        $sql = sprintf("SELECT p.leftDate, p.nickname  
                        FROM ofConParticipant p, ofConversation c
                        WHERE SUBSTRING_INDEX(c.room, '@', 1) = %s AND
                              c.conversationID = p.conversationID
                        ORDER BY p.leftDate ASC",
            $this->da->quoteSmart($muc_name));
        return $this->retrieve($sql);
    }

	/**
    * Searches 'left the room' event logs by muc room name before a date 
    * @return DataAccessResult
    */
    function & searchByMucNameBeforeDate($muc_name, $end_date) {
        $sql = sprintf("SELECT p.leftDate, p.nickname  
                        FROM ofConParticipant p, ofConversation c
                        WHERE SUBSTRING_INDEX(c.room, '@', 1) = %s AND
                              c.conversationID = p.conversationID AND
                              p.leftDate <= UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000
                        ORDER BY p.leftDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
	/**
    * Searches 'left the room' event logs by muc room name after a date 
    * @return DataAccessResult
    */
    function & searchByMucNameAfterDate($muc_name, $start_date) {
        $sql = sprintf("SELECT p.leftDate, p.nickname  
                        FROM ofConParticipant p, ofConversation c
                        WHERE SUBSTRING_INDEX(c.room, '@', 1) = %s AND
                              c.conversationID = p.conversationID AND
                              p.leftDate >= UNIX_TIMESTAMP(%s) * 1000
                        ORDER BY p.leftDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date)
            );
        return $this->retrieve($sql);
    }
    
    /**
    * Searches 'left the room' event logs by muc room name between two dates 
    * @return DataAccessResult
    */
    function & searchByMucNameBetweenDates($muc_name, $start_date, $end_date) {
        $sql = sprintf("SELECT p.leftDate, p.nickname  
                        FROM ofConParticipant p, ofConversation c
                        WHERE SUBSTRING_INDEX(c.room, '@', 1) = %s AND
                              c.conversationID = p.conversationID AND
                              p.leftDate >= UNIX_TIMESTAMP(%s) * 1000 AND
                              p.leftDate <= UNIX_TIMESTAMP(ADDDATE(%s, 1)) * 1000
                        ORDER BY p.leftDate ASC",
            $this->da->quoteSmart($muc_name),
            $this->da->quoteSmart($start_date),
            $this->da->quoteSmart($end_date)
            );
        return $this->retrieve($sql);
    }
    
}

?>