<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I build Git_Driver_Gerrit objects
 */
class Git_Driver_Gerrit_GerritDriverFactory {

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Builds the Gerrit Driver regarding the gerrit server version
     *
     * @param Git_RemoteServer_GerritServer $server The gerrit server
     *
     * @return Git_Driver_Gerrit
     */
    public function getDriver(Git_RemoteServer_GerritServer $server) {
        include_once 'server.php';
        if (server_is_php_version_equal_or_greater_than_53() && $server->getGerritVersion() === Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS) {
            include_once '/usr/share/php-guzzle/guzzle.phar';
            $class = 'Guzzle\Http\Client';
            return new Git_Driver_GerritREST(
                new $class,
                $this->logger
            );
        }

        return new Git_Driver_GerritLegacy(new Git_Driver_Gerrit_RemoteSSHCommand($this->logger), $this->logger);
    }
}