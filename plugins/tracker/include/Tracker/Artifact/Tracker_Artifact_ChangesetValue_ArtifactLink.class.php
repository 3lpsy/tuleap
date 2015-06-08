<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Manage values in changeset for 'artifact link' fields
 */
class Tracker_Artifact_ChangesetValue_ArtifactLink extends Tracker_Artifact_ChangesetValue {
    
    /**
     * @var array of artifact_id => Tracker_ArtifactLinkInfo
     */
    protected $artifact_links;

    /** @var UserManager */
    private $user_manager;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_ArtifactLink $field        The field of the value
     * @param boolean                                $has_changed  If the changeset value has chnged from the previous one
     * @param array                                  $artifact_links array of artifact_id => Tracker_ArtifactLinkInfo
     */
    public function __construct($id, $field, $has_changed, $artifact_links) {
        parent::__construct($id, $field, $has_changed);
        $this->artifact_links = $artifact_links;
        $this->user_manager   = UserManager::instance();
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor) {
        return $visitor->visitArtifactLink($this);
    }
    
    /**
     * Check if there are changes between current and new value
     *
     * @param array $new_value array of artifact ids
     *
     * @return bool true if there are differences
     */
    public function hasChanges($new_value) {
        if (empty($new_value['new_values']) && empty($new_value['removed_values'])) {
            // no changes
            return false;
        } else {
            $array_new_values = array_map('intval', explode(',', $new_value['new_values']));
            $array_cur_values = $this->getArtifactIds();
            sort($array_new_values);
            sort($array_cur_values);
            return $array_new_values !== $array_cur_values;
        }
    }
    
    /**
     * Returns a diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     * @param PFUser                          $user            The user or null
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html', PFUser $user = null) {
        $changes = false;
        $diff = $this->getArtifactLinkInfoDiff($changeset_value);
        if ($diff->hasChanges()) {
            $this->setCurrentUserIfUserIsNotDefined($user);

            $removed = $diff->getRemovedFormatted($user, $format);
            $added   = $diff->getAddedFormatted($user, $format);

            if ($diff->isCleared()) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
            } else if ($diff->isInitialized()) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
            } else if ($diff->isReplace()) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' '.$removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' '.$added;
            } else {
                if ($removed) {
                    $changes = $removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','added');
                }
            }
        }
        return $changes;
    }

    /**
     * Return diff between 2 changeset values
     *
     * @param Tracker_Artifact_ChangesetValue_ArtifactLink $old_changeset_value
     *
     * @return Tracker_Artifact_ChangesetValue_ArtifactLinkDiff
     */
    public function getArtifactLinkInfoDiff(Tracker_Artifact_ChangesetValue_ArtifactLink $old_changeset_value = null) {
        $previous = array();
        if ($old_changeset_value !== null) {
            $previous = $old_changeset_value->getValue();
        }
        return new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff(
            $previous,
            $this->getValue()
        );
    }

    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff() {
        $next = $this->getValue();
        if (!empty($next)) {
            $result = '';
            $added_arr = array();
            foreach($next as $art_id => $added_element) {
                $added_arr[] = $added_element->getUrl();
            }
            $added   = implode(', ', $added_arr);
            $result = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
            return $result;
        }
    }
    
    /**
     * Returns the SOAP value of this changeset value
     *
     * @param PFUser $user
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue(PFUser $user) {
        return $this->encapsulateRawSoapValue(implode(', ', $this->getArtifactIdsUserCanSee($user)));
    }

    public function getRESTValue(PFUser $user) {
        return $this->getFullRESTValue($user);
    }

    public function getFullRESTValue(PFUser $user) {
        $values = array();
        $tracker_artifact_factory = Tracker_ArtifactFactory::instance();

        foreach ($this->getArtifactIdsUserCanSee($user) as $id) {
            $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactReference';
            $artifact_reference = new $classname_with_namespace;
            $artifact_reference->build($tracker_artifact_factory->getArtifactById($id));
            $values[] = $artifact_reference;
        }

        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueArtifactLinksFullRepresentation';
        $artifact_links_representation = new $classname_with_namespace;
        $artifact_links_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $values
        );
        return $artifact_links_representation;
    }

    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    public function getValue() {
        return $this->artifact_links;
    }
    
    public function getArtifactIds() {
        return array_keys($this->artifact_links);
    }

    /**
     * Returns the list of artifact id in all artifact links user can see
     *
     * @param PFUser $user
     * @return type
     */
    public function getArtifactIdsUserCanSee(PFUser $user) {
        $artifact_links_user_can_see = array();

        foreach ($this->artifact_links as $artifact_id => $link) {
            if ($link->userCanView($user)) {
                $artifact_links_user_can_see[] = $artifact_id;
            }
        }

        return $artifact_links_user_can_see;
    }

    private function setCurrentUserIfUserIsNotDefined(&$user) {
        if (! isset($user)) {
            $user = $this->user_manager->getCurrentUser();
        }
    }
}