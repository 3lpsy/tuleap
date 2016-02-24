<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Svn\Hooks;

use PFUser;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Commit\CannotFindSVNCommitInfoException;
use Tuleap\Svn\Admin\MailReference;
use Tuleap\Svn\Commit\CommitInfoEnhancer;
use Tuleap\Svn\Commit\CommitInfo;
use ReferenceManager;
use MailBuilder;
use MailEnhancer;
use PluginManager;
use ForgeConfig;
use Notification;
use UserManager;

class PostCommit {
    private $reference_manager;
    private $repository_manager;
    private $mail_header_manager;
    private $mail_notification_manager;
    private $plugin_manager;
    private $commit_info_enhancer;
    private $mail_builder;
    private $user_manager;

    public function __construct(
        ReferenceManager $reference_manager,
        RepositoryManager $repository_manager,
        MailHeaderManager $mail_header_manager,
        MailNotificationManager $mail_notification_manager,
        PluginManager $plugin_manager,
        MailBuilder $mail_builder,
        CommitInfoEnhancer $commit_info_enhancer,
        UserManager $user_manager
    ) {
        $this->reference_manager         = $reference_manager;
        $this->repository_manager        = $repository_manager;
        $this->mail_header_manager       = $mail_header_manager;
        $this->mail_notification_manager = $mail_notification_manager;
        $this->plugin_manager            = $plugin_manager;
        $this->commit_info_enhancer      = $commit_info_enhancer;
        $this->mail_builder              = $mail_builder;
        $this->user_manager              = $user_manager;
    }

    public function sendMail($repository_path, $new_revision, $old_revision) {
        $repository = $this->repository_manager->getRepositoryFromSystemPath($repository_path);
        $goto_link  = $repository->getSvnDomain() . $this->getGotoLink('rev', $new_revision, $repository);

        $mail_enhancer = new MailEnhancer();

        $this->commit_info_enhancer->enhance($repository, $new_revision);

        $notified_mail = $this->getNotifiedMails($repository);
        $subject       = $this->getSubject($repository, $new_revision);
        $committer     = $this->getCommitter($this->commit_info_enhancer->getCommitInfo());
        $body          = $this->createMailBody(
            $committer,
            $goto_link,
            $repository,
            $new_revision,
            $old_revision,
            $repository_path
        );
        $this->setFrom($mail_enhancer, $committer);

        $notification = new Notification($notified_mail, $subject, '', $body, $goto_link, 'Svn');
        return $this->mail_builder->buildAndSendEmail($repository->getProject(), $notification, $mail_enhancer);
    }

    /**
     * @return string
     */
    private function getGotoLink($keyword, $revision_id, Repository $repository) {
        $link = new MailReference($keyword, $revision_id, $repository);
        return $link->getLink();
    }

    /**
     * @return PFUser
     * @throws CannotFindSVNCommitInfoException
     */
    private function getCommitter(CommitInfo $commit_info) {
        $user_name = $commit_info->getUser();
        if (ForgeConfig::get('sys_auth_type') === ForgeConfig::AUTH_TYPE_LDAP) {
            $user = $this->user_manager->findUser($user_name);
        } else {
            $user = $this->user_manager->getUserByUserName($user_name);
        }

        if ($user === null) {
            throw new CannotFindSVNCommitInfoException();
        }

        return $user;
    }

    private function createMailBody(
        PFUser $committer,
        $goto_link,
        Repository $repository,
        $new_revision,
        $old_revision,
        $system_path
    ) {
        $commit_info = $this->commit_info_enhancer->getCommitInfo();

        $body = "SVN Repository: ".$system_path;
        $body .= "\n";
        $body .= "Changes by: ". $committer->getName() ." <". $committer->getEmail() ."> on ".$commit_info->getDate()."\n";
        $body .= "New Revision:   $new_revision  $goto_link \n";
        $body .= "\nLog message: \n".$commit_info->getCommitMessage(). "\n";
        $body .= $this->listFiles($commit_info->getUpdatedFiles(), "Updated");
        $body .= $this->listFiles($commit_info->getAddedFiles()  , "Added");
        $body .= $this->listFiles($commit_info->getDeletedFiles(), "Deleted");

        if ($commit_info->hasChangedFiles()) {
            $body .= "\n\nSource code changes: \n";
            foreach ($commit_info->getAllFiles() as $file) {
                $body .= $repository->getSvnDomain() . "/plugins/svn/index.php/".trim($file)."?roottype=svn&root=".$repository->getFullName()."&r1=$old_revision&r2=$new_revision\n";
            }
        }

        return $body;
    }

    private function listFiles(array $list_files, $type) {
        $text = '';
        if ( count($list_files) > 0) {
            $text = "$type files : \n";
            foreach ($list_files as $file) {
                 $text .= $file . "\n";
            }
        }

        return $text;
    }

    private function getSubject(Repository $repository, $revision) {
        $subject = $this->mail_header_manager->getByRepository($repository)->getHeader();
        if ($subject !== '') {
            $subject .= ' ';
        }
        $subject .= "r$revision ";

        $commit_info = $this->commit_info_enhancer->getCommitInfo();
        if (count($commit_info->getDirectories()) > 3) {
            $directories = $commit_info->getDirectories();
            $subject .= $directories[0] . " ". $directories[1] . " ". $directories[2] . "...";
        } else {
            $subject .= join(' ', $commit_info->getDirectories());
        }

        return $subject;
    }

    private function getNotifiedMails(Repository $repository) {
        $notified_mail = array();

        $commit_info = $this->commit_info_enhancer->getCommitInfo();
        foreach ($commit_info->getChangedDirectories() as $path) {
            $mailing_list = $this->mail_notification_manager->getByPath($repository, $path);
            foreach ($mailing_list as $mail) {
                    $notified_mail[] = $mail->getNotifiedMails();
            }
        }

        return $notified_mail;
    }

    private function setFrom(MailEnhancer $mail_enhancer, PFUser $user) {
        $mail_enhancer->addHeader('From', $user->getEmail());
    }
}
