<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_Milestone_Pane_Content_ContentPresenter {
    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $todo_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $done_collection;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection */
    private $inconsistent_collection;

    /** @var String */
    protected $backlog_item_type;

    /** @var String[] */
    private $trackers_without_initial_effort_field;

    /** @var Tracker[] */
    private $trackers = array();

    /** @var String[] */
    private $add_new_backlog_items_urls;

    /** @var Boolean */
    private $can_prioritize;

    /** @var String */
    private $solve_inconsistencies_url;

    /** @var int */
    private $milestone_artifact_id;

    public function __construct(
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $todo,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $done,
        AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection $inconsistent_collection,
        $backlog_item_type,
        $add_new_backlog_items_urls,
        $trackers,
        $can_prioritize,
        array $trackers_without_initial_effort_defined,
        $solve_inconsistencies_url,
        $milestone_artifact_id
    ) {
        $this->todo_collection           = $todo;
        $this->done_collection           = $done;
        $this->inconsistent_collection   = $inconsistent_collection;
        $this->backlog_item_type         = $backlog_item_type;
        foreach ($trackers_without_initial_effort_defined as $tracker) {
            $this->trackers_without_initial_effort_field[] = $tracker->getName();
        }
        $this->milestone_artifact_id       = $milestone_artifact_id;
        $this->add_new_backlog_items_urls  = $add_new_backlog_items_urls;
        $this->trackers                    = $trackers;
        $this->can_prioritize              = $can_prioritize;
        $this->solve_inconsistencies_url   = $solve_inconsistencies_url;
    }

    public function getTemplateName() {
        return 'pane-content';
    }

    public function can_prioritize() {
        return $this->can_prioritize;
    }

    public function can_add_backlog_item() {
        return count($this->add_new_backlog_items_urls) > 0;
    }

    public function only_one_new_backlog_items_urls() {
        return count($this->add_new_backlog_items_urls) == 1;
    }

    public function add_new_backlog_items_urls() {
        return $this->add_new_backlog_items_urls;
    }

    public function trackers() {
        return $this->trackers;
    }

    public function create_new_specific_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_specific_item', $this->add_new_backlog_items_urls[0]['tracker_type']);
    }

    public function create_new_item() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_item');
    }

    public function create_new_item_help() {
        $trackers = array();
        foreach($this->add_new_backlog_items_urls as $backlog_entry) {
            array_push($trackers, $backlog_entry['tracker_type']);
        }
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'create_new_item_help', implode(', ', $trackers));
    }

    public function solve_inconsistencies_button() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'solve_inconsistencies');
    }

    public function solve_inconsistencies_url() {
        return $this->solve_inconsistencies_url;
    }

    public function milestone_id() {
        return (int) $this->milestone_artifact_id;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_title');
    }

    public function points() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_points');
    }

    public function type() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_type');
    }

    public function parent() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'content_head_parent');
    }

    public function todo_collection() {
        return $this->todo_collection;
    }

    public function done_collection() {
        return $this->done_collection;
    }

    public function has_something_todo() {
        return $this->todo_collection->count() > 0;
    }

    public function has_something_done() {
        return $this->done_collection->count() > 0;
    }

    public function has_something() {
        return $this->has_something_todo() || $this->has_something_done();
    }

    public function has_nothing() {
        return ! $this->has_something();
    }

    public function has_nothing_todo() {
        return ! $this->has_something_todo();
    }

    public function closed_items_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_title', $this->backlog_item_type);
    }

    public function closed_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'closed_items_intro', $this->backlog_item_type);
    }

    public function open_items_title() {
        $key = 'open_items_title';
        if ($this->has_nothing()) {
            $key = 'open_items_title-not_yet';
            if ($this->can_add_backlog_item()) {
                $key = 'open_items_title-not_yet-can_add';
            }
        } else if ($this->has_nothing_todo()) {
            $key = 'open_items_title-no_more';
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', $key, $this->backlog_item_type);
    }

    public function open_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'open_items_intro', $this->backlog_item_type);
    }

    public function user_cannot_prioritize() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'unsufficient_rights_for_ranking');
    }

    public function initial_effort_not_defined() {
        return count($this->trackers_without_initial_effort_field) > 0;
    }

    public function initial_effort_warning() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'initial_effort_warning', implode(', ', $this->trackers_without_initial_effort_field));
    }

    public function inconsistent_items_title() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'inconsistent_items_title', $this->backlog_item_type);
    }

    public function inconsistent_items_intro() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_contentpane', 'inconsistent_items_intro');
    }

    public function has_something_inconsistent() {
        return count($this->inconsistent_collection) > 0;
    }

    public function inconsistent_collection() {
        return $this->inconsistent_collection;
    }
}
