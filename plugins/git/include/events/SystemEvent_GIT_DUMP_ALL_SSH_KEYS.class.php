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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class SystemEvent_GIT_DUMP_ALL_SSH_KEYS extends SystemEvent {
    const NAME = 'GIT_DUMP_ALL_SSH_KEYS';

    /** @var Logger */
    private $logger;

    /** @var Git_Gitolite_SSHKeyMassDumper */
    private $mass_dumper;

    public function injectDependencies(
        Git_Gitolite_SSHKeyMassDumper $mass_dumper,
        Logger $logger
    ) {
        $this->mass_dumper      = $mass_dumper;
        $this->logger           = $logger;
    }

    public function process() {
        $this->logger->debug('Dump all user ssh keys');
        $this->mass_dumper->dumpSSHKeys();
        $this->done();
    }

    public function verbalizeParameters($with_link) {
       return '';
    }
}
