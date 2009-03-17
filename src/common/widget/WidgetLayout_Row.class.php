<?php
/**
* WidgetLayout_Row
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class WidgetLayout_Row {
    var $id;
    var $rank;
    var $columns;
    var $layout;
    
    function WidgetLayout_Row($id, $rank) {
        $this->id      = $id;
        $this->rank    = $rank;
        $this->columns = array();
    }
    function setLayout(&$layout) {
        $this->layout =& $layout;
    }
    function add(&$c) {
        $this->columns[] =& $c;
        $c->setRow($this);
    }
    function display($readonly, $owner_id, $owner_type) {
        echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">';
        echo '<tr style="vertical-align:top;">';
        $last = count($this->columns) - 1;
        $i = 0;
        foreach($this->columns as $key => $nop) {
            $this->columns[$key]->display($readonly, $owner_id, $owner_type, $is_last = ($i++ == $last));
        }
        echo '</tr>';
        echo '</table>';
    }
    function getColumnIds() {
        $ret = array();
        foreach($this->columns as $key => $nop) {
            $ret[] = $this->columns[$key]->getColumnId();
        }
        return $ret;
    }
}
?>
