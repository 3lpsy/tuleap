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

namespace Tuleap\OpenIDConnectClient\UserMapping;

use CSRFSynchronizerToken;
use DateTime;

class UserPreferencesPresenter {

    /**
     * @var UserMappingUsage[]
     */
    private $user_mappings_usage;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(array $user_mappings_usage, CSRFSynchronizerToken $csrf_token) {
        $this->user_mappings_usage = $user_mappings_usage;
        $this->csrf_token          = $csrf_token;
    }

    public function user_mappings() {
        $mappings_presenter = array();

        foreach ($this->user_mappings_usage as $user_mapping_usage) {
            $last_usage = DateTime::createFromFormat('U', $user_mapping_usage->getLastUsage());
            $mappings_presenter[] = array(
                'provider_id'   => $user_mapping_usage->getProviderId(),
                'provider_name' => $user_mapping_usage->getProviderName(),
                'last_usage'    => $last_usage->format($GLOBALS['Language']->getText('system', 'datefmt'))
            );
        }
        return $mappings_presenter;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'title_user_preferences');
    }

    public function unlink() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'unlink');
    }

    public function form_action() {
        return OPENIDCONNECTCLIENT_BASE_URL . '/?action=remove-user-mapping';
    }

    public function csrf_token() {
        return $this->csrf_token->fetchHTMLInput();
    }

}