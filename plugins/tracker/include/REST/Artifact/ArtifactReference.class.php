<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use \Tuleap\REST\JsonCast;
use \Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use \Tuleap\Tracker\REST\TrackerReference;
use \Tracker_Artifact;

class ArtifactReference {

    /**
     * @var int ID of the milestone
     */
    public $id;

    /**
     * @var string URI of the milestone
     */
    public $uri;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference
     */
    public $tracker;

    public function build(Tracker_Artifact $artifact) {
        $this->id  = JsonCast::toInt($artifact->getId());
        $this->uri = ArtifactRepresentation::ROUTE . '/' . $this->id;

        $this->tracker = new TrackerReference();
        $this->tracker->build($artifact->getTracker());
    }
}