<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once('common/system_event/include/SystemEvent_UGROUP_MODIFY.class.php');
require_once('common/backend/BackendSystem.class.php');
Mock::generatePartial('SystemEvent_UGROUP_MODIFY',
                      'SystemEvent_UGROUP_MODIFY_TestVersion',
                      array('getProject',
                            'getBackend',
                            'done',
                            'processUgroupBinding',
                            'error',
                            'getEventManager',
                            'getParametersAsArray'));

Mock::generate('BackendSystem');
Mock::generate('Project');
Mock::generate('BackendSVN');
Mock::generate('UGroupBinding');

/**
 * Test for project delete system event
 */
class SystemEvent_UGROUP_MODIFY_Test extends UnitTestCase {

    /**
     * Ugroup modify Users fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessUgroupModifyFail() {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', false);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('1'));

        $backendSVN = new MockBackendSVN();
        $backendSVN->expectNever('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectNever('getProject');
        $evt->expectNever('getBackend');
        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not process binding to this user group (2)"));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Ugroup modify SVN fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessSVNFail() {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', true);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, 1);

        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('updateSVNAccess', false);
        $backendSVN->expectOnce('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectOnce('getProject');
        $evt->expectOnce('getBackend');
        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not update SVN access file (1)"));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Ugroup modify Success
     *
     * @return Void
     */
    public function testUgroupModifyProcessSuccess() {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', true);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, 1);

        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('updateSVNAccess', true);
        $backendSVN->expectOnce('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectOnce('getProject');
        $evt->expectOnce('getBackend');
        $evt->expectOnce('done');
        $evt->expectNever('error');

        // Launch the event
        $this->assertTrue($evt->process());
    }

}

?>