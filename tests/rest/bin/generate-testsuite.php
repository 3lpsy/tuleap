<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

$run_dir     = $argv[1];
$output_dir  = $argv[2];

$xml = simplexml_load_string(<<<XML
<?xml version='1.0'?>
<phpunit bootstrap="/usr/share/tuleap/tests/rest/vendor/autoload.php">
  <php>
    <ini name="date.timezone" value="Europe/Paris"/>
  </php>
  <logging>
    <log type="junit" target="$output_dir/rest_tests.xml" logIncompleteSkipped="true"/>
  </logging>
  <testsuites>
    <testsuite name="Tuleap REST tests">
    </testsuite>
  </testsuites>
</phpunit>
XML
);

$src_dir = __DIR__.'/../../../';

$xml->testsuites[0]->testsuite[0]->addChild('directory', $src_dir."/tests/rest/tests");

foreach (glob($src_dir.'/plugins/*/tests/rest') as $directory) {
    $xml->testsuites[0]->testsuite[0]->addChild('directory', $directory);
}

// Write the XML config
$xml->asXML("$run_dir/suite.xml");
