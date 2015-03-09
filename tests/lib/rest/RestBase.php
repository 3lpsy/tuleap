<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

use \Guzzle\Http\Client;
use \Test\Rest\RequestWrapper;

class RestBase extends PHPUnit_Framework_TestCase {
    protected $base_url  = 'http://localhost:8089/api/v1';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Client
     */
    protected $xml_client;

    /**
     * @var RequestWrapper
     */
    protected $rest_request;

    public function __construct() {
        parent::__construct();
        $this->rest_request = new RequestWrapper();
    }

    public function setUp() {
        parent::setUp();
        $this->client = new Client($this->base_url);
        $this->xml_client = new Client($this->base_url);

        $this->client->setDefaultOption('headers/Accept', 'application/json');
        $this->client->setDefaultOption('headers/Content-Type', 'application/json');

        $this->xml_client->setDefaultOption('headers/Accept', 'application/xml');
        $this->xml_client->setDefaultOption('headers/Content-Type', 'application/xml; charset=UTF8');
    }

    protected function getResponseByName($name, $request) {
        return $this->rest_request->getResponseByName($name, $request);
    }

    protected function getResponseByToken(Rest_Token $token, $request) {
        return $this->rest_request->getResponseByToken($token, $request);
    }

    protected function getTokenForUserName($user_name) {
        return $this->rest_request->getTokenForUserName($user_name);
    }
}
?>