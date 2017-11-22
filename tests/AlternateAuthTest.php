<?php

use DreamFactory\Core\User\Components\AlternateAuth;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Testing\TestServiceRequest;

class AlternateAuthTest extends \DreamFactory\Core\Testing\TestCase
{
    public $serviceId = null;

    public function setUp()
    {
        parent::setUp();

        $data = [
            'name'        => 'mysql',
            'type'        => 'mysql',
            'label'       => 'mysql',
            'description' => 'Mysql test db service',
            'is_active'   => 1,
            'config'      => [
                'host'          => 'localhost',
                'database'      => 'df_unit_test',
                'username'      => 'homestead',
                'password'      => 'secret',
                'cache_enabled' => false
            ]
        ];

        $service = Service::create($data);
        $this->serviceId = $service->id;
    }

    public function tearDown()
    {
        Service::whereId($this->serviceId)->delete();

        parent::tearDown();
    }

    public function testGenerateFilter()
    {
        $table = 'df_unit_test.user ';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email';
        $otherFields = 'is_sys_admin, last_name';
        $filters = 'is_active= true,is_smart=1 , is_big=false';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);

        $this->assertEquals('mysql', $this->getNonPublicProperty($auth, 'service'));
        $this->assertEquals('df_unit_test.user', $this->getNonPublicProperty($auth, 'table'));

        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!', 'is_sys_admin' => true]
        );
        $filterString = $this->invokeMethod($auth, 'generateFilter', [$request]);
        $this->assertEquals(
            '((is_active=1) AND (is_smart=1) AND (is_big=0) AND (email=admin@test.com) AND (is_sys_admin=1))',
            $filterString
        );
    }

    public function testHandleLoginSuccess()
    {
        $table = 'user';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email';
        $otherFields = 'is_sys_admin';
        $filters = 'is_active=true';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);
        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!', 'is_sys_admin' => true]
        );
        \DreamFactory\Core\Utility\Session::put('app.id', 1);
        $result = $auth->handLogin($request);
        $this->assertTrue(isset($result['session_token']));
        $this->assertEquals('admin@test.com', $result['email']);
    }

    public function testHandleLoginFailure1()
    {
        $table = 'user';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email';
        $otherFields = 'is_sys_admin';
        $filters = 'is_active=true';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);
        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!1', 'is_sys_admin' => true]
        );
        \DreamFactory\Core\Utility\Session::put('app.id', 1);
        $this->expectException(\DreamFactory\Core\Exceptions\UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid credential supplied');
        $auth->handLogin($request);
    }

    public function testHandleLoginFailure2()
    {
        $table = 'user';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email_address';
        $otherFields = 'is_sys_admin';
        $filters = 'is_active=true';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);
        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!', 'is_sys_admin' => true]
        );
        \DreamFactory\Core\Utility\Session::put('app.id', 1);
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('Failed to retrieve alternate user\'s email address using field email_address');
        $auth->handLogin($request);
    }

    public function testHandleLoginFailure3()
    {
        $table = 'user';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email';
        $otherFields = 'is_sys_admin';
        $filters = 'is_active=true';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);
        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!', 'is_sys_admin' => false]
        );
        \DreamFactory\Core\Utility\Session::put('app.id', 1);
        $this->expectException(\DreamFactory\Core\Exceptions\UnauthorizedException::class);
        $this->expectExceptionMessage('Invalid user information provided');
        $auth->handLogin($request);
    }

    public function testHandleLoginFailure4()
    {
        $table = 'user123';
        $usernameField = 'email';
        $passwordField = ' password';
        $emailField = 'email';
        $otherFields = 'is_sys_admin';
        $filters = 'is_active=true';

        $auth = new AlternateAuth($this->serviceId, $table, $usernameField, $passwordField, $emailField);
        $auth->setOtherFields($otherFields);
        $auth->setFilters($filters);
        $request = new TestServiceRequest(
            'POST', [], [], ['email' => 'admin@test.com', 'password' => 'Dream123!', 'is_sys_admin' => false]
        );
        \DreamFactory\Core\Utility\Session::put('app.id', 1);
        $this->expectException(\DreamFactory\Core\Exceptions\RestException::class);
        $auth->handLogin($request);
    }

    public function testSetService1()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No alternate db service found with id -9999');
        new AlternateAuth(-9999, 'user', 'email', 'password', 'email');
    }

    public function testSetService2()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No service id provided');
        new AlternateAuth('', 'user', 'email', 'password', 'email');
    }

    public function testSetTable()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No table name provided');
        new AlternateAuth($this->serviceId, '', 'email', 'password', 'email');
    }

    public function testSetUsernameField()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No username field provided');
        new AlternateAuth($this->serviceId, 'user', ' ', 'password', 'email');
    }

    public function testSetPasswordField()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No password field provided');
        new AlternateAuth($this->serviceId, 'user', 'email', null, 'email');
    }

    public function testSetEmailField()
    {
        $this->expectException(\DreamFactory\Core\Exceptions\InternalServerErrorException::class);
        $this->expectExceptionMessage('No email field provided');
        new AlternateAuth($this->serviceId, 'user', 'email', 'password', 0);
    }
}