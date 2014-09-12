<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

require_once 'pre.php';

require_once dirname(__FILE__).'/../include/ProjectQuotaHtml.class.php';

$pluginManager    = PluginManager::instance();
$statisticsPlugin = $pluginManager->getPluginByName('statistics');
if (! $statisticsPlugin || ! $pluginManager->isPluginAvailable($statisticsPlugin)) {
    header('Location: '.get_server_url());
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$title = $GLOBALS['Language']->getText('plugin_statistics', 'projects_over_quota_title');
$GLOBALS['HTML']->header(array('title' => $title));

$pqHtml = new ProjectQuotaHtml();
$pqHtml->displayProjectsOverQuota();

$GLOBALS['HTML']->footer(array());

?>
