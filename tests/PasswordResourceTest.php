<?php
/**
 * This file is part of the DreamFactory Rave(tm)
 *
 * DreamFactory Rave(tm) <http://github.com/dreamfactorysoftware/rave>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Illuminate\Support\Arr;

class PasswordResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'password';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test1234',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => 1
    ];

    protected $user2 = [
        'name'                   => 'Jane Doe',
        'first_name'             => 'Jane',
        'last_name'              => 'Doe',
        'email'                  => 'jadoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => 1,
        'user_lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => 0
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => 1
            ]
        ]
    ];

    protected $user3 = [
        'name'                   => 'Dan Doe',
        'first_name'             => 'Dan',
        'last_name'              => 'Doe',
        'email'                  => 'ddoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => 1,
        'user_lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => 0
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => 1
            ],
            [
                'name'    => 'test3',
                'value'   => '56789',
                'private' => 1
            ]
        ]
    ];

    public function tearDown()
    {
        $this->deleteUser( 1 );
        $this->deleteUser( 2 );
        $this->deleteUser( 3 );

        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();

        Session::set( 'rsa.role.name', 'test' );
        Session::set( 'rsa.role.id', 1 );

    }

    /************************************************
     * Password sub-resource test
     ************************************************/

    public function testGET()
    {
        $this->setExpectedException( '\DreamFactory\Core\Exceptions\BadRequestException' );
        $this->makeRequest( Verbs::GET, static::RESOURCE );
    }

    public function testDELETE()
    {
        $this->setExpectedException( '\DreamFactory\Core\Exceptions\BadRequestException' );
        $this->makeRequest( Verbs::DELETE, static::RESOURCE );
    }

    public function testPasswordChange()
    {
        $user = $this->createUser( 1 );

        $this->makeRequest( Verbs::POST, 'session', [ ], [ 'email' => $user['email'], 'password' => $this->user1['password'] ] );

        $this->service = ServiceHandler::getService( $this->serviceId );
        $rs = $this->makeRequest( Verbs::POST, static::RESOURCE, [ ], [ 'old_password' => $this->user1['password'], 'new_password' => '123456' ] );
        $content = $rs->getContent();
        $this->assertTrue( $content['success'] );

        $this->service = ServiceHandler::getService( $this->serviceId );
        $this->makeRequest( Verbs::DELETE, 'session' );

        $rs = $this->makeRequest( Verbs::POST, 'session', [ ], [ 'email' => $user['email'], 'password' => '123456' ] );
        $content = $rs->getContent();
        $this->assertTrue( !empty( $content['session_id'] ) );
    }

    public function testPasswordResetUsingSecurityQuestion()
    {
        $user = $this->createUser( 1 );

        $rs = $this->makeRequest( Verbs::POST, static::RESOURCE, [ 'reset' => 'true' ], [ 'email' => $user['email'] ] );
        $content = $rs->getContent();

        $this->assertEquals( $this->user1['security_question'], $content['security_question'] );

        $rs = $this->makeRequest(
            Verbs::POST,
            static::RESOURCE,
            [ ],
            [ 'email' => $user['email'], 'security_answer' => $this->user1['security_answer'], 'new_password' => '778877' ]
        );
        $content = $rs->getContent();
        $this->assertTrue( $content['success'] );

        $this->service = ServiceHandler::getService( $this->serviceId );
        $rs = $this->makeRequest( Verbs::POST, 'session', [ ], [ 'email' => $user['email'], 'password' => '778877' ] );
        $content = $rs->getContent();
        $this->assertTrue( !empty( $content['session_id'] ) );
    }

    public function testPasswordResetUsingConfirmationCode()
    {
        if ( !$this->serviceExists( 'mymail' ) )
        {
            $emailService = \DreamFactory\Core\Models\Service::create(
                [
                    "name"        => "mymail",
                    "label"       => "Test mail service",
                    "description" => "Test mail service",
                    "is_active"   => 1,
                    "type"        => "local_email",
                    "mutable"     => 1,
                    "deletable"   => 1,
                    "config"      => [
                        "driver"  => "sendmail",
                        "command" => "/usr/sbin/sendmail -bs"
                    ]
                ]
            );

            $userConfig = \DreamFactory\Core\User\Models\UserConfig::find(4);
            $userConfig->password_email_service_id = $emailService->id;
            $userConfig->save();
        }

        if(!\DreamFactory\Core\Models\EmailTemplate::whereName('mytemplate')->exists())
        {
            $template = \DreamFactory\Core\Models\EmailTemplate::create(
                [
                    'name'=>'mytemplate',
                    'description'=>'test',
                    'to'=>$this->user2['email'],
                    'subject'=>'rest password test',
                    'body_text'=>'link {link}'
                ]
            );

            $userConfig = \DreamFactory\Core\User\Models\UserConfig::find(4);
            $userConfig->password_email_template_id = $template->id;
            $userConfig->save();
        }

        Arr::set( $this->user2, 'email', 'arif@dreamfactory.com' );
        $user = $this->createUser( 2 );

        Config::set( 'mail.pretend', true );

        $rs = $this->makeRequest( Verbs::POST, static::RESOURCE, [ 'reset' => 'true' ], [ 'email' => $user['email'] ] );
        $content = $rs->getContent();
        $this->assertTrue( $content['success'] );

        /** @var User $userModel */
        $userModel = User::find( $user['id'] );
        $code = $userModel->confirm_code;

        $rs = $this->makeRequest(
            Verbs::POST,
            static::RESOURCE,
            [ 'login' => 'true' ],
            [ 'email' => $user['email'], 'code' => $code, 'new_password' => '778877' ]
        );
        $content = $rs->getContent();
        $this->assertTrue( $content['success'] );
        $this->assertTrue( Auth::check() );

        $userModel = User::find( $user['id'] );
        $this->assertEquals( 'y', $userModel->confirm_code );

        $this->service = ServiceHandler::getService($this->serviceId);
        $rs = $this->makeRequest( Verbs::POST, 'session', [ ], [ 'email' => $user['email'], 'password' => '778877' ] );
        $content = $rs->getContent();
        $this->assertTrue( !empty( $content['session_id'] ) );
    }

    /************************************************
     * Helper methods
     ************************************************/

    protected function createUser( $num )
    {
        $user = $this->{'user' . $num};
        $payload = json_encode( [ $user ], JSON_UNESCAPED_SLASHES );

        $this->service = ServiceHandler::getService( 'system' );
        $rs = $this->makeRequest( Verbs::POST, 'user', [ 'fields' => '*', 'related' => 'user_lookup_by_user_id' ], $payload );
        $this->service = ServiceHandler::getService( $this->serviceId );

        return $rs->getContent();
    }

    protected function deleteUser( $num )
    {
        $user = $this->{'user' . $num};
        $email = Arr::get( $user, 'email' );
        \DreamFactory\Core\Models\User::whereEmail( $email )->delete();
    }
}