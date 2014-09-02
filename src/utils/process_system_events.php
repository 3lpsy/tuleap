<?php
/**
 * Copyright (c) Enalean SAS, 2013-2014. All rights reserved
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
 *
 */

require_once 'pre.php';
require_once 'common/system_event/SystemEventProcessorMutex.class.php';
require_once 'common/system_event/SystemEventProcessApplicationOwner.class.php';

$queue = (isset($argv[1])) ? $argv[1] : SystemEvent::DEFAULT_QUEUE;
switch ($queue) {
    case SystemEvent::OWNER_APP :
        $process = new SystemEventProcessApplicationOwner();
        break;
    case SystemEvent::TV3_TV5_MIGRATION_QUEUE :
        $process = new SystemEventProcessAppOwnerTV3TV5Migration();
        break;
    case SystemEvent::FULL_TEXT_SEARCH_QUEUE :
        $process = new SystemEventProcessRootFullTextSearch();
        break;
    case SystemEvent::DEFAULT_QUEUE :
    default :
        $process = new SystemEventProcessRootDefault;
}

if (in_array($queue, array(SystemEvent::OWNER_APP, SystemEvent::TV3_TV5_MIGRATION_QUEUE))) {
    require_once 'common/system_event/SystemEventProcessor_ApplicationOwner.class.php';
    $processor = new SystemEventProcessor_ApplicationOwner(
        $process,
        $system_event_manager,
        new SystemEventDao(),
        new BackendLogger()
    );
} else {
    require_once 'common/system_event/SystemEventProcessor_Root.class.php';
    $processor = new SystemEventProcessor_Root(
        $process,
        $system_event_manager,
        new SystemEventDao(),
        new BackendLogger(),
        Backend::instance('Aliases'),
        Backend::instance('CVS'),
        Backend::instance('SVN'),
        Backend::instance('System')
    );
}

$mutex = new SystemEventProcessorMutex(new SystemEventProcessManager(), $processor);
$mutex->execute();
?>
