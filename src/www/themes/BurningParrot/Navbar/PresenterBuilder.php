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
use PFUser;
use EventManager;

class PresenterBuilder
{
    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    public function build(
        HTTPRequest $request,
        PFUser $current_user
    ) {
        $this->request      = $request;
        $this->current_user = $current_user;

        return new Presenter(
            new GlobalNavPresenter(
                $this->getGlobalMenuItems()
            ),
            new SearchPresenter(),
            new UserNavPresenter(
                $this->request,
                $this->current_user,
                $this->displayNewAccountMenuItem()
            )
        );
    }

    private function getGlobalMenuItems()
    {
        return array(
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'projects'),
                '#',
                'fa fa-archive',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'extras'),
                '#',
                'fa fa-ellipsis-h',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'help'),
                '/site/',
                'fa fa-question-circle',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'site_admin'),
                '/admin/',
                'fa fa-cog',
                'go-to-admin'
            )
        );
    }

    private function displayNewAccountMenuItem()
    {
        $display_new_user_menu_item = true;

        EventManager::instance()->processEvent(
            'display_newaccount',
            array('allow' => &$display_new_user_menu_item)
        );

        return $display_new_user_menu_item;
    }
}
