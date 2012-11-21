<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Workflow_Dao.class.php');
require_once('Workflow_TransitionDao.class.php');
require_once('Workflow.class.php');
require_once('WorkflowFactory.class.php');
require_once('PostAction/Field/Transition_PostAction_Field_Date.class.php');
require_once('PostAction/Transition_PostActionFactory.class.php');
require_once('PostAction/Transition_PostActionManager.class.php');
require_once('Transition/ConditionManager.class.php');
require_once('Action/Rules/EditRules.class.php');
require_once('Action/Transitions/Create.class.php');
require_once('Action/Transitions/EditTransition.class.php');
require_once('Action/Transitions/Delete.class.php');
require_once('Action/Transitions/CreateMatrix.class.php');
require_once('Action/Transitions/EnableWorkflow.class.php');
require_once('Action/Transitions/Details.class.php');
require_once('Action/Transitions/DefineWorkflow.class.php');

class WorkflowManager {
    protected $tracker;
    public function __construct($tracker) {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $engine, Codendi_Request $request, User $current_user) {
        if ($request->get('func') == 'admin-workflow-rules') {
            $action = new Tracker_Workflow_Action_Rules_EditRules($this->tracker, Tracker_FormElementFactory::instance());
        } else if ($request->get('create')) {
            $action = new Tracker_Workflow_Action_Transitions_Create($this->tracker, WorkflowFactory::instance());
        } else if ($request->get('edit_transition')) {
            $action = new Tracker_Workflow_Action_Transitions_EditTransition($this->tracker, TransitionFactory::instance(), new Transition_PostActionFactory());
        } else if ($request->get('delete')) {
            $action = new Tracker_Workflow_Action_Transitions_Delete($this->tracker, WorkflowFactory::instance());
        } else if ($request->get('create_matrix')) {
            $action = new Tracker_Workflow_Action_Transitions_CreateMatrix($this->tracker, WorkflowFactory::instance(), Tracker_FormElementFactory::instance());
        } else if ($request->get('enable_workflow')) {
            $action = new Tracker_Workflow_Action_Transitions_EnableWorkflow($this->tracker, WorkflowFactory::instance());
        } else if ($request->get('workflow_details')) {
            $action = new Tracker_Workflow_Action_Transitions_Details($this->tracker, TransitionFactory::instance());
        } else {
            $action = new Tracker_Workflow_Action_Transitions_DefineWorkflow($this->tracker, WorkflowFactory::instance(), Tracker_FormElementFactory::instance());
        }
        $action->process($engine, $request, $current_user);
    }
}
?>
