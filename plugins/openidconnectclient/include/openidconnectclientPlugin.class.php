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

use Tuleap\OpenIDConnectClient\AccountLinker\RegisterPresenter;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDao;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\AccountLinker;
use Tuleap\OpenIDConnectClient\AdminRouter;
use Tuleap\OpenIDConnectClient\Administration\IconPresenterFactory;
use Tuleap\OpenIDConnectClient\Administration\ColorPresenterFactory;
use Tuleap\OpenIDConnectClient\Authentication\AuthorizationDispatcher;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\IDTokenVerifier;
use Tuleap\OpenIDConnectClient\Authentication\StateFactory;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\StateStorage;
use Tuleap\OpenIDConnectClient\Authentication\Uri\Generator;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Login;
use Tuleap\OpenIDConnectClient\Provider\ProviderDao;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Router;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserPreferencesPresenter;
use Tuleap\OpenIDConnectClient\UserMapping;
use Tuleap\OpenIDConnectClient\Administration;
use Zend\Loader\AutoloaderFactory;

require_once('constants.php');

class openidconnectclientPlugin extends Plugin {
    public function __construct($id) {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(Event::LOGIN_ADDITIONAL_CONNECTOR);
        $this->addHook('before_register');
        $this->addHook(Event::USER_REGISTER_ADDITIONAL_FIELD);
        $this->addHook(Event::AFTER_USER_REGISTRATION);
        $this->addHook('anonymous_access_to_script_allowed');
        $this->addHook('javascript_file');
        $this->addHook('cssfile');
        $this->addHook(Event::MANAGE_THIRD_PARTY_APPS);
        $this->addHook('site_admin_option_hook');
        $this->addHook(Event::IS_IN_SITEADMIN);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
    }

