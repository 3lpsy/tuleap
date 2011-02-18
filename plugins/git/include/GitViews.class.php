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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */

require_once('mvc/PluginViews.class.php');
require_once('GitDao.class.php');
require_once('GitBackend.class.php');
/**
 * GitViews
 */
class GitViews extends PluginViews {

    public function __construct($controller) {
        parent::__construct($controller);
        $this->groupId     = (int)$this->request->get('group_id');
        $this->projectName = ProjectManager::instance()->getProject($this->groupId)->getUnixName();        
        $this->userName    = $this->user->getName();        
    }

    public function header() {
        $title = $GLOBALS['Language']->getText('plugin_git','title');
        $GLOBALS['HTML']->header(array('title'=>$title,'group'=>$this->groupId, 'toptab'=>'plugin_git'));
    }

    public function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    protected function getText($key, $params=array() ) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }

    /**
     * HELP VIEW
     */
    public function help($topic, $params=array()) {
        if ( empty($topic) ) {
            return false;
        }
        $display = 'block';
        if ( !empty($params['display']) ) {
            $display = $params['display'];
        }
        switch( $topic ) {
                case 'init':
             ?>
<div id="help_init" class="help" style="display:<?php echo $display?>">
    <h3><?php echo $this->getText('help_reference_title'); ?></h3>
    <p>
                       <?php
                       $repoName = 'REPO_NAME';
                       if ( !empty($params['repo_name']) ) {
                           $repoName = $params['repo_name'];
                       }
                       echo '<ul>'.$this->getText('help_init_reference', array($this->_getRepositoryUrl($repoName)) ).'</ul>';
                       ?>
    </p>
    </div>
                    <?php
                    break;
                    case 'create':
                        ?>                        
                        <div id="help_create" class="help" style="display:<?php echo $display?>">
                            <h3><?php echo $this->getText('help_create_reference_title'); ?></h3>
                        <?php
                        echo '<ul>'.$this->getText('help_create_reference').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'tree':
                        ?>
                        <div id="help_tree" class="help" style="display:<?php echo $display?>">                            
                        <?php
                        echo '<ul>'.$this->getText('help_tree').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'fork':
                        ?>
                        <div id="help_fork" class="help" style="display:<?php echo $display?>">
                        <?php
                        echo '<ul>'.$this->getText('help_fork').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'addMail':
                        ?>
                        <div id="help_addMail" class="help" style="display:<?php echo $display?>">                            
                        <?php
                        echo '<ul>'.$this->getText('add_mail_msg').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                default:
                    break;
            }            
        }      

        /**
         * REPO VIEW
         */
        public function view() {
            $gitphp      = '';
            $params       = $this->getData();
            if ( empty($params['repository']) ) {
                $this->getController()->redirect('/plugins/git/?action=index&group_id='.$this->groupId);
                return false;
            }
            $repository   = $params['repository'];
            $repoId       = $repository->getId();
            $repoName     = $repository->getName();
            $initialized  = $repository->isInitialized();
            $creator      = $repository->getCreator();
            $parent       = $repository->getParent();
            $access       = $repository->getAccess();
            $description  = $repository->getDescription();
            $creatorName  = '';
            if ( !empty($creator) ) {
                $creatorName  = UserHelper::instance()->getLinkOnUserFromUserId($creator->getId());
            }
            $creationDate = html_time_ago(strtotime($repository->getCreationDate()));

            if ( $initialized ) {
                ob_start();
                $this->getView($repository);
                $gitphp = ob_get_contents();
                ob_end_clean();    
            }
            //download
            if ( $this->request->get('noheader') == 1 ) {
                die($gitphp);
            }

            echo '<br />';
            if ( !$initialized ) {
                echo '<div class="feedback_warning">'.$this->getText('help_init_reference_msg').'</div>';
                $this->help('init', array('repo_name'=>$repoName));
            }
            $this->_getBreadCrumb();
            
            // Access type
            $accessType = '<span class="plugin_git_repo_privacy" title=';
            switch ($access) {
            case GitRepository::PRIVATE_ACCESS:
                $accessType .= '"'.$this->getText('view_repo_access_private').'">';
                $accessType .= '<img src="'.util_get_image_theme('ic/lock.png').'" />';
                break;
            case GitRepository::PUBLIC_ACCESS:
                $accessType .= '"'.$this->getText('view_repo_access_public').'">';
                $accessType .= '<img src="'.util_get_image_theme('ic/lock-unlock.png').'" />';
                break;
            }
            $accessType .= '</span>';

            // Actions
            $repoActions = '<ul id="plugin_git_repository_actions">';
            if ($this->getController()->isAPermittedAction('repo_management')) {
                $repoActions .= '<li>'.$this->linkTo($this->getText('admin_repo_management'), '/plugins/git/?action=repo_management&group_id='.$this->groupId.'&repo_id='.$repoId, 'class="repo_admin"').'</li>';
            }

            if ($initialized && $this->getController()->isAPermittedAction('clone')) {
                $repoActions .= '<li>'.$this->linkTo($this->getText('admin_fork_creation_title'), '/plugins/git/?action=fork&group_id='.$this->groupId.'&repo_id='.$repoId, 'class="repo_fork"').'</li>';
            }
            $repoActions .= '</ul>';

            echo '<div id="plugin_git_reference">';
            echo '<h2>'.$accessType.$repoName.'</h2>';
            echo $repoActions;
?>
<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
    <input type="hidden" id="action" name="action" value="edit" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
<?php
            echo '<p id="plugin_git_reference_author">'. 
                $this->getText('view_repo_creator') 
                .' '. 
                $creatorName
                .' '.
                $creationDate
                .'</p>';
            ?>
    <?php
    if ( !empty($parent) ) :
    ?>
    <p id="plugin_git_repo_parent"><?php echo $this->getText('view_repo_parent');
            ?>: <span><?php echo $this->_getRepositoryPageUrl( $parent->getId(), $parent->getName() );?></span>
    </p>
    <?php
    endif;
    if (!empty($description)) {
        echo '<p id="plugin_git_description">'.$this->HTMLPurifier->purify($description, CODENDI_PURIFIER_CONVERT_HTML, $this->groupId).'</p>';
    }
    ?>
    <p id="plugin_git_clone_url"><?php echo $this->getText('view_repo_clone_url');
            ?>: <input id="plugin_git_clone_field" type="text" value="git clone <?php echo $this->_getRepositoryUrl($repoName); ?>" />
    </p>
</form>
        <?php
        echo '</div>';
        if ( $initialized ) {
            echo $gitphp;
        }
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        $access       = $repository->getAccess();
        $description  = $repository->getDescription();
        $this->repoId = $repository->getId();
        $mailPrefix   = $repository->getMailPrefix();
        echo "<br/>";
        $this->_getBreadCrumb();
        echo "<h2><b>".$this->_getRepositoryPageUrl($this->repoId, $repoName)."</b></h2>";
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
        <input type="hidden" id="action" name="action" value="edit" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
        <?php
        if ( $this->getController()->isAPermittedAction('del') && !$repository->hasChild() ) {
            echo '<div id="plugin_git_confirm_deletion"><input type="submit" name="confirm_deletion" value="'. $this->getText('admin_deletion_submit') .'" /></div>';
        }
        if ( $this->getController()->isAPermittedAction('save') ) :
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
        <p id="plugin_git_description"><?php echo $this->getText('view_repo_description');
            ?>: <textarea class="text" id="repo_desc" name="repo_desc"><?php echo $this->HTMLPurifier->purify($description, CODENDI_PURIFIER_CONVERT_HTML, $this->groupId);
        ?></textarea>
        </p>
        <?php
        $public  = '';
        $private = '';
        $checked = 'checked="checked"';
        if ( $access == GitRepository::PRIVATE_ACCESS ) {
            $private = $checked;
            ?> <input type="hidden" id="action" name="action" value="edit" /> <?php
        } else if ( $access == GitRepository::PUBLIC_ACCESS ) {
            $public  = $checked;
            ?> <input type="hidden" id="action" name="action" value="confirm_private" /> <?php
        }
        ?>
        <p id="plugin_git_access"><?php echo $this->getText('view_repo_access');?>: <span><input type="radio" name="repo_access" value="private" <?php echo $private?>/><?php echo $this->getText('view_repo_access_private'); ?><input type="radio" name="repo_access" value="public"  <?php echo $public?>/>Public</span>
            <input type="submit" name="save" value="<?php echo $this->getText('admin_save_submit');?>" />
        </p>
        </form>
        <?php
        endif;
        // form to update notification mail prefix
        $this->_mailPrefixForm($mailPrefix);
        // form to add email addresses (mailing list) or a user to notify
        $this->_addMailForm();
        // show the list of mails to notify
        $this->_listOfMails();
    }

    /**
     * FORK VIEW
     */
    public function fork() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        echo "<br/>";
        $this->_getBreadCrumb();
        echo "<h2><b>".$this->_getRepositoryPageUrl($repoId, $repoName)."</b></h2>";
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
        <input type="hidden" id="action" name="action" value="edit" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
        <?php
        if ( $initialized && $this->getController()->isAPermittedAction('clone') ) :
        ?>
            <p id="plugin_git_fork_form">
                <input type="hidden" id="parent_id" name="parent_id" value="<?php echo $repoId?>">
                <label for="repo_name"><?php echo $this->getText('admin_fork_creation_input_name');
        ?>:     </label>
                <input type="text" id="repo_name" name="repo_name" value="" /><input type="submit" name="clone" value="<?php echo $this->getText('admin_fork_creation_submit');?>" />
                <a href="#" onclick="$('help_fork').toggle();"> [?]</a>
            </p>
        </form>
        <?php
        endif;
        $this->help('fork', array('display'=>'none'));
    }

    /**
     * CONFIRM PRIVATE
     */
    public function confirmPrivate() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        $mails        = $params['mails'];
        if ( $initialized && $this->getController()->isAPermittedAction('save') ) :
        ?>
        <div class="confirm">
        <h3><?php echo $this->getText('set_private_confirm'); ?></h3>
        <form id="confirm_private" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId; ?>" >
        <input type="hidden" id="action" name="action" value="set_private" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo $this->getText('yes') ?>"/><span><input type="button" value="<?php echo $this->getText('no')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->groupId;?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
        <h3><?php echo $this->getText('set_private_mails'); ?></h3>
    <table>
        <?php
        $i = 0;
        foreach ($mails as $mail) {
            echo '<tr class="'.html_get_alt_row_color(++$i).'">';
            echo '<td>'.$mail.'</td>';
            echo '</tr>';
        }
        ?>
    </table>
    </div>
        <?php
        endif;
    }

    /**
     * TREE VIEW
     */
    public function index() {        
        $params = $this->getData();
        $this->_getBreadCrumb();
        $this->_tree($params);
        if ( $this->getController()->isAPermittedAction('add') ) {
            $this->_createForm();
        }
    }

    public function getView($repository) {
        require_once('../../../src/common/include/Codendi_HTMLPurifier.class.php');        
        if ( empty($_REQUEST['a']) )  {
            $_REQUEST['a'] = 'summary';
        }
        set_time_limit(300);
        $_GET['a'] = $_REQUEST['a'];        
        $_REQUEST['group_id']      = $this->groupId;
        $_REQUEST['repo_id']       = $repository->getId();
        $_REQUEST['repo_name']     = $repository->getName();
        $_GET['p']                 = $_REQUEST['repo_name'].'.git';
        $_REQUEST['repo_path']     = $repository->getPath();
	$_REQUEST['project_dir']   = $repository->getProject()->getUnixName();
        $_REQUEST['git_root_path'] = GitBackend::GIT_ROOT_PATH;
        $_REQUEST['action']        = 'view';
        if ( empty($_REQUEST['noheader']) ) {
            //echo '<hr>';
            echo '<div id="gitphp">';
        }
        include( dirname(__FILE__).'/../gitphp/index.php' );
        if ( empty($_REQUEST['noheader']) ) {
            echo '</div>';
        }
    }
    /**
     * CONFIRM_DELETION
     * @todo make a generic function ?
     * @param <type> $params
     * @return <type>
     */
    public function confirm_deletion( $params ) {
        if (  empty($params['repo_id']) ) {
            return false;
        }        
        $repoId = $params['repo_id'];
        if ( !$this->getController()->isAPermittedAction('del') ) {
            return false;
        }
        ?>
    <div class="confirm">
        <form id="confirm_deletion" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId; ?>" >
        <input type="hidden" id="action" name="action" value="del" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo $this->getText('yes') ?>"/><span><input type="button" value="<?php echo $this->getText('no')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->groupId;?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
    </div>
        <?php
    }

    /**
     * CREATE REF FORM
     */
    protected function _createForm() {
        ?>
<h3><?php echo $this->getText('admin_reference_creation_title');
        ?><a href="#" onclick="$('help_create').toggle();$('help_init').toggle()"> [?]</a></h3>
<form id="addRepository" action="/plugins/git/?group_id=<?php echo $this->groupId ?>" method="POST">
    <input type="hidden" id="action" name="action" value="add" />
    <table>
        <tr>
            <td><label for="repo_name"><?php echo $this->getText('admin_reference_creation_input_name');
        ?></label></td>
            <td><input id="repo_name" name="repo_name" class="" type="text" value=""/></td>
            <td rowspan="2"><input type="submit" id="repo_add" name="repo_add" value="<?php echo $this->getText('admin_reference_creation_submit')?>"></td>
        </tr>
    </table>    
</form>
        <?php
        $this->help('create', array('display'=>'none')) ;
        $this->help('init', array('display'=>'none')) ;
    }

    /**
     * CREATE NOTIFICATION FORM
     */
    protected function _mailPrefixForm($mailPrefix) {
        ?>
<h3><?php echo $this->getText('mail_prefix_title'); ?></h3>
<form id="mail_prefix_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="mail_prefix" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->groupId ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repoId ?>" />
    <table>
        <tr>
            <td class="plugin_git_first_col" ><label for="mail_prefix_label"><?php echo $this->getText('mail_prefix');
        ?></label></td>
            <td><input name="mail_prefix" class="plugin_git_mail_prefix" type="text" value="<?php echo $mailPrefix; ?>" /></td>
            <td rowspan="2"><input type="submit" id="mail_prefix_submit" name="mail_prefix_submit" value="<?php echo $this->getText('mail_prefix_submit')?>"></td>
        </tr>
    </table>
</form>
        <?php
    }

    /**
     * MAIL FORM
     */
    protected function _addMailForm() {
        ?>
<h3><?php echo $this->getText('add_mail_title'); ?></h3>
<form id="add_mail_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="add_mail" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->groupId ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repoId ?>" />
    <table>
        <tr>
            <td class="plugin_git_first_col" ><label for="add_mail_label"><?php echo $this->getText('add_mail');?>
                <a href="#" onclick="$('help_addMail').toggle();"> [?]</a></label></td>
            <td><textarea id="add_mail" name="add_mail" class="plugin_git_add_mail"></textarea></td>
            <td rowspan="2"><input type="submit" id="add_mail_submit" name="add_mail_submit" value="<?php echo $this->getText('add_mail_submit')?>"></td>
        </tr>
    </table>
</form>
        <?php
        $this->help('addMail', array('display'=>'none') );
        $js = "new UserAutoCompleter('add_mail', '".util_get_dir_image_theme()."', true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
    }

    /**
     * LIST OF MAILS TO NOTIFY
     */
    protected function _listOfMails() {
        $r = new GitRepository();
        $r->setId($this->repoId);
        $r->load();
        $mails = $r->getNotifiedMails();
        ?>
<h3><?php echo $this->getText('notified_mails_title'); ?></h3>
    <?php if (!empty($mails)) {?>
<form id="add_user_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="remove_mail" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->groupId ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repoId ?>" />
    <table>
        <?php
        $i = 0;
        foreach ($mails as $mail) {
            echo '<tr class="'.html_get_alt_row_color(++$i).'">';
            echo '<td>'.$mail.'</td>';
            echo '<td>';
            echo '<button type="submit" style="background:white; border:0;" name="mail" value="'.$mail.'" ><img src="'.util_get_dir_image_theme().'/ic/cross.png"></button>';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        ?>
    </table>
</form>
        <?php
        } else {
?>
<h4><?php echo $this->getText('add_mail_existing'); ?> </h4>
<?php
}
    }

    /**
     * @todo make a breadcrumb out of the repository hierarchie ?
     */
    protected function _getBreadCrumb() {
        echo $this->linkTo( '<b>'.$this->getText('bread_crumb_home').'</b>', '/plugins/git/?group_id='.$this->groupId, 'class=""');
        echo ' | ';
        echo $this->linkTo( '<b>'.$this->getText('bread_crumb_help').'</b>', 'javascript:help_window(\'/documentation/user_guide/html/'.$this->user->getLocale().'/VersionControlWithGit.html\')');
    }
    
    /**
     * @todo several cases ssh, http ...
     * @param <type> $repositoryName
     * @return <type>
     */
    protected function _getRepositoryUrl($repositoryName) {
        $serverName  = $_SERVER['SERVER_NAME'];
        return  $this->userName.'@'.$serverName.':/gitroot/'.$this->projectName.'/'.$repositoryName.'.git';
    }

    protected function _getRepositoryPageUrl($repoId, $repoName) {
        return $this->linkTo($repoName,'/plugins/git/index.php/'.$this->groupId.'/view/'.$repoId.'/');
    }

    /**
     * TREE SUBVIEW
     */
    protected function _tree( $params=array() ) {        
        if ( empty($params) ) {
            $params = $this->getData();
        }
        if ( !empty($params['repository_list']) ) {
            echo '<h3>'.$this->getText('tree_title_available_repo').' <a href="#" onclick="$(\'help_tree\').toggle();"> [?]</a></h3>';
            $this->help('tree', array('display'=>'none') );
            echo '<ul>';
            $this->_displayRepositoryList($params['repository_list']);
            echo '</ul>';
        }
        else {
            echo "<h3>".$this->getText('tree_msg_no_available_repo')."</h3>";
        }        
    }

    protected function _displayRepositoryList($data) {
        $parentChildrenAssoc = array();
        foreach ( $data as $repoId=>$repoData ) {
            if ( !empty($repoData[GitDao::REPOSITORY_PARENT]) ) {
                $parentId = $repoData[GitDao::REPOSITORY_PARENT];
                $parentChildrenAssoc[$parentId][] = $repoData[GitDao::REPOSITORY_ID];
            }
            else {
                if ( !isset($parentChildrenAssoc[0][$repoId]) ) {
                    $parentChildrenAssoc[0][] = $repoId;
                }
            }
        }
        $this->_makeRepositoryTree($parentChildrenAssoc, 0, $data);
    }

    protected function _makeRepositoryTree(&$flatTree, $currentId, $data) {
        foreach ( $flatTree[$currentId] as $childId ) {
            $repoId   = $data[$childId][GitDao::REPOSITORY_ID];
            $repoName = $data[$childId][GitDao::REPOSITORY_NAME];
            $repoDesc = $data[$childId][GitDao::REPOSITORY_DESCRIPTION];
            $delDate  = $data[$childId][GitDao::REPOSITORY_DELETION_DATE];
            $isInit   = $data[$childId][GitDao::REPOSITORY_IS_INITIALIZED];
            $access   = $data[$childId][GitDao::REPOSITORY_ACCESS];
            //needs to be checked on filesystem (GitDao::getRepositoryList do not check)
            //TODO move this code to GitBackend and write a new getRepositoryList function ?
            if ( $isInit == 0 ) {
                $r = new GitRepository();
                $r->setId($repoId);
                $r->load();
                $isInit = $r->isInitialized();
            }
            //we do not want to display deleted repository
            if ( $delDate != '0000-00-00 00:00:00' ) {
                continue;
            }
            if ($isInit == 0) {
                echo '<li>'.$this->_getRepositoryPageUrl($repoId, $repoName). ' ('.$this->getText('view_repo_not_initialized').') </li>';
            } else {
                // Access type
                $accessType = '<span class="plugin_git_repo_privacy" title=';
                switch ($access) {
                    case GitRepository::PRIVATE_ACCESS:
                        $accessType .= '"'.$this->getText('view_repo_access_private').'">';
                        $accessType .= '<img src="'.util_get_image_theme('ic/lock.png').'" />';
                        break;
                    case GitRepository::PUBLIC_ACCESS:
                        $accessType .= '"'.$this->getText('view_repo_access_public').'">';
                        $accessType .= '<img src="'.util_get_image_theme('ic/lock-unlock.png').'" />';
                        break;
                }
                $accessType .= '</span>';
                echo '<li>'.$accessType.' '.$this->_getRepositoryPageUrl($repoId, $repoName). '</li>';
            }
            if ( !empty($flatTree[$childId]) ) {
                echo '<ul>';
                $this->_makeRepositoryTree($flatTree, $childId, $data);
                echo '</ul>';
            }
        }
    }
}

?>
