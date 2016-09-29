<?php
//
// Copyright (c) Enalean, 2016. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

use Tuleap\CVS\ViewVC\ViewVCProxyFactory;
use Tuleap\ViewVCVersionChecker;

require_once('pre.php');

if (user_isloggedin()) {
    // be backwards compatible with old viewvc.cgi links that are now redirected
    $request = HTTPRequest::instance();
    $root    = $request->get('root');
    if (!$root) {
        $root = $request->get('cvsroot');
    }

    $project_manager = ProjectManager::instance();
    $project         = $project_manager->getProjectByUnixName($root);
    if (!$project) {
        exit_no_group();
    }
    $group_id = $project->getID();

    $viewvc_version_checker = new ViewVCVersionChecker();
    $viewvc_proxy_factory   = new ViewVCProxyFactory($viewvc_version_checker);
    $viewvc_proxy           = $viewvc_proxy_factory->getViewVCProxy();
    $viewvc_proxy->displayContent($project, $request);
} else {
    exit_not_logged_in();
}
