<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUserGroupPermssionsFactory_BaseTest extends TuleapTestCase {

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    protected $dao;

    /**
     * @var User_ForgeUserGroupPermissionsFactory
     */
    protected $factory;

    public function setUp() {
        $this->dao     = mock('User_ForgeUserGroupPermissionsDao');
        $this->factory = new User_ForgeUserGroupPermissionsFactory($this->dao);
    }
}

class User_ForgeUserGroupFactory_GetPermissionsForForgeUserGroupTest extends User_ForgeUserGroupPermssionsFactory_BaseTest {

    public function itReturnsEmptyArrayIfNoResultsInDb() {
        $user_group = new User_ForgeUGroup(101, '', '');

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns(false);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertEqual(0, count($all));
    }

    public function itReturnsAnArrayOfDistinctPermissions() {
        $user_group  = new User_ForgeUGroup(101, '', '');
        $expected_id = User_ForgeUserGroupPermission_ProjectApproval::ID;

        $permission_ids = array (
            array('permission_id' => $expected_id),
            array('permission_id' => $expected_id)
        );

        stub($this->dao)->getPermissionsForForgeUGroup(101)->returns($permission_ids);
        $all = $this->factory->getPermissionsForForgeUserGroup($user_group);

        $this->assertCount($all, 1);

        $permission = $all[0];
        $this->assertIsA($permission, 'User_ForgeUserGroupPermission_ProjectApproval');
        $this->assertEqual($expected_id, $permission->getId());
    }
}

?>
