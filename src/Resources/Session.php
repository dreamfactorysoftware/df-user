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
use DreamFactory\Rave\Resources\System\UserSessionTrait;

class Session extends BaseRestResource
{
    /**
     * Resource name
     */
    const RESOURCE_NAME = 'session';

    use UserSessionTrait;

    /**
     * {@inheritdoc}
     */
    public function getApiDocInfo()
    {
        $path = '/' . $this->getServiceName() . '/' . $this->getFullPathName();
        $eventPath = $this->getServiceName() . '.' . $this->getFullPathName( '.' );
        $apis = [
            [
                'path'        => $path,
                'operations'  => [
                    [
                        'method'           => 'GET',
                        'summary'          => 'getSession() - Retrieve the current user session information.',
                        'nickname'         => 'getSession',
                        'event_name'       => [ $eventPath . '.read' ],
                        'type'             => 'Session',
                        'responseMessages' => [
                            [
                                'message' => 'Unauthorized Access - No currently valid session available.',
                                'code'    => 401,
                            ],
                            [
                                'message' => 'System Error - Specific reason is included in the error message.',
                                'code'    => 500,
                            ],
                        ],
                        'notes'            => 'Calling this refreshes the current session, or returns an error for timed-out or invalid sessions.',
                    ],
                    [
                        'method'           => 'POST',
                        'summary'          => 'login() - Login and create a new user session.',
                        'nickname'         => 'login',
                        'type'             => 'Session',
                        'event_name'       => [ $eventPath . '.create', 'user.login' ],
                        'parameters'       => [
                            [
                                'name'          => 'body',
                                'description'   => 'Data containing name-value pairs used for logging into the system.',
                                'allowMultiple' => false,
                                'type'          => 'Login',
                                'paramType'     => 'body',
                                'required'      => true,
                            ],
                        ],
                        'responseMessages' => [
                            [
                                'message' => 'Bad Request - Request does not have a valid format, all required parameters, etc.',
                                'code'    => 400,
                            ],
                            [
                                'message' => 'System Error - Specific reason is included in the error message.',
                                'code'    => 500,
                            ],
                        ],
                        'notes'            => 'Calling this creates a new session and logs in the user.',
                    ],
                    [
                        'method'           => 'DELETE',
                        'summary'          => 'logout() - Logout and destroy the current user session.',
                        'nickname'         => 'logout',
                        'type'             => 'Success',
                        'event_name'       => [ $eventPath . '.delete', 'user.logout' ],
                        'responseMessages' => [
                            [
                                'message' => 'System Error - Specific reason is included in the error message.',
                                'code'    => 500,
                            ],
                        ],
                        'notes'            => 'Calling this deletes the current session and logs out the user.',
                    ],
                ],
                'description' => 'Operations on a user\'s session.',
            ],
        ];

        $models = [
            'Session'    => [
                'id'         => 'Session',
                'properties' => [
                    'id'              => [
                        'type'        => 'string',
                        'description' => 'Identifier for the current user.',
                    ],
                    'email'           => [
                        'type'        => 'string',
                        'description' => 'Email address of the current user.',
                    ],
                    'first_name'      => [
                        'type'        => 'string',
                        'description' => 'First name of the current user.',
                    ],
                    'last_name'       => [
                        'type'        => 'string',
                        'description' => 'Last name of the current user.',
                    ],
                    'display_name'    => [
                        'type'        => 'string',
                        'description' => 'Full display name of the current user.',
                    ],
                    'is_sys_admin'    => [
                        'type'        => 'boolean',
                        'description' => 'Is the current user a system administrator.',
                    ],
                    'role'            => [
                        'type'        => 'string',
                        'description' => 'Name of the role to which the current user is assigned.',
                    ],
                    'last_login_date' => [
                        'type'        => 'string',
                        'description' => 'Date timestamp of the last login for the current user.',
                    ],
                    'app_groups'      => [
                        'type'        => 'Array',
                        'description' => 'App groups and the containing apps.',
                        'items'       => [
                            '$ref' => 'SessionApp',
                        ],
                    ],
                    'no_group_apps'   => [
                        'type'        => 'Array',
                        'description' => 'Apps that are not in any app groups.',
                        'items'       => [
                            '$ref' => 'SessionApp',
                        ],
                    ],
                    'session_id'      => [
                        'type'        => 'string',
                        'description' => 'Id for the current session, used in X-DreamFactory-Session-Token header for API requests.',
                    ],
                    'ticket'          => [
                        'type'        => 'string',
                        'description' => 'Timed ticket that can be used to start a separate session.',
                    ],
                    'ticket_expiry'   => [
                        'type'        => 'string',
                        'description' => 'Expiration time for the given ticket.',
                    ],
                ],
            ],
            'Login'      => [
                'id'         => 'Login',
                'properties' => [
                    'email'    => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                    'password' => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                    'duration' => [
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'description' => 'Duration of the session, Defaults to 0, which means until browser is closed.',
                    ],
                ],
            ],
            'SessionApp' => [
                'id'         => 'SessionApp',
                'properties' => [
                    'id'                      => [
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'description' => 'Id of the application.',
                    ],
                    'name'                    => [
                        'type'        => 'string',
                        'description' => 'Displayed name of the application.',
                    ],
                    'description'             => [
                        'type'        => 'string',
                        'description' => 'Description of the application.',
                    ],
                    'is_url_external'         => [
                        'type'        => 'boolean',
                        'description' => 'Does this application exist on a separate server.',
                    ],
                    'launch_url'              => [
                        'type'        => 'string',
                        'description' => 'URL at which this app can be accessed.',
                    ],
                    'requires_fullscreen'     => [
                        'type'        => 'boolean',
                        'description' => 'True if the application requires fullscreen to run.',
                    ],
                    'allow_fullscreen_toggle' => [
                        'type'        => 'boolean',
                        'description' => 'True allows the fullscreen toggle widget to be displayed.',
                    ],
                    'toggle_location'         => [
                        'type'        => 'string',
                        'description' => 'Where the fullscreen toggle widget is to be displayed, defaults to top.',
                    ],
                    'is_default'              => [
                        'type'        => 'boolean',
                        'description' => 'True if this app is set to launch by default at sign in.',
                    ],
                ],
            ],
        ];

        return [ 'apis' => $apis, 'models' => $models ];
    }
}