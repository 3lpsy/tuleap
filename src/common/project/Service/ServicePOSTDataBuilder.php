<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use Codendi_Request;
use ForgeConfig;
use Project;

class ServicePOSTDataBuilder
{
    /**
     * @param Codendi_Request $request
     * @return ServicePOSTData
     * @throws InvalidServicePOSTDataException
     */
    public function buildFromRequest(Codendi_Request $request)
    {
        $project = $request->getProject();

        $short_name        = $request->getValidated('short_name', 'string', '');
        $label             = $request->getValidated('label', 'string', '');
        $description       = $request->getValidated('description', 'string', '');
        $link              = $request->getValidated('link', 'localuri', '');
        $rank              = $request->getValidated('rank', 'int', 500);
        $scope             = $request->getValidated('scope', 'string', '');
        $is_active         = $request->getValidated('is_active', 'uint', 0);
        $is_used           = $request->getValidated('is_used', 'uint', false);
        $is_system_service = $this->isSystemService($request, $short_name);

        $this->checkShortname($project, $short_name);
        $this->checkLabel($label);
        $this->checkLink($link);
        $this->checkRank($project, $short_name, $rank);

        $link = $this->substituteVariablesInLink($project, $link);

        return new ServicePOSTData(
            $short_name,
            $label,
            $description,
            $link,
            $rank,
            $scope,
            (bool) $is_active,
            (bool) $is_used,
            (bool) $is_system_service
        );
    }

    /**
     * @extracted from servicebar.php
     * @param Project $project
     * @param $link
     * @return mixed
     */
    private function substituteVariablesInLink(Project $project, $link)
    {
        if ((int) $project->getID() !== 100) {
            // NOTE: if you change link variables here, change them also below, and
            // in src/common/project/RegisterProjectStep_Confirmation.class.php and src/www/include/Layout.class.php
            if (strstr($link, '$projectname')) {
                // Don't check project name if not needed.
                // When it is done here, the service bar will not appear updated on the current page
                $link = str_replace('$projectname', $project->getUnixName(), $link);
            }
            $link                 = str_replace('$sys_default_domain', $GLOBALS['sys_default_domain'], $link);
            $sys_default_protocol = 'http';
            if (ForgeConfig::get('sys_https_host')) {
                $sys_default_protocol = 'https';
            }
            $link = str_replace('$sys_default_protocol', $sys_default_protocol, $link);
            $link = str_replace('$group_id', $project->getID(), $link);
        }

        return $link;
    }

    /**
     * @param Codendi_Request $request
     * @param string $short_name
     * @return bool
     */
    private function isSystemService(Codendi_Request $request, $short_name)
    {
        $is_system_service = false;
        if ($request->exist('short_name') && trim($short_name) != '') {
            $is_system_service = true;
        }

        return $is_system_service;
    }

    /**
     * @param string $label
     * @throws InvalidServicePOSTDataException
     */
    private function checkLabel($label)
    {
        if (! $label) {
            throw new InvalidServicePOSTDataException(
                $GLOBALS['Language']->getText('project_admin_servicebar', 'label_missed')
            );
        }
    }

    /**
     * @param string $link
     * @throws InvalidServicePOSTDataException
     */
    private function checkLink($link)
    {
        if (! $link) {
            throw new InvalidServicePOSTDataException(
                $GLOBALS['Language']->getText('project_admin_servicebar', 'link_missed')
            );
        }
    }

    /**
     * @param Project $project
     * @param string $short_name
     * @param int $rank
     * @throws InvalidServicePOSTDataException
     */
    private function checkRank(Project $project, $short_name, $rank)
    {
        $minimal_rank = $project->getMinimalRank();
        if ($short_name != 'summary') {
            if (! $rank) {
                throw new InvalidServicePOSTDataException(
                    $GLOBALS['Language']->getText('project_admin_servicebar', 'rank_missed')
                );
            }
            if ($rank <= $minimal_rank) {
                throw new InvalidServicePOSTDataException(
                    $GLOBALS['Language']->getText('project_admin_servicebar', 'bad_rank', $minimal_rank)
                );
            }
        }
    }

    /**
     * @param Project $project
     * @param string $short_name
     * @throws InvalidServicePOSTDataException
     */
    private function checkShortname(Project $project, $short_name)
    {
        if (((int) $project->getID() === 100) && (! $short_name)) {
            throw new InvalidServicePOSTDataException(
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_make_s')
            );
        }
    }
}
