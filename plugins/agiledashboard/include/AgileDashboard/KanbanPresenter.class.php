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

class KanbanPresenter {

    /** @var string */
    public $language;

    /** @var string json of Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentationBuilder */
    public $kanban_representation;

    /** @var boolean */
    public $user_is_kanban_admin;

    /** @var int */
    public $project_id;

    public function __construct(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        $user_is_kanban_admin,
        $language,
        $project_id
    ) {
        $kanban_representation_builder = new Tuleap\AgileDashboard\REST\v1\Kanban\KanbanRepresentationBuilder(
            new AgileDashboard_KanbanColumnFactory(new AgileDashboard_KanbanColumnDao()),
            TrackerFactory::instance(),
            Tracker_FormElementFactory::instance()
        );
        $this->kanban_representation = json_encode($kanban_representation_builder->build($kanban, $user));
        $this->user_is_kanban_admin  = (int) $user_is_kanban_admin;
        $this->language              = $language;
        $this->project_id            = $project_id;
    }
}
