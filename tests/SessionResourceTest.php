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
use DreamFactory\Rave\Utility\ServiceHandler;
use Illuminate\Support\Arr;

class SessionResourceTest extends \DreamFactory\Rave\Testing\TestCase
{
    const RESOURCE = 'session';

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

    /************************************************
     * Session sub-resource test
     ************************************************/

    public function testGET()
    {
        $rs = $this->makeRequest( Verbs::GET );
        $content = $rs->getContent();

        $expected = [
            'resource' => [
                'password',
                'profile',
                'register',
                'session'
            ]
        ];

        $this->assertEquals( $expected, $content );
    }

    public function testSessionNotFound()
    {
        $this->setExpectedException( '\DreamFactory\Rave\Exceptions\NotFoundException' );
        $this->makeRequest( Verbs::GET, static::RESOURCE );
    }

    public function testUnauthorizedSessionRequest()
    {
        $user = $this->createUser( 1 );

        Auth::attempt( [ 'email' => $user['email'], 'password' => $this->user1['password'] ] );

        //Using a new instance here. Prev instance is set for user resource.
        $this->service = ServiceHandler::getService( 'system' );

        $this->setExpectedException( '\DreamFactory\Rave\Exceptions\UnauthorizedException' );
        $this->makeRequest( Verbs::GET, 'admin/session' );
    }

    public function testLogin()
    {
        Session::set( 'rsa.role.name', 'test' );
        Session::set( 'rsa.role.id', 1 );

        $user = $this->createUser( 1 );

        $payload = [ 'email' => $user['email'], 'password' => $this->user1['password'] ];

        $rs = $this->makeRequest( Verbs::POST, static::RESOURCE, [ ], $payload );
        $content = $rs->getContent();

        $this->assertEquals( $user['first_name'], $content['first_name'] );
        $this->assertTrue( !empty( $content['session_id'] ) );
    }

    public function testSessionBadPatchRequest()
    {
        $user = $this->createUser( 1 );
        $payload = [ 'name' => 'foo' ];

        $this->setExpectedException( '\DreamFactory\Rave\Exceptions\BadRequestException' );
        $this->makeRequest( Verbs::PATCH, static::RESOURCE . '/' . $user['id'], [ ], $payload );
    }

    public function testLogout()
    {
        Session::set( 'rsa.role.name', 'test' );
        Session::set( 'rsa.role.id', 1 );

        $user = $this->createUser( 1 );
        $payload = [ 'email' => $user['email'], 'password' => $this->user1['password'] ];
        $rs = $this->makeRequest( Verbs::POST, static::RESOURCE, [ ], $payload );
        $content = $rs->getContent();

        $this->assertTrue( !empty( $content['session_id'] ) );

        $rs = $this->makeRequest( Verbs::DELETE, static::RESOURCE );
        $content = $rs->getContent();

        $this->assertTrue( $content['success'] );

        $this->setExpectedException( '\DreamFactory\Rave\Exceptions\NotFoundException' );
        $this->makeRequest( Verbs::GET, static::RESOURCE );
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
        \DreamFactory\Rave\Models\User::whereEmail( $email )->delete();
    }
}