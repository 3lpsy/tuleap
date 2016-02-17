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

namespace Tuleap\OpenIDConnectClient\Login;

use BackendLogger;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDataAccessException;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\FlowResponse;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMapping;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;
use Exception;
use User_LoginException;
use SessionNotCreatedException;
use UserNotActiveException;
use UserManager;

class Controller {
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProviderManager
     */
    private $provider_manager;

    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    /**
     * @var UnlinkedAccountManager
     */
    private $unlinked_account_manager;

    /**
     * @var Flow
     */
    private $flow;

    public function __construct(
        UserManager $user_manager,
        ProviderManager $provider_manager,
        UserMappingManager $user_mapping_manager,
        UnlinkedAccountManager $unlinked_account_manager,
        Flow $flow
    ) {
        $this->user_manager             = $user_manager;
        $this->provider_manager         = $provider_manager;
        $this->user_mapping_manager     = $user_mapping_manager;
        $this->unlinked_account_manager = $unlinked_account_manager;
        $this->flow                     = $flow;
    }

    public function login($return_to) {
        require_once('account.php');
        $this->checkIfUserAlreadyLogged($return_to);

        try {
            $flow_response = $this->flow->process();
        } catch (Exception $ex) {
            $this->redirectToLoginPageAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'invalid_request')
            );
        }

        $provider          = $flow_response->getProvider();
        $user_informations = $flow_response->getUserInformations();
        try {
            $user_mapping = $this->user_mapping_manager->getByProviderAndIdentifier(
                $provider,
                $user_informations['id']
            );
            $this->openSession($user_mapping, $flow_response->getReturnTo());
        } catch (UserMappingNotFoundException $ex) {
            $this->redirectToLinkAnUnknowAccount($flow_response);
        }
    }

    private function checkIfUserAlreadyLogged($return_to) {
        $user = $this->user_manager->getCurrentUser();
        if($user->isLoggedIn()) {
            \account_redirect_after_login($return_to);
        }
    }

    private function openSession(UserMapping $user_mapping, $return_to) {
        $user = $this->user_manager->getUserById($user_mapping->getUserId());
        try {
            $this->user_manager->openSessionForUser($user);
        } catch (User_LoginException $ex) {
            $this->redirectToLoginPageAfterFailure($ex->getMessage());
        } catch (UserNotActiveException $ex) {
            $this->redirectToLoginPageAfterFailure($ex->getMessage());
        } catch (SessionNotCreatedException $ex) {
            $this->redirectToLoginPageAfterFailure($ex->getMessage());
        }
        \account_redirect_after_login($return_to);
    }

    private function redirectToLoginPageAfterFailure($message) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );
        $GLOBALS['Response']->redirect('/account/login.php');
    }

    private function redirectToLinkAnUnknowAccount(FlowResponse $flow_response) {
        $provider          = $flow_response->getProvider();
        $user_informations = $flow_response->getUserInformations();
        try {
            $unlinked_account  = $this->unlinked_account_manager->create($provider->getId(), $user_informations['id']);
        } catch (UnlinkedAccountDataAccessException $ex) {
            $this->redirectToLoginPageAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'unexpected_error')
            );
        }
        $GLOBALS['Response']->redirect(
            OPENIDCONNECTCLIENT_BASE_URL . '/?' . http_build_query(
                array(
                    'action'    => 'link',
                    'link_id'   => $unlinked_account->getId(),
                    'return_to' => $flow_response->getReturnTo()
                )
            )
        );
    }
}