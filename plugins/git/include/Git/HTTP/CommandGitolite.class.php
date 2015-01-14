<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Git_HTTP_CommandGitolite extends Git_HTTP_Command {

    protected $gitolite_home = '/usr/com/gitolite';

    public function __construct(PFUser $user, Git_HTTP_Command $command) {
        parent::__construct();

        if (is_file("/var/lib/gitolite/projects.list")) {
            $this->gitolite_home = "/var/lib/gitolite";
        }

        $this->env['SHELL']            = '/bin/sh';
        $this->env['REMOTE_USER']      = $user->getUnixName();
        $this->env['GIT_HTTP_BACKEND'] = $command->getCommand();
        $this->env['HOME']             = $this->gitolite_home;
        $this->appendToEnv('REQUEST_URI');
        $this->appendToEnv('REMOTE_ADDR');
        $this->appendToEnv('REMOTE_PORT');
        $this->appendToEnv('SERVER_ADDR');
        $this->appendToEnv('SERVER_PORT');
    }

    protected function sudo($command) {
        return 'sudo -E -u gitolite '.$command;
    }

    public function getCommand() {
        return $this->sudo('/usr/bin/gl-auth-command');
    }
}
