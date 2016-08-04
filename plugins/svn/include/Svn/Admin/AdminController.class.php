<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Admin;

use SystemEventManager;
use Tuleap\Svn\ServiceSvn;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\HookConfig;
use Project;
use HTTPRequest;
use Valid_String;
use Valid_Array;
use CSRFSynchronizerToken;
use Logger;

class AdminController
{

    private $repository_manager;
    private $mail_header_manager;
    private $mail_notification_manager;

    public function __construct(
        MailHeaderManager $mail_header_manager,
        RepositoryManager $repository_manager,
        MailNotificationManager $mail_notification_manager,
        Logger $logger
    ) {
        $this->repository_manager        = $repository_manager;
        $this->mail_header_manager       = $mail_header_manager;
        $this->mail_notification_manager = $mail_notification_manager;
        $this->logger                    = $logger;
    }

    private function generateToken(Project $project, Repository $repository) {
        return new CSRFSynchronizerToken(SVN_BASE_URL."/?group_id=".$project->getid(). '&repo_id='.$repository->getId()."&action=display-mail-notification");
    }

    public function displayMailNotification(ServiceSvn $service, HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);

        $mail_header           = $this->mail_header_manager->getByRepository($repository);
        $notifications_details = $this->mail_notification_manager->getByRepository($repository);

        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $service->renderInPage(
            $request,
            $repository->getName() .' – '. $title,
            'admin/mail_notification',
            new MailNotificationPresenter(
                $repository,
                $request->getProject(),
                $token,
                $title,
                $mail_header,
                $notifications_details
            )
        );
    }

    public function saveMailHeader(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $repo_name = $request->get("form_mailing_header");
        $vHeader = new Valid_String('form_mailing_header');
        if($request->valid($vHeader)) {
            $mail_header = new MailHeader($repository, $repo_name);
            try {
                $this->mail_header_manager->create($mail_header);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_success'));
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

    public function createMailingList(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $valid_path    = new Valid_String('form_path');
        $form_path     = $request->get('form_path');
        $list_mails    = new MailReceivedFromUserExtractor($request->get('form_mailing_list'));
        $valid_mails   = join(', ', $list_mails->getValidAdresses());
        $invalid_mails = join(', ', $list_mails->getInvalidAdresses());

        $is_path_valid = $request->valid($valid_path) && $form_path !== '';
        if (! $is_path_valid) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'update_path_error')
            );
        }
        if (! empty($invalid_mails)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'upd_email_bad_adr', $invalid_mails)
            );
        }
        if (empty($valid_mails)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'upd_email_fail')
            );
        }

        if(! empty($valid_mails) && $is_path_valid) {
            $mail_notification = new MailNotification($repository, $valid_mails, $form_path);
            try {
                $this->mail_notification_manager->create($mail_notification);
                $GLOBALS['Response']->addFeedback(
                    'info',
                    $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'upd_email_success')
                );
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    $GLOBALS['Language']->getText('plugin_svn_admin_notification', 'upd_email_error')
                );
            }
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

    public function deleteMailingList(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $vPathToDelete = new Valid_Array('paths_to_delete');
        if($request->valid($vPathToDelete)) {
            $PathsToDelete = $request->get('paths_to_delete');
            try {
                $this->mail_notification_manager->removeSvnNotification($repository, $PathsToDelete);
            } catch (CannotDeleteMailNotificationException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','delete_error'));
            }
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

    public function updateHooksConfig(ServiceSvn $service, HTTPRequest $request) {
        $hook_config = array(
            HookConfig::MANDATORY_REFERENCE => (bool)
                $request->get("pre_commit_must_contain_reference"),
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => (bool)
                $request->get("allow_commit_message_changes")
        );
        $this->repository_manager->updateHookConfig($request->get('repo_id'), $hook_config);

        return $this->displayHooksConfig($service, $request);
    }

    public function displayHooksConfig(ServiceSvn $service, HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());
        $hook_config = $this->repository_manager->getHookConfig($repository);


        $token = $this->generateToken($request->getProject(), $repository);
        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $service->renderInPage(
            $request,
            $repository->getName() .' – '. $title,
            'admin/hooks_config',
            new HooksConfigurationPresenter(
                $repository,
                $request->getProject(),
                $token,
                $title,
                $hook_config->getHookConfig(HookConfig::MANDATORY_REFERENCE),
                $hook_config->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)
            )
        );
    }

    public function displayRepositoryDelete(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());
        $title      = $GLOBALS['Language']->getText('global', 'Administration');

        $token = $this->generateTokenDeletion($request->getProject(), $repository);

        $service->renderInPage(
            $request,
            $repository->getName() .' – '. $title,
            'admin/repository_delete',
            new RepositoryDeletePresenter(
                $repository,
                $request->getProject(),
                $title,
                $token
            )
        );
    }

    public function deleteRepository(HTTPRequest $request)
    {
        $project       = $request->getProject();
        $project_id    = $project->getID();
        $repository_id = $request->get('repo_id');

        if ($project_id === null || $repository_id === null || $repository_id === false || $project_id === false) {
            $GLOBALS['Response']->addFeedback('error', 'actions_params_error');
            return false;
        }

        $repository = $this->repository_manager->getById($repository_id, $project);
        if ($repository !== null) {
            $token = $this->generateTokenDeletion($project, $repository);
            $token->check();

            if ($repository->canBeDeleted()) {
                $this->repository_manager->queueRepositoryDeletion($repository, \SystemEventManager::instance());

                $GLOBALS['Response']->addFeedback(
                    'info',
                    $GLOBALS['Language']->getText('plugin_svn', 'actions_delete_process', array($repository->getFullName()))
                );
                $GLOBALS['Response']->addFeedback(
                    'info',
                    $GLOBALS['Language']->getText(
                        'plugin_svn',
                        'actions_delete_backup',
                        array(
                            $repository->getFullName(),
                            $repository->getSystemBackupPath()
                        )
                    )
                );
                $GLOBALS['Response']->addFeedback(
                    'info',
                    $GLOBALS['Language']->getText('plugin_svn', 'feedback_event_delete', array($repository->getFullName()))
                );
            } else {
                $this->redirect($project_id);
                return false;
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn', 'actions_repo_not_found'));
        }
        $this->redirect($project_id);
    }

    private function generateTokenDeletion(Project $project, Repository $repository)
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL.'/?'. http_build_query(
            array(
                'group_id' => $project->getID(),
                'repo_id'  => $repository->getId(),
                'action'   => 'delete-repository'
            )
        ));
    }

    private function redirect($project_id)
    {
        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $project_id)
        ));
    }
}
