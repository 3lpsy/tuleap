<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

require_once dirname(__FILE__).'/../autoload.php';
require_once 'common/autoload.php';

class SOAPBase extends PHPUnit_Framework_TestCase {

    protected $server_base_url;
    protected $base_wsdl;
    protected $server_name;
    protected $server_port;
    protected $login;
    protected $password;

    /** @var SoapClient */
    protected $soap_base;

    public function setUp() {
        parent::setUp();

        $this->login           = SOAP_TestDataBuilder::TEST_USER_1_NAME;
        $this->password        = SOAP_TestDataBuilder::TEST_USER_1_PASS;
        $this->server_base_url = 'http://localhost/soap/?wsdl';
        $this->base_wsdl       = '/soap/codendi.wsdl.php';
        $this->server_name     = 'localhost';
        $this->server_port     = '80';

        // Connecting to the soap's tracker client
        $this->soap_base = new SoapClient(
            $this->server_base_url,
            array('cache_wsdl' => WSDL_CACHE_NONE)
        );
    }

    /**
     * @return string
     */
    protected function getSessionHash() {
        // Establish connection to the server
        return $this->soap_base->login($this->login, $this->password)->session_hash;
    }

}
