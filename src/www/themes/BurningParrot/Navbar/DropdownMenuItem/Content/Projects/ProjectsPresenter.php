<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects;

use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Presenter;

class ProjectsPresenter extends Presenter
{
    public $is_projects = true;

    /** @var ProjectPresenter[] */
    public $projects;

    public function __construct(
        $id,
        array $projects
    ) {
        parent::__construct($id);

        $this->projects = $projects;
    }

    public function is_member_of_at_least_one_project()
    {
        return count($this->projects) > 0;
    }

    public function browse_all()
    {
        return $GLOBALS['Language']->getText('include_menu', 'browse_all');
    }

    public function add_project()
    {
        return $GLOBALS['Language']->getText('include_menu', 'add_project');
    }

    public function is_there_something_to_filter()
    {
        return $this->is_member_of_at_least_one_project() || $this->is_trove_cat_enabled();
    }

    public function filter()
    {
        return $GLOBALS['Language']->getText('include_menu', 'filter_projects');
    }

    public function is_project_registration_enabled()
    {
        return \ForgeConfig::get('sys_use_project_registration', true);
    }

    public function is_trove_cat_enabled()
    {
        return \ForgeConfig::get('sys_use_trove');
    }
}
