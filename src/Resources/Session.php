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

namespace DreamFactory\Rave\User\Resources;

use DreamFactory\Rave\Exceptions\UnauthorizedException;
use DreamFactory\Rave\Exceptions\NotFoundException;
use DreamFactory\Rave\Resources\BaseRestResource;
use DreamFactory\Library\Utility\ArrayUtils;

class Session extends BaseRestResource
{
    /**
     * Resource name
     */
    const RESOURCE_NAME = 'session';

    /**
     * Gets basic user session data.
     *
     * @return array
     * @throws NotFoundException
     */
    protected function handleGET()
    {
        return static::getSessionData();
    }

    /**
     * Authenticates valid user.
     *
     * @return array
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    protected function handlePOST()
    {
        $this->triggerActionEvent( $this->response );

        $credentials = [
            'email'    => $this->getPayloadData( 'email' ),
            'password' => $this->getPayloadData( 'password' )
        ];

        //if user management not available then only system admins can login.
        if ( !class_exists( '\DreamFactory\Rave\User\Resources\System\User' ) )
        {
            $credentials['is_sys_admin'] = 1;
        }

        if ( \Auth::attempt( $credentials ) )
        {
            return static::getSessionData();
        }
        else
        {
            throw new UnauthorizedException( 'Invalid user name and password combination.' );
        }

    }

    /**
     * Logs out user
     *
     * @return array
     */
    protected function handleDELETE()
    {
        $this->triggerActionEvent( $this->response );
        \Auth::logout();
        return [ 'success' => true ];
    }

    /**
     * Fetches user session data based on the authenticated user.
     *
     * @return array
     * @throws NotFoundException
     */
    public static function getSessionData()
    {
        $user = \Auth::getUser();

        if ( empty( $user ) )
        {
            throw new NotFoundException( 'No user session found.' );
        }

        $sessionData = [
            'user_id'         => $user->id,
            'session_id'      => \Session::getId(),
            'name'            => $user->name,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'email'           => $user->email,
            'is_sys_admin'    => $user->is_sys_admin,
            'last_login_date' => $user->last_login_date,
            'host'            => gethostname()
        ];

        $s = \Session::all();

        if ( !$user->is_sys_admin )
        {
            $role = session( 'rsa.role' );
            ArrayUtils::set( $sessionData, 'role', ArrayUtils::get( $role, 'name' ) );
            ArrayUtils::set( $sessionData, 'rold_id', ArrayUtils::get( $role, 'id' ) );
        }

        return $sessionData;
    }
}