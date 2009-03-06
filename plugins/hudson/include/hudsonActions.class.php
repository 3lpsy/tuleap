<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonActions
 */

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('HudsonJob.class.php');
require_once('PluginHudsonJobDao.class.php');

/**
 * hudsonActions
 */
class hudsonActions extends Actions {
    
    function hudsonActions(&$controler, $view=null) {
        $this->Actions($controler);
	}
	
	// {{{ Actions
    function addJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_url = $request->get('hudson_job_url');
        try {
            $job = new HudsonJob($job_url);
            $use_svn_trigger = ($request->get('hudson_use_svn_trigger') === 'on');
            $use_cvs_trigger = ($request->get('hudson_use_cvs_trigger') === 'on');
            if ($use_svn_trigger || $use_cvs_trigger) {
                $token = $request->get('hudson_trigger_token');
            } else {
                $token = null;
            }
            $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            if ( ! $job_dao->createHudsonJob($group_id, $job_url, $job->getName(), $use_svn_trigger, $use_cvs_trigger, $token)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','add_job_error'));
            } else {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_added'));
            }
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }
    function updateJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $new_job_url = $request->get('new_hudson_job_url');
        $new_job_name = $request->get('new_hudson_job_name');
        if (strpos($new_job_name, " ") !== false) {
            $new_job_name = str_replace(" ", "_", $new_job_name);
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_hudson','edit_jobname_spacesreplaced'));
        }
        $new_use_svn_trigger = ($request->get('new_hudson_use_svn_trigger') === 'on');
        $new_use_cvs_trigger = ($request->get('new_hudson_use_cvs_trigger') === 'on');
        if ($new_use_svn_trigger || $new_use_cvs_trigger) {
            $new_token = $request->get('new_hudson_trigger_token');
        } else {
            $new_token = null;
        }
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ( ! $job_dao->updateHudsonJob($job_id, $new_job_url, $new_job_name, $new_use_svn_trigger, $new_use_cvs_trigger, $new_token)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','edit_job_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_updated'));
        }
    }
    function deleteJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ( ! $job_dao->deleteHudsonJob($job_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','delete_job_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_deleted'));
        }
    }
    // }}}
   
}

?>