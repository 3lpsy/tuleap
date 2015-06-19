#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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

require_once 'pre.php';

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'u:'; // give me a user
$usage_options .= 't:'; // give me a tracker
$usage_options .= 'f';  // should we force the export
$usage_options .= 'h';  // should we display the usage

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project_id -u user_name [-t tracker_id] [-f]

Dump a project structure to XML format

  -p <project_id> The id of the project to export
  -u <user_name>  The user used to export
  -t <tracker_id> The id of the tracker to include in the export (optional)
  -f              Force the export (for example if there are too many artifacts). Use at your own risks.
  -h              Display this help


EOT;
    exit(1);
}

$arguments = getopt($usage_options);

if (isset($arguments['h'])) {
    usage();
}

if (! isset($arguments['p'])) {
    usage();
} else {
    $project_id = (int)$arguments['p'];
}

if (! isset($arguments['u'])) {
    usage();
} else {
    $username = $arguments['u'];
}

$options = array();
if (isset($arguments['t'])) {
    $options['tracker_id'] = (int)$arguments['t'];
}

$options['force'] = isset($arguments['f']);


$project = ProjectManager::instance()->getProject($project_id);
if ($project && ! $project->isError() && ! $project->isDeleted()) {
    try {
        $xml_exporter = new ProjectXMLExporter(
            EventManager::instance(),
            new UGroupManager(),
            new XML_RNGValidator(),
            new UserXMLExporter(UserManager::instance()),
            new ProjectXMLExporterLogger()
        );

        $user = UserManager::instance()->forceLogin($username);

        echo $xml_exporter->export($project, $options, $user);

        exit(0);
    } catch (XML_ParseException $exception) {
        fwrite(STDERR, "*** PARSE ERROR: ".$exception->getIndentedXml().PHP_EOL);
        foreach ($exception->getErrors() as $parse_error) {
            fwrite(STDERR, "*** PARSE ERROR: ".$parse_error.PHP_EOL);
        }
        fwrite(STDERR, "RNG path: ". $exception->getRngPath() . PHP_EOL);
        exit(1);
    } catch (Exception $exception) {
        fwrite(STDERR, "*** ERROR: ".$exception->getMessage().PHP_EOL);
        exit(1);
    }
} else {
    echo "*** ERROR: Invalid project_id\n";
    exit(1);
}
