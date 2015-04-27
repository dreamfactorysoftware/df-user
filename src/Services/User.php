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

namespace DreamFactory\Rave\User\Services;

use DreamFactory\Rave\Services\BaseRestService;
use DreamFactory\Rave\User\Resources\Password;
use DreamFactory\Rave\User\Resources\Profile;
use DreamFactory\Rave\User\Resources\Register;
use DreamFactory\Rave\User\Resources\Session;

class User extends BaseRestService
{
    protected $resources = [
        Password::RESOURCE_NAME => [
            'name'       => Password::RESOURCE_NAME,
            'class_name' => 'DreamFactory\\Rave\\User\\Resources\\Password',
            'label'      => 'Password'
        ],
        Profile::RESOURCE_NAME  => [
            'name'       => Profile::RESOURCE_NAME,
            'class_name' => 'DreamFactory\\Rave\\User\\Resources\\Profile',
            'label'      => 'Profile'
        ],
        Register::RESOURCE_NAME => [
            'name'       => Register::RESOURCE_NAME,
            'class_name' => 'DreamFactory\\Rave\\User\\Resources\\Register',
            'label'      => 'Register'
        ],
        Session::RESOURCE_NAME  => [
            'name'       => Session::RESOURCE_NAME,
            'class_name' => 'DreamFactory\\Rave\\User\\Resources\\Session',
            'label'      => 'Session'
        ]
    ];

    public function getResources()
    {
        return $this->resources;
    }
}