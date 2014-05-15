<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'pre.php';

session_require(array('isloggedin'=>'1'));

$em = EventManager::instance();
$um = UserManager::instance();

$user = $um->getCurrentUser();

$third_paty_html     = '';
$can_change_password = true;
$can_change_realname = true;
$can_change_email    = true;
$extra_user_info     = array();
$ssh_keys_extra_html = '';

$em->processEvent(
    Event::MANAGE_THIRD_PARTY_APPS,
    array(
        'user' => $user,
        'html' => &$third_paty_html
    )
);

$em->processEvent(
    'display_change_password',
    array(
        'allow' => &$can_change_password
    )
);

$em->processEvent(
    'display_change_realname',
    array(
        'allow' => &$can_change_realname
    )
);

$em->processEvent(
    'display_change_email',
    array(
        'allow' => &$can_change_email
    )
);

$em->processEvent(
    'account_pi_entry',
    array(
        'user'      => $user,
        'user_info' => &$extra_user_info,
    )
);

$em->processEvent(
    Event::LIST_SSH_KEYS,
    array(
        'user' => $user,
        'html' => &$ssh_keys_extra_html
    )
);

$labs = array();

$em->processEvent(
    Event::LAB_FEATURES_DEFINITION_LIST,
    &$labs
);

$csrf = new CSRFSynchronizerToken('/account/index.php');
$mail_manager = new MailManager();
$tracker_formats = array();

foreach ($mail_manager->getAllMailFormats() as $format) {
    $tracker_formats[] = array(
        'format'      => $format,
        'is_selected' => $format === $mail_manager->getMailPreferencesByUser($user)
    );
}

$all_themes = array();
$themes     = util_get_theme_list();
natcasesort($themes);

foreach ($themes as $theme) {
    $all_themes[] = array(
        'theme_name'  => $theme,
        'is_selected' => $theme === $user->getTheme(),
        'is_default'  => $theme === $GLOBALS['sys_themedefault']
    );
}

$languages_html = html_get_language_popup(
    $Language,
    'language_id',
    $user->getLocale()
);

$user_helper_preferences = array(
    array(
        'preference_name'  => UserHelper::PREFERENCES_NAME_AND_LOGIN,
        'preference_label' => $Language->getText('account_options','tuleap_name_and_login'),
        'is_selected'      => user_get_preference("username_display") === UserHelper::PREFERENCES_NAME_AND_LOGIN
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_LOGIN_AND_NAME,
        'preference_label' => $Language->getText('account_options','tuleap_login_and_name'),
        'is_selected'      => user_get_preference("username_display") === UserHelper::PREFERENCES_LOGIN_AND_NAME
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_LOGIN,
        'preference_label' => $Language->getText('account_options','tuleap_login'),
        'is_selected'      => user_get_preference("username_display") === UserHelper::PREFERENCES_LOGIN
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_REAL_NAME,
        'preference_label' => $Language->getText('account_options','real_name'),
        'is_selected'      => user_get_preference("username_display") === UserHelper::PREFERENCES_REAL_NAME
    )
);

$plugins_prefs = array();
$em->processEvent(
    'user_preferences_appearance',
    array('preferences' => &$plugins_prefs)
);

$all_csv_separator = array();

foreach ($csv_separators as $separator) {
    $all_csv_separator[] = array(
        'separator_name'  => $separator,
        'separator_label' => $Language->getText('account_options', $separator),
        'is_selected'     => $separator === user_get_preference("user_csv_separator")
    );
}

$all_csv_dateformat = array();

foreach ($csv_dateformats as $dateformat) {
    $all_csv_dateformat[] = array(
        'dateformat_name'  => $dateformat,
        'dateformat_label' => $Language->getText('account_preferences', $dateformat),
        'is_selected'      => $dateformat === user_get_preference("user_csv_dateformat")
    );
}

$presenter = new User_PreferencesPresenter(
    $user,
    $can_change_realname,
    $can_change_email,
    $can_change_password,
    $extra_user_info,
    $um->getUserAccessInfo($user),
    $ssh_keys_extra_html,
    $third_paty_html,
    $csrf->fetchHTMLInput(),
    $tracker_formats,
    $labs,
    $all_themes,
    $languages_html,
    $user_helper_preferences,
    $plugins_prefs,
    $all_csv_separator,
    $all_csv_dateformat
);

$HTML->header(array(
    'title'      => $Language->getText('account_options', 'title'),
    'body_class' => array('account-maintenance')
    )
);

$renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates/user');
$renderer->renderToPage('account-maintenance', $presenter);

$HTML->footer(array());
