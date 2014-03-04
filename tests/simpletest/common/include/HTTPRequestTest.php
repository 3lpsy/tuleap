<?php
require_once('common/include/HTTPRequest.class.php');
require_once('common/valid/ValidFactory.class.php');
Mock::generatePartial('Valid', 'MockValid', array('isValid', 'getKey', 'validate', 'required'));
Mock::generate('Rule');
Mock::generatePartial('Valid_File', 'Valid_FileTest', array('getKey', 'validate'));

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class HTTPRequest
 */
class HTTPRequestTest extends UnitTestCase {

    function setUp() {
        $_REQUEST['exists'] = '1';
        $_REQUEST['exists_empty'] = '';
        $_SERVER['server_exists'] = '1';
        $_SERVER['server_quote'] = "l'avion du server";
        if (get_magic_quotes_gpc()) {
            $_REQUEST['quote'] = "l\\'avion";
            $_REQUEST['array'] = array('quote_1' => "l\\'avion", 'quote_2' => array('quote_3' => "l\\'oiseau"));
        } else {
            $_REQUEST['quote'] = "l'avion";
            $_REQUEST['array'] = array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau"));
        }
        $_REQUEST['testkey'] = 'testvalue';
        $_REQUEST['testarray'] = array('key1' => 'valuekey1');
        $_REQUEST['testkey_array'] = array('testvalue1', 'testvalue2', 'testvalue3');
        $_REQUEST['testkey_array_empty'] = array();
        $_REQUEST['testkey_array_mixed1'] = array('testvalue',1, 2);
        $_REQUEST['testkey_array_mixed2'] = array(1, 'testvalue', 2);
        $_REQUEST['testkey_array_mixed3'] = array(1, 2, 'testvalue');
        $_FILES['file1'] = array('name' => 'Test file 1');
    }

    function tearDown() {
        unset($_REQUEST['exists']);
        unset($_REQUEST['quote']);
        unset($_REQUEST['exists_empty']);
        unset($_SERVER['server_exists']);
        unset($_SERVER['server_quote']);
        unset($_REQUEST['testkey']);
        unset($_REQUEST['testarray']);
        unset($_REQUEST['testkey_array']);
        unset($_REQUEST['testkey_array_empty']);
        unset($_REQUEST['testkey_array_mixed1']);
        unset($_REQUEST['testkey_array_mixed2']);
        unset($_REQUEST['testkey_array_mixed3']);
        unset($_FILES['file1']);
    }

    function testGet() {
        $r =& new HTTPRequest();
        $this->assertEqual($r->get('exists'), '1');
        $this->assertFalse($r->get('does_not_exist'));
    }

    function testExist() {
        $r =& new HTTPRequest();
        $this->assertTrue($r->exist('exists'));
        $this->assertFalse($r->exist('does_not_exist'));
    }

    function testExistAndNonEmpty() {
        $r =& new HTTPRequest();
        $this->assertTrue($r->existAndNonEmpty('exists'));
        $this->assertFalse($r->existAndNonEmpty('exists_empty'));
        $this->assertFalse($r->existAndNonEmpty('does_not_exist'));
    }

