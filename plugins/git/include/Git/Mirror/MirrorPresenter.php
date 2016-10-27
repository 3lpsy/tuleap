<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Mirror;

use Codendi_HTMLPurifier;
use Git_Mirror_Mirror;

class MirrorPresenter
{
    public $id;
    public $url;
    public $hostname;
    public $name;
    public $owner_id;
    public $owner_name;
    public $ssh_key_value;
    public $ssh_key_ellipsis_value;
    public $number_of_repositories;
    public $delete_title;
    public $purified_delete_desc;
    public $has_repositories;
    public $already_used;

    public function __construct(Git_Mirror_Mirror $mirror, $nb_repositories)
    {
        $this->id                     = $mirror->id;
        $this->url                    = $mirror->url;
        $this->hostname               = $mirror->hostname;
        $this->name                   = $mirror->name;
        $this->owner_id               = $mirror->owner_id;
        $this->owner_name             = $mirror->owner_name;
        $this->ssh_key_value          = $mirror->ssh_key;
        $this->ssh_key_ellipsis_value = substr($mirror->ssh_key, 0, 40) . '...' . substr($mirror->ssh_key, -40);
        $this->has_repositories       = $nb_repositories > 0;

        $this->number_of_repositories = $GLOBALS['Language']->getText(
            'plugin_git',
            'mirror_number_of_repositories',
            $nb_repositories
        );

        $this->edit_title           = $GLOBALS['Language']->getText('plugin_git', 'edit_mirror_title', $mirror->name);
        $this->delete_title         = $GLOBALS['Language']->getText('plugin_git', 'delete_mirror_title', $mirror->name);
        $this->purified_delete_desc = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_git', 'delete_mirror_desc', $mirror->name),
            CODENDI_PURIFIER_LIGHT
        );

        $this->already_used = $GLOBALS['Language']->getText('plugin_git', 'mirror_already_used');
    }
}
