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

use DreamFactory\Library\Utility\Scalar;
use DreamFactory\Library\Utility\Enums\Verbs;

class UserResourceTest extends \DreamFactory\Rave\Testing\UserResourceTestCase
{
    const RESOURCE = 'user';

    /************************************************
     * Testing GET
     ************************************************/

    public function testGET()
    {
        $user1 = $this->createUser( 1 );
        $user2 = $this->createUser( 2 );
        $user3 = $this->createUser( 3 );

        $rs = $this->makeRequest( Verbs::GET, static::RESOURCE );
        $content = $rs->getContent();

        //Total 4 users including the default admin user but admin users shouldn't come up here.
        $this->assertEquals( 3, count( $content['record'] ) );
        $this->assertTrue( $this->adminCheck( $content['record'] ) );

        $ids = implode( ',', array_column( $content['record'], 'id' ) );
        $this->assertEquals( implode( ',', array_column( [ $user1, $user2, $user3 ], 'id' ) ), $ids );
    }

    public function testGETWithLimitOffset()
    {
        $user1 = $this->createUser( 1 );
        $user2 = $this->createUser( 2 );
        $user3 = $this->createUser( 3 );

        $rs = $this->makeRequest( Verbs::GET, static::RESOURCE, [ 'limit' => 3 ] );
        $content = $rs->getContent();

        $this->assertEquals( 3, count( $content['record'] ) );

        $idsOut = implode( ',', array_column( $content['record'], 'id' ) );
        $this->assertEquals( implode( ',', array_column( [ $user1, $user2, $user3 ], 'id' ) ), $idsOut );

        $rs = $this->makeRequest( Verbs::GET, static::RESOURCE, [ 'limit' => 3, 'offset' => 1 ] );
        $content = $rs->getContent();

        $this->assertEquals( 2, count( $content['record'] ) );

        $idsOut = implode( ',', array_column( $content['record'], 'id' ) );
        $this->assertEquals( implode( ',', array_column( [$user2, $user3 ], 'id' ) ), $idsOut );

        $rs = $this->makeRequest( Verbs::GET, static::RESOURCE, [ 'limit' => 2, 'offset' => 2 ] );
        $content = $rs->getContent();

        $this->assertEquals( 1, count( $content['record'] ) );

        $idsOut = implode( ',', array_column( $content['record'], 'id' ) );
        $this->assertEquals( implode( ',', array_column( [ $user3 ], 'id' ) ), $idsOut );
        $this->assertTrue( $this->adminCheck( $content['record'] ) );
    }


    protected function adminCheck( $records )
    {
        foreach ( $records as $user )
        {
            $userModel = \DreamFactory\Rave\Models\User::find( $user['id'] );

            if ( Scalar::boolval( $userModel->is_sys_admin ) )
            {
                return false;
            }
        }

        return true;
    }
}