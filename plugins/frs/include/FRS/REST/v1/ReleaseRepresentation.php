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

namespace Tuleap\FRS\REST\v1;

use Tuleap\REST\JsonCast;
use FRSRelease;
use PFUser;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\FRS\Link\Retriever;
use Tracker_REST_Artifact_ArtifactRepresentationBuilder;
use Tracker_FormElementFactory;
use Tracker_ArtifactFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

class ReleaseRepresentation
{
    const ROUTE = 'frs_release';

    /**
     * @var id {@type int}
     */
    public $id;

    /**
     * @var $uri {@type string}
     */
    public $uri;

    /**
     * @var $name {@type string}
     */
    public $name;

    /**
     * @var $files {@type array}
     */
    public $files = array();

    /**
     * @var $changelog {@type string}
     */
    public $changelog;

    /**
     * @var $release_note {@type string}
     */
    public $release_note;

    /**
     * @var $resources {@type array}
     */
    public $resources;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var Tuleap\Tracker\REST\Artifact\ArtifactRepresentation
     */
    public $artifact;

    public function build(FRSRelease $release, Retriever $link_retriever, PFUser $user)
    {
        $this->id           = JsonCast::toInt($release->getReleaseID());
        $this->uri          = self::ROUTE ."/". urlencode($release->getReleaseID());
        $this->changelog    = $release->getChanges();
        $this->release_note = $release->getNotes();
        $this->name         = $release->getName();
        $this->package      = array(
            "id"   =>$release->getPackage()->getPackageID(),
            "name" => $release->getPackage()->getName()
        );

        $this->artifact  = $this->getArtifactRepresentation($release, $link_retriever, $user);
        $this->resources = array(
            "artifacts" => array(
                "uri" => $this->uri ."/artifacts"
            )
        );
        $this->project = new ProjectReference();
        $this->project->build($release->getProject());

        foreach ($release->getFiles() as $file) {
            $file_representation = new FileRepresentation();
            $file_representation->build($file);
            $this->files[] = $file_representation;
        }

    }

    private function getArtifactRepresentation(FRSRelease $release, Retriever $link_retriever, PFUser $user)
    {
        $artifact_id = $link_retriever->getLinkedArtifactId($release->getReleaseID());

        if (! $artifact_id) {
            return null;
        }

        $tracker_artifact_builder = new Tracker_REST_Artifact_ArtifactRepresentationBuilder(
            Tracker_FormElementFactory::instance(),
            Tracker_ArtifactFactory::instance(),
            new NatureDao()
        );

        $tracker_factory = Tracker_ArtifactFactory::instance();
        $artifact        = $tracker_factory->getArtifactByIdUserCanView($user, $artifact_id);

        if (! $artifact) {
            return null;
        }

        return $tracker_artifact_builder->getArtifactRepresentation($artifact);
    }
}