    /**
     * @return OpenIDConnectClientPluginInfo
     */
    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'OpenIDConnectClientPluginInfo')) {
            $this->pluginInfo = new OpenIDConnectClientPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function anonymous_access_to_script_allowed($params) {
        if (strpos($params['script_name'], $this->getPluginPath()) === 0) {
            $params['anonymous_allowed'] = true;
        }
    }

    public function javascript_file($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/open-id-connect-client.js"></script>';
        }
    }

    public function cssfile() {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0 || strpos($_SERVER['REQUEST_URI'], '/plugins/openidconnectclient') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0 || strpos($_SERVER['REQUEST_URI'], '/plugins/openidconnectclient') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    private function loadLibrary() {
        AutoloaderFactory::factory(
            array(
                'Zend\Loader\StandardAutoloader' => array(
                    'namespaces' => array(
                        'InoOicClient' => '/usr/share/php/InoOicClient/'
                    )
                )
            )
        );
    }

    /**
     * @return Flow
     */
    private function getFlow(ProviderManager $provider_manager)
    {
        $state_manager     = new StateManager(
            new StateStorage(),
            new StateFactory(new RandomNumberGenerator())
        );
        $id_token_verifier = new IDTokenVerifier();
        $uri_generator     = new Generator();
        $flow              = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager, $uri_generator),
            $provider_manager,
            $id_token_verifier
        );
        return $flow;
    }

    /**
     * @return bool
     */
    private function canPluginAuthenticateUser() {
        return ForgeConfig::get('sys_auth_type') !== 'ldap';
    }

    public function login_additional_connector(array $params) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        if(! $params['is_secure']) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'only_https_possible')
            );
            return;
        }
        $this->loadLibrary();

        $provider_manager                  = new ProviderManager(new ProviderDao());
        $flow                              = $this->getFlow($provider_manager);
        $login_connector_presenter_builder = new ConnectorPresenterBuilder($provider_manager, $flow);
        $login_connector_presenter         = $login_connector_presenter_builder->getLoginConnectorPresenter(
            $params['return_to']
        );

        $renderer                        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $params['additional_connector'] .= $renderer->renderToString('login_connector', $login_connector_presenter);
    }

    public function before_register(array $params) {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($this->isUserRegistrationWithOpenIDConnectPossible($params['is_registration_confirmation'], $link_id)) {
            $provider_manager         = new ProviderManager(new ProviderDao());
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            try {
                $unlinked_account     = $unlinked_account_manager->getbyId($link_id);
                $provider             = $provider_manager->getById($unlinked_account->getProviderId());

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText(
                        'plugin_openidconnectclient',
                        'info_registration',
                        array($provider->getName(), ForgeConfig::get('sys_name'))
                    )
                );
            } catch (Exception $ex) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_openidconnectclient', 'unexpected_error')
                );
                $GLOBALS['Response']->redirect('/account/login.php');
            }
        }
    }

    /**
     * @return bool
     */
    private function isUserRegistrationWithOpenIDConnectPossible($is_registration_confirmation, $link_id) {
        return ! $is_registration_confirmation && $link_id && $this->canPluginAuthenticateUser();
    }

    public function user_register_additional_field(array $params) {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($link_id && $this->canPluginAuthenticateUser()) {
            $register_presenter       = new RegisterPresenter($link_id);
            $renderer                 = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $params['field']         .= $renderer->renderToString('register_field', $register_presenter);
        }
    }

    public function after_user_registration(array $params) {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($link_id) {
            $user_manager             = UserManager::instance();
            $provider_manager         = new ProviderManager(new ProviderDao());
            $user_mapping_manager     = new UserMappingManager(new UserMappingDao());
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            $account_linker_controler = new AccountLinker\Controller(
                $user_manager,
                $provider_manager,
                $user_mapping_manager,
                $unlinked_account_manager
            );

            $account_linker_controler->linkRegisteringAccount($params['user_id'], $link_id, $request->getTime());
        }
    }

    public function manage_third_party_apps(array $params) {
        $user                 = $params['user'];
        $user_mapping_manager = new UserMappingManager(new UserMappingDao());
        $user_mappings_usage  = $user_mapping_manager->getUsageByUser($user);

        if (count($user_mappings_usage) > 0 && $this->canPluginAuthenticateUser()) {
            $renderer        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $csrf_token      = new CSRFSynchronizerToken('openid-connect-user-preferences');
            $presenter       = new UserPreferencesPresenter($user_mappings_usage, $csrf_token);
            $params['html'] .= $renderer->renderToString('user_preference', $presenter);
        }
    }

    public function site_admin_option_hook() {
        $url         = $this->getPluginPath().'/admin/';
        $plugin_name = $GLOBALS['Language']->getText('plugin_openidconnectclient', 'descriptor_name');
        echo '<li><a href="' . $url . '">' . $plugin_name . '</a></li>';
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/admin/') === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    public function process(HTTPRequest $request) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        $this->loadLibrary();

        $user_manager             = UserManager::instance();
        $provider_manager         = new ProviderManager(new ProviderDao());
        $user_mapping_manager     = new UserMappingManager(new UserMappingDao());
        $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
        $flow                     = $this->getFlow($provider_manager);

        $login_controller          = new Login\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            $flow
        );
        $account_linker_controller = new AccountLinker\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager
        );
        $user_mapping_controller   = new UserMapping\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager
        );
        $router                    = new Router(
            $login_controller,
            $account_linker_controller,
            $user_mapping_controller);
        $router->route($request);
    }

    public function processAdmin(HTTPRequest $request) {
        $provider_manager        = new ProviderManager(new ProviderDao());
        $icon_presenter_factory  = new IconPresenterFactory();
        $color_presenter_factory = new ColorPresenterFactory();
        $controller              = new Administration\Controller($provider_manager, $icon_presenter_factory, $color_presenter_factory);
        $csrf_token              = new CSRFSynchronizerToken(OPENIDCONNECTCLIENT_BASE_URL . '/admin');

        $router = new AdminRouter($controller, $csrf_token);
        $router->route($request);
    }
}
