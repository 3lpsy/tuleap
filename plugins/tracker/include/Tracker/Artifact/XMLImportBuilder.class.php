<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_XMLImportBuilder {

    /**
     * @return Tracker_Artifact_XMLImport
     */
    public function build() {
        $user_manager          = UserManager::instance();
        $artifact_factory      = Tracker_ArtifactFactory::instance();
        $formelement_factory   = Tracker_FormElementFactory::instance();
        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($formelement_factory);
        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();
        $logger                = new Log_ConsoleLogger();
        $send_notifications    = false;

        $artifact_creator = new Tracker_ArtifactCreator(
            $artifact_factory,
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                $formelement_factory,
                $changeset_dao,
                $artifact_factory,
                EventManager::instance()
            )
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            $formelement_factory,
            $changeset_dao,
            $changeset_comment_dao,
            $artifact_factory,
            EventManager::instance(),
            ReferenceManager::instance()
        );

        return new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            Tracker_FormElementFactory::instance(),
            new Tracker_Artifact_XMLImport_XMLImportHelper($user_manager),
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $logger,
            $send_notifications
        );
    }
}
