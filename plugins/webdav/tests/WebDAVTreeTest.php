<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
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

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once(dirname(__FILE__).'/../../../src/common/include/Error.class.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/MethodNotAllowed.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/URLUtil.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSFile.class.php');
Mock::generate('WebDAVFRSFile');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSPackage.class.php');
require_once(dirname(__FILE__).'/../include/lib/Sabre/DAV/Tree.php');
require_once(dirname(__FILE__).'/../include/lib/Sabre/DAV/ObjectTree.php');
require_once(dirname(__FILE__).'/../include/WebDAVTree.class.php');

/**
 * This is the unit test of WebDAVTree
 */
class TestTree extends WebDAVTree {
    function __construct() {
        
    }

    function getNodeForPath($path) {
        return new MockWebDAVFRSFile();
    }
}
Mock::generatePartial('TestTree', 'TestTreeTestVersion', array('canBeMoved'));

class TestFile extends WebDAVFRSFile {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease extends WebDAVFRSRelease {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease2 extends WebDAVFRSRelease {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 2);
        return $project;
    }
}

class TestPackage extends WebDAVFRSPackage {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class WebDAVTreeTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVTreeTest($name = 'WebDAVTreeTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    function testCanBeMovedFailNotMovable() {
        $source = null;
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotReleaseDestinationPackage() {
        $source = null;
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotFileDestinationRelease() {
        $source = null;
        $destination = new TestRelease();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationNotPackage() {
        $source = new TestRelease();
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationNotRelease() {
        $source = new TestFile();
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationPackageNotSameProject() {
        $source = new TestRelease2();
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationReleaseNotSameProject() {
        $source = new TestFile();
        $destination = new TestRelease2();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedSucceedeSourceReleaseDestinationPackage() {
        $source = new TestRelease();
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), true);
    }

    function testCanBeMovedSucceedeSourceFileDestinationRelease() {
        $source = new TestFile();
        $destination = new TestRelease();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), true);
    }

    function testMoveOnlyRename() {
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', true);
        //$node = $tree->getNodeForPath('path');

        //$node->expectOnce('setName');
        //$node->expectNever('move');
        $this->assertNoErrors();

        $tree->move('project1/package1/release1', 'project1/package1/release2');
    }

    function testMoveCanNotMove() {
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', false);
        //$node = $tree->getNodeForPath('path');

        //$node->expectNever('setName');
        //$node->expectNever('move');
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    function testMoveSucceed() {
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', true);
        //$node = $tree->getNodeForPath('path');

        //$node->expectNever('setName');
        //$node->expectOnce('move');
        $this->assertNoErrors();

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

}
?>