<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/plugin/Plugin.class.php';
require_once 'Statistics_DiskUsageManager.class.php';

class StatisticsPlugin extends Plugin {

    function __construct($id) {
        parent::__construct($id);
        $this->_addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->_addHook('root_daily_start', 'root_daily_start', false);
    }

    function getPluginInfo() {
        if (!$this->pluginInfo instanceof StatisticsPluginInfo) {
            include_once('StatisticsPluginInfo.class.php');
            $this->pluginInfo = new StatisticsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Statistics</a></li>';
    }

    /**
     * Each day, load sessions info from elapsed day.
     * We need to do that because sessions are deleted from DB when user logout
     * or when session expire.
     *
     * This not perfect because with very short session (few hours for instance)
     * do data will survive in this session table.
     */
    protected function _archiveSessions() {
        $max = 0;
        $sql = 'SELECT MAX(time) as max FROM plugin_statistics_user_session';
        $res = db_query($sql);
        if ($res && db_numrows($res) == 1) {
            $row = db_fetch_array($res);
            if($row['max'] != null) {
                $max = $row['max'];
            }
        }

        $sql = 'INSERT INTO plugin_statistics_user_session (user_id, time)'.
               ' SELECT user_id, time FROM session WHERE time > '.$max;
        db_query($sql);
    }

    /**
     *
     */
    protected function _diskUsage() {
        $dum = new Statistics_DiskUsageManager();
        $dum->collectAll();
    }

    /**
     * Hook.
     *
     * @param $params
     * @return void
     */
    function root_daily_start($params) {
        $this->_archiveSessions();
        $this->_diskUsage();
    }
}

?>
