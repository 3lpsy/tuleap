<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group UserGroupTests
 */
class UserGroupTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    private function getResponseWithUser2($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
            $request
        );
    }

    public function testGETId() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID));

        $this->assertEquals(
            $response->json(),
            array(
                'id'         => (string) REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri'        => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'label'      => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri'  => 'user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users',
                'key'        => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'short_name' => 'static_ugroup_1'
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETIdDoesNotWorkIfUserIsProjectMemberButNotProjectAdmin() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByToken(
                $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_1_ID)
            );
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETIdDoesNotWorkIfUserIsNotProjectMember() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByToken(
                $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_2_NAME),
                $this->client->get('user_groups/'.REST_TestDataBuilder::STATIC_UGROUP_2_ID)
            );
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETIdThrowsA404IfUserGroupIdDoesNotExist() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_ID.'_999'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testOptionsUsers() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromADynamicGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_3/users'));
        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => REST_TestDataBuilder::ADMIN_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::ADMIN_ID,
                    'user_url'     => '/users/admin',
                    'email'        => REST_TestDataBuilder::ADMIN_EMAIL,
                    'real_name'    => REST_TestDataBuilder::ADMIN_REAL_NAME,
                    'display_name' => REST_TestDataBuilder::ADMIN_DISPLAY_NAME,
                    'username'     => REST_TestDataBuilder::ADMIN_USER_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                ),
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                ),
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_2_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_2_ID,
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => '',
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                ),
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_3_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_3_ID,
                    'user_url'     => '/users/rest_api_tester_3',
                    'email'        => '',
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_3_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_3_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_1_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetMultipleUsersFromAStaticGroup() {
        $response = $this->getResponse($this->client->get('user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users'));

        $this->assertEquals(
            $response->json(),
            array(
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_1_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_1_ID,
                    'user_url'     => '/users/rest_api_tester_1',
                    'email'        => REST_TestDataBuilder::TEST_USER_1_EMAIL,
                    'real_name'    => REST_TestDataBuilder::TEST_USER_1_REALNAME,
                    'display_name' => REST_TestDataBuilder::TEST_USER_1_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_1_NAME,
                    'ldap_id'      => REST_TestDataBuilder::TEST_USER_1_LDAPID,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                ),
                array(
                    'id'           => REST_TestDataBuilder::TEST_USER_2_ID,
                    'uri'          => 'users/'.REST_TestDataBuilder::TEST_USER_2_ID,
                    'user_url'     => '/users/rest_api_tester_2',
                    'email'        => '',
                    'real_name'    => '',
                    'display_name' => REST_TestDataBuilder::TEST_USER_2_DISPLAYNAME,
                    'username'     => REST_TestDataBuilder::TEST_USER_2_NAME,
                    'ldap_id'      => null,
                    'avatar_url'   => '/themes/common/images/avatar_default.png',
                    'status'       => 'A',
                    'is_anonymous' => false
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPutUsersInUserGroupWithUsername() {
        $put_resource = json_encode(array(
            array('username' => REST_TestDataBuilder::TEST_USER_1_NAME),
            array('username' => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 2);
        $this->assertEquals($response_get_json[0]["id"], 102);
        $this->assertEquals($response_get_json[1]["id"], 104);
    }

    /**
     * @depends testPutUsersInUserGroupWithUsername
     */
    public function testPutUsersInUserGroup() {
        $put_resource = json_encode(array(
            array('id' => REST_TestDataBuilder::TEST_USER_1_ID),
            array('id' => REST_TestDataBuilder::TEST_USER_2_ID),
            array('id' => REST_TestDataBuilder::TEST_USER_3_ID)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users')
        );

        $response_get_json = $response_get->json();

        $this->assertEquals(count($response_get_json), 3);
        $this->assertEquals($response_get_json[0]["id"], 102);
        $this->assertEquals($response_get_json[1]["id"], 103);
        $this->assertEquals($response_get_json[2]["id"], 104);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithTwoDifferentIds() {
        $put_resource = json_encode(array(
            array('id'       => REST_TestDataBuilder::TEST_USER_1_ID),
            array('id'       => REST_TestDataBuilder::TEST_USER_2_ID),
            array('username' => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithUnknownKey() {
        $put_resource = json_encode(array(
            array('unknown' => REST_TestDataBuilder::TEST_USER_1_ID),
            array('id'      => REST_TestDataBuilder::TEST_USER_2_ID),
            array('id'       => REST_TestDataBuilder::TEST_USER_3_NAME)
        ));

        $response = $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithNonAdminUser() {
        $put_resource = json_encode(array(
            array('id' => REST_TestDataBuilder::TEST_USER_1_ID)
        ));

        $this->getResponseWithUser2($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );
    }

    /**
     * @depends testPutUsersInUserGroup
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPutUsersInUserGroupWithNonValidRepresentation() {
        $put_resource = json_encode(array(
            array(
                'id'       => REST_TestDataBuilder::TEST_USER_1_ID,
                'username' => REST_TestDataBuilder::TEST_USER_1_NAME
            )
        ));

        $this->getResponse($this->client->put(
            'user_groups/'.REST_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.REST_TestDataBuilder::STATIC_UGROUP_2_ID.'/users',
            null,
            $put_resource)
        );
    }
}
