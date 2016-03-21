<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn;

use HTTPRequest;
use Tuleap\Svn\Explorer\ExplorerController;
use Tuleap\Svn\Explorer\RepositoryDisplayController;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use Tuleap\Svn\Admin\MailNotificationController;
use Tuleap\Svn\AccessControl\AccessControlController;
use Tuleap\Svn\Admin\AdminController;
use Tuleap\Svn\AuthFile\AccessFileHistoryManager;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailnotificationManager;
use Tuleap\Svn\Admin\ImmutableTagController;
use ProjectManager;
use Project;
use ForgeConfig;
use Feedback;

class SvnRouter {

    /** @var RepositoryDisplayController */
    private $display_controller;

    /** @var ExplorerController */
    private $explorer_controller;

    /** @var MailNotificationController */
    private $admin_controller;

    /** @var AccessControlController */
    private $access_control_controller;

    /** @var RepositoryManager */
    private $repository_manager;

    /** @var ImmutableTagController */
    private $immutable_tag_controller;

    public function __construct(
        RepositoryManager $repository_manager,
        AccessControlController $access_control_controller,
        AdminController $notification_controller,
        ExplorerController $explorer_controller,
        RepositoryDisplayController $display_controller,
        ImmutableTagController $immutable_tag_controller
    ) {
        $this->repository_manager        = $repository_manager;
        $this->access_control_controller = $access_control_controller;
        $this->admin_controller          = $notification_controller;
        $this->explorer_controller       = $explorer_controller;
        $this->display_controller        = $display_controller;
        $this->immutable_tag_controller  = $immutable_tag_controller;
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {
        try {
            $this->useAViewVcRoadIfRootValid($request);

            if (! $request->get('action')) {
                $this->useDefaultRoute($request);
                return;
            }

            $action = $request->get('action');

            switch ($action) {
                case "create-repository":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->explorer_controller->createRepository($this->getService($request), $request);
                    break;
                case "display-repository":
                    $this->display_controller->displayRepository($this->getService($request), $request);
                    break;
                case "settings":
                case "display-mail-notification":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->displayMailNotification($this->getService($request), $request);
                    break;
                case "save-mail-header":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->saveMailHeader($request);
                    break;
                case "create-mailing-lists":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->createMailingList($request);
                    break;
                case "delete-mailing-list":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->deleteMailingList($request);
                    break;
                case "save-hooks-config":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->updateHooksConfig($this->getService($request), $request);
                    break;
                case "hooks-config":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->displayHooksConfig($this->getService($request), $request);
                    break;
                case "access-control":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->displayAuthFile($this->getService($request), $request);
                    break;
                case "save-access-file":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->saveAuthFile($this->getService($request), $request);
                    break;
                case "display-archived-version":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->displayArchivedVersion($request);
                    break;
                case "display-immutable-tag":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->immutable_tag_controller->displayImmutableTag($this->getService($request), $request);
                    break;
                case "save-immutable-tag":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->immutable_tag_controller->saveImmutableTag($this->getService($request), $request);
                    break;

                default:
                    $this->useDefaultRoute($request);
                    break;
            }
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_svn','find_error'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL .'/?group_id='. $request->get('group_id'));
        } catch (UserCannotAdministrateRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'perm_denied'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL .'/?group_id='. $request->get('group_id'));
        }
    }


    private function checkUserCanAdministrateARepository(HTTPRequest $request) {
        if (! $request->getProject()->userIsAdmin($request->getCurrentUser())) {
            throw new UserCannotAdministrateRepositoryException();
        }
    }

    private function useAViewVcRoadIfRootValid(HTTPRequest $request) {
        if ($request->get('root')) {
            $repository = $this->repository_manager->getRepositoryFromPublicPath($request->get('root'));

            $request->set("group_id", $repository->getProject()->getId());
            $request->set("repo_id", $repository->getId());

            $this->display_controller->displayRepository($this->getService($request), $request);
            return;
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request) {
        $this->explorer_controller->index($this->getService($request), $request);
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     * @param HTTPRequest $request
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request) {
        return $request->getProject()->getService('plugin_svn');
    }
}
