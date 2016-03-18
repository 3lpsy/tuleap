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
CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_user_mapping (
    user_id INT(11) UNSIGNED NOT NULL,
    provider_id INT(11) UNSIGNED NOT NULL,
    user_openidconnect_identifier TEXT NOT NULL,
    last_used INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(user_id, provider_id)
);

CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_provider (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    authorization_endpoint TEXT NOT NULL,
    token_endpoint TEXT NOT NULL,
    user_info_endpoint TEXT NOT NULL,
    client_id TEXT NOT NULL DEFAULT '',
    client_secret TEXT NOT NULL DEFAULT '',
    PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_unlinked_account (
    id VARCHAR(32) NOT NULL,
    provider_id INT(11) UNSIGNED NOT NULL,
    openidconnect_identifier TEXT NOT NULL,
    PRIMARY KEY(id)
);

INSERT INTO plugin_openidconnectclient_provider(name, authorization_endpoint, token_endpoint, user_info_endpoint)
VALUES (
    'GitHub',
    'https://github.com/login/oauth/authorize',
    'https://github.com/login/oauth/access_token',
    'https://api.github.com/user'
);

INSERT INTO plugin_openidconnectclient_provider(name, authorization_endpoint, token_endpoint, user_info_endpoint)
VALUES (
    'Google',
    'https://accounts.google.com/o/oauth2/auth',
    'https://accounts.google.com/o/oauth2/token',
    'https://www.googleapis.com/oauth2/v2/userinfo'
);