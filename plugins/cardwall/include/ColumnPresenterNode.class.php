<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/Tracker/TreeNode/CardPresenterNode.class.php';

/**
 * A TreeNode holding a ColumnPresenter
 */
class Cardwall_ColumnPresenterNode extends Tracker_TreeNode_SpecializedNode {

    /** @var ColumnPresenter */
    private $presenter;
    
    function __construct(TreeNode $node, ColumnPresenter $presenter) {
        parent::__construct($node);
        $this->presenter = $presenter;
    }
    
    public function getColumnPresenter() {
        return $this->presenter;
    }
}

?>
