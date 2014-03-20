<?php
/**
 * Copyright (c) Enalean, 2014 - 2014. All Rights Reserved.
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

class Git_RemoteServer_GerritServerPresenter {

    public function __construct(Git_RemoteServer_GerritServer $server, $is_used) {
        $this->id               = $server->getId();
        $this->host             = $server->getHost();
        $this->http_port        = $server->getHTTPPort();
        $this->ssh_port         = $server->getSSHPort();
        $this->replication_key  = $server->getReplicationKey();
        $this->use_ssl          = $server->usesSSL();
        $this->login            = $server->getLogin();
        $this->identity_file    = $server->getIdentityFile();
        $this->use_gerrit_2_5   = $server->getGerritVersion() === Git_RemoteServer_GerritServer::DEFAULT_GERRIT_VERSION;
        $this->use_gerrit_2_8   = $server->getGerritVersion() !== Git_RemoteServer_GerritServer::DEFAULT_GERRIT_VERSION;
        $this->is_used          = $is_used;
        $this->http_password    = $server->getHTTPPassword();
    }
}