    function testQuotes() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->get('quote'), "l'avion");
    }

    function testServerGet() {
        $r =& new HTTPRequest();
        $this->assertEqual($r->getFromServer('server_exists'), '1');
        $this->assertFalse($r->getFromServer('does_not_exist'));
    }

    function testServerQuotes() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->getFromServer('server_quote'), "l'avion du server");
    }

    function testSingleton() {
        $this->assertReference(
                HTTPRequest::instance(),
                HTTPRequest::instance());
        $this->assertIsA(HTTPRequest::instance(), 'HTTPRequest');
    }

    function testArray() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->get('array'), array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau")));
    }

    function testValidKeyTrue() {
        $v =& new MockRule($this);
        $v->setReturnValue('isValid', true);
        $r =& new HTTPRequest();
        $this->assertTrue($r->validKey('testkey', $v));
    }

    function testValidKeyFalse() {
        $v =& new MockRule($this);
        $v->setReturnValue('isValid', false);
        $r =& new HTTPRequest();
        $this->assertFalse($r->validKey('testkey', $v));
    }

    function testValidKeyScalar() {
        $v =& new MockRule($this);
        $v->expectOnce('isValid', array('testvalue'));
        $r =& new HTTPRequest();
        $r->validKey('testkey', $v);
    }

    function testValid() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', true);
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $r->valid($v);
    }

    function testValidTrue() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', true);
        $r =& new HTTPRequest();
        $this->assertTrue($r->valid($v));
    }

    function testValidFalse() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', false);
        $r =& new HTTPRequest();
        $this->assertFalse($r->valid($v));
    }

    function testValidScalar() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array('testvalue'));
        $r =& new HTTPRequest();
        $r->valid($v);
    }

    function testValidArray() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', true);
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $r->validArray($v);
    }

    function testValidArrayTrue() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', true);
        $r =& new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    function testValidArrayFalse() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', false);
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayScalar() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->expectAtLeastOnce('getKey');
        $v->expectAt(0,'validate', array('testvalue1'));
        $v->expectAt(1,'validate', array('testvalue2'));
        $v->expectAt(2,'validate', array('testvalue3'));
        $v->expectCallCount('validate', 3);
        $r =& new HTTPRequest();
        $r->validArray($v);
    }

    function testValidArrayArgNotArray() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayArgEmptyArrayRequired() {
        $v =& new MockValid($this);
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_empty');
        $v->expectAtLeastOnce('getKey');
        $v->setReturnValue('validate', false, array(null));
        $v->expectAtLeastOnce('validate', array(null));
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayArgEmptyArrayNotRequired() {
        $v =& new MockValid($this);
        $v->expectNever('required');
        $v->setReturnValue('getKey', 'testkey_array_empty');
        $v->expectAtLeastOnce('getKey');
        $v->setReturnValue('validate', true, array(null));
        $v->expectAtLeastOnce('validate', array(null));
        $r =& new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    function testValidArrayArgNotEmptyArrayRequired() {
        $v =& new MockValid($this);
        $v->expectAtLeastOnce('required');
        $v->required();
        $v->setReturnValue('getKey', 'testkey_array');
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayFirstArgFalse() {
        $v =& new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed1');
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayMiddleArgFalse() {
        $v =& new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed2');
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayLastArgFalse() {
        $v =& new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed3');
        $v->expectAtLeastOnce('getKey');
        $r =& new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidInArray() {
        $v =& new MockValid($this);
        $v->setReturnValue('getKey', 'key1');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array('valuekey1'));
        $r =& new HTTPRequest();
        $r->validInArray('testarray', $v);
    }

    function testValidFileNoFileValidator() {
        $v =& new MockValid($this);
        $r =& new HTTPRequest();
        $this->assertFalse($r->validFile($v));
    }

    function testValidFileOk() {
        $v =& new Valid_FileTest($this);
        $v->setReturnValue('getKey', 'file1');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array(array('file1' => array('name' => 'Test file 1')), 'file1'));
        $r =& new HTTPRequest();
        $r->validFile($v);
    }

    function testGetValidated() {
        $v1 =& new MockValid($this);
        $v1->setReturnValue('getKey', 'testkey');
        $v1->setReturnValue('validate', true);

        $v2 =& new MockValid($this);
        $v2->setReturnValue('getKey', 'testkey');
        $v2->setReturnValue('validate', false);

        $v3 =& new MockValid($this);
        $v3->setReturnValue('getKey', 'does_not_exist');
        $v3->setReturnValue('validate', false);

        $v4 =& new MockValid($this);
        $v4->setReturnValue('getKey', 'does_not_exist');
        $v4->setReturnValue('validate', true);

        $r =& new HTTPRequest();
        //If valid, should return the submitted value...
        $this->assertEqual($r->getValidated('testkey', $v1), 'testvalue');
        //...even if there is a defult value!
        $this->assertEqual($r->getValidated('testkey', $v1, 'default value'), 'testvalue');
        //If not valid, should return the default value...
        $this->assertEqual($r->getValidated('testkey', $v2, 'default value'), 'default value');
        //...or null if there is no default value!
        $this->assertNull($r->getValidated('testkey', $v2));
        //If the variable is not submitted, there is no incidence, the result depends on the validator...
        $this->assertEqual($r->getValidated('does_not_exist', $v3, 'default value'), 'default value');
        $this->assertEqual($r->getValidated('does_not_exist', $v4, 'default value'), false);

        //Not really in the "unit" test spirit
        //(create dynamically a new instance of a validator inside the function. Should be mocked)
        $this->assertEqual($r->getValidated('testkey', 'string', 'default value'), 'testvalue');
        $this->assertEqual($r->getValidated('testkey', 'uint', 'default value'), 'default value');
    }
}

class HTTPRequest_BrowserTests extends TuleapTestCase {

    private $user_agent;

    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $user;

    private $msg_ie7_deprecated        = 'ie7 warning message';
    private $msg_ie7_deprecated_button = 'disable ie7 warning';

    public function setUp() {
        parent::setUp();
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        $this->setText($this->msg_ie7_deprecated, array('*', 'ie7_deprecated'));
        $this->setText($this->msg_ie7_deprecated_button, array('*', 'ie7_deprecated_button'));

        $this->user   = mock('PFUser');
        $user_manager = stub('UserManager')->getCurrentUser()->returns($this->user);
        UserManager::setInstance($user_manager);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->user);
    }

    public function tearDown() {
        unset($_SERVER['HTTP_USER_AGENT']);
        if ($this->user_agent) {
            $_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
        }

        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testIE9CompatibilityModeIsDeprected() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        expect($GLOBALS['Language'])->getText('*', 'ie9_compatibility_deprecated')->once();

        $browser->getDeprecatedMessage();
    }

    public function testIE9IsUnfortunatlyNotDeprecated() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
        $browser = $this->request->getBrowser();

        expect($GLOBALS['Language'])->getText()->never();

        $browser->getDeprecatedMessage();
    }

    public function testFirefoxIsNotDeprecated() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:27.0) Gecko/20100101 Firefox/27.0';
        $browser = $this->request->getBrowser();

        expect($GLOBALS['Language'])->getText()->never();

        $browser->getDeprecatedMessage();
    }

    public function testIE7IsDeprecated() {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern("/$this->msg_ie7_deprecated/", $browser->getDeprecatedMessage());
    }

    public function testIE7IsDeprecatedButUserChoseToNotDisplayTheWarning() {
        stub($this->user)->getPreference(PFUser::PREFERENCE_DISABLE_IE7_WARNING)->returns(1);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertNoPattern("/$this->msg_ie7_deprecated/", $browser->getDeprecatedMessage());
    }

    public function itDisplaysOkButtonToDisableIE7Warning() {
        stub($this->user)->isAnonymous()->returns(false);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern("/$this->msg_ie7_deprecated_button/", $browser->getDeprecatedMessage());
    }

    public function itDoesNotDisplayOkButtonForAnonymousUser() {
        stub($this->user)->isAnonymous()->returns(true);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertNoPattern("/$this->msg_ie7_deprecated_button/", $browser->getDeprecatedMessage());
    }
}