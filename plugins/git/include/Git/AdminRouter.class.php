<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

/**
 * This routes site admin part of Git
 */
class Git_AdminRouter {

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var Git_Mirror_MirrorDataMapper */
    private $git_mirror_mapper;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    public function __construct(
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        CSRFSynchronizerToken                $csrf,
        Git_Mirror_MirrorDataMapper          $git_mirror_factory
    ) {
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->csrf                  = $csrf;
        $this->git_mirror_mapper     = $git_mirror_factory;
    }

    public function process(Codendi_Request $request) {
        $controller = $this->getControllerFromRequest($request);

        $controller->process($request);
    }

    public function display(Codendi_Request $request) {
        $controller = $this->getControllerFromRequest($request);

        $controller->display($request);
    }

    private function getControllerFromRequest(Codendi_Request $request) {
        if ($request->get('pane') == 'gerrit_servers_admin') {
            return new Git_AdminGerritController($this->csrf, $this->gerrit_server_factory);
        } else {
            return new Git_AdminMirrorController($this->csrf, $this->git_mirror_mapper);
        }
    }
}
