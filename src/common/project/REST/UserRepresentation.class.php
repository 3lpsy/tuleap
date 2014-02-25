<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use \PFUser;
use \Tuleap\REST\JsonCast;

class UserRepresentation {
    /**
     * @var int
     */
    public $id;

    /**
     * @var String
     */
    public $email;

    /**
     * @var String
     */
    public $real_name;

    /**
     * @var String
     */
    public $username;

    /**
     * @var String
     */
    public $ldap_id;


    public function build(PFUser $user) {
        $this->id        = JsonCast::toInt($user->getId());
        $this->email     = $user->getEmail();
        $this->real_name = $user->getRealName();
        $this->username  = $user->getUserName();
        $this->ldap_id   = $user->getLdapId();
    }
}