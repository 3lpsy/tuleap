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

namespace Tuleap\Theme\BurningParrot\Navbar;

use HTTPRequest;
use Tuleap\Theme\BurningParrot\Navbar\Dropdown\DropdownPresenter;

class GlobalNavbarDropdownMenuItemPresenter
{
    /** @var string */
    public $label;

    /** @var icon */
    public $icon;

    /** @var DropdownPresenter */
    public $navbar_dropdown;

    /** @var boolean */
    public $is_user_on_site_admin;

    public function __construct($label, $icon, $navbar_dropdown)
    {
        $this->label           = $label;
        $this->icon            = $icon;
        $this->navbar_dropdown = $navbar_dropdown;
    }

    public function is_user_on_site_admin()
    {
        $pattern = '/^\/admin\/*.*/';
        return preg_match($pattern, $_SERVER['REQUEST_URI']) === 1;
    }
}