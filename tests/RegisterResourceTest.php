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

use DreamFactory\Rave\Utility\ServiceHandler;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Utility\Session;
use DreamFactory\Rave\Models\User;
use Illuminate\Support\Arr;

class RegisterResourceTest extends \DreamFactory\Rave\Testing\TestCase
{
    const RESOURCE = 'register';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test12345678',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => 1
    ];

    public function tearDown()
    {
        $email = Arr::get( $this->user1, 'email' );
        User::whereEmail( $email )->delete();

        parent::tearDown();
    }

    public function testPOSTRegister()
    {
        $u = $this->user1;
        $password = Arr::get( $u, 'password' );
        $payload = [
            'first_name'            => Arr::get( $u, 'first_name' ),
            'last_name'             => Arr::get( $u, 'last_name' ),
            'name'                  => Arr::get( $u, 'name' ),
            'email'                 => Arr::get( $u, 'email' ),
            'phone'                 => Arr::get( $u, 'phone' ),
            'security_question'     => Arr::get( $u, 'security_question' ),
            'security_answer'       => Arr::get( $u, 'security_answer' ),
            'password'              => $password,
            'password_confirmation' => Arr::get( $u, 'password_confirmation', $password )
        ];

        $r = $this->makeRequest( Verbs::POST, static::RESOURCE, [ ], $payload );
        $c = $r->getContent();

        $this->assertTrue( Arr::get( $c, 'success' ) );

        Session::set( 'rsa.role.name', 'test' );
        Session::set( 'rsa.role.id', 1 );

        $this->service = ServiceHandler::getService( 'user' );
        $r = $this->makeRequest( Verbs::POST, 'session', [ ], [ 'email' => Arr::get( $u, 'email' ), 'password' => Arr::get( $u, 'password' ) ] );
        $c = $r->getContent();

        $this->assertTrue(!empty(Arr::get($c, 'session_id')));
    }
}