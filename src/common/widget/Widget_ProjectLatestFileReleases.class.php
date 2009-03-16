<?php

require_once('Widget.class.php');

/**
* Widget_ProjectLatestFileReleases
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestFileReleases extends Widget {
    var $content;
    function Widget_ProjectLatestFileReleases() {
        $this->Widget('projectlatestfilereleases');
        $request =& HTTPRequest::instance();
        $project =& project_get_object($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            $this->content = $project->services['file']->getSummaryPageContent();
        }
    }
    function getTitle() {
        return $this->content['title'];
    }
    function getContent() {
        return $this->content['content'];
    }
    function isAvailable() {
        return isset($this->content['title']);
    }
    function canBeUsedByProject(&$project) {
        return $project->usesFile();
    }
    
    function getCategory() {
        return 'frs';
    }
}
?>