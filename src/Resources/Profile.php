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

use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Resources\BaseRestResource;
use DreamFactory\Rave\Exceptions\NotFoundException;

class Profile extends BaseRestResource
{
    const RESOURCE_NAME = 'profile';

    /**
     * @param array $settings
     */
    public function __construct( $settings = [ ] )
    {
        $verbAliases = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::POST,
            Verbs::PATCH => Verbs::POST
        ];
        ArrayUtils::set( $settings, "verbAliases", $verbAliases );

        parent::__construct( $settings );
    }

    /**
     * Fetches user profile.
     *
     * @return array
     * @throws NotFoundException
     */
    protected function handleGET()
    {
        $user = \Auth::user();

        if ( empty( $user ) )
        {
            throw new NotFoundException( 'No user session found.' );
        }

        $data = [
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'security_question' => $user->security_question
        ];

        return $data;
    }

    /**
     * Updates user profile.
     *
     * @return array
     * @throws \Exception
     */
    protected function handlePOST()
    {
        $payload = $this->getPayloadData();

        $data = [
            'first_name'        => ArrayUtils::get( $payload, 'first_name' ),
            'last_name'         => ArrayUtils::get( $payload, 'last_name' ),
            'name'              => ArrayUtils::get( $payload, 'name' ),
            'email'             => ArrayUtils::get( $payload, 'email' ),
            'phone'             => ArrayUtils::get( $payload, 'phone' ),
            'security_question' => ArrayUtils::get( $payload, 'security_question' ),
            'security_answer'   => ArrayUtils::get( $payload, 'security_answer' )
        ];

        ArrayUtils::removeNull( $data );

        $user = \Auth::user();

        if ( empty( $user ) )
        {
            throw new NotFoundException( 'No user session found.' );
        }

        $user->update( $data );

        return [ 'success' => true ];
    }

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
                        'summary'          => 'getProfile() - Retrieve the current user\'s profile information.',
                        'nickname'         => 'getProfile',
                        'type'             => 'ProfileResponse',
                        'event_name'       => $eventPath . '.read',
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
                        'notes'            =>
                            'A valid current session is required to use this API. ' .
                            'This profile, along with password, is the only things that the user can directly change.',
                    ],
                    [
                        'method'           => 'POST',
                        'summary'          => 'updateProfile() - Update the current user\'s profile information.',
                        'nickname'         => 'updateProfile',
                        'type'             => 'Success',
                        'event_name'       => $eventPath . '.update',
                        'parameters'       => [
                            [
                                'name'          => 'body',
                                'description'   => 'Data containing name-value pairs for the user profile.',
                                'allowMultiple' => false,
                                'type'          => 'ProfileRequest',
                                'paramType'     => 'body',
                                'required'      => true,
                            ],
                        ],
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
                        'notes'            => 'Update the display name, phone, etc., as well as, security question and answer.',
                    ],
                ],
                'description' => 'Operations on a user\'s profile.',
            ],
        ];

        $_commonProfile = [
            'email'             => [
                'type'        => 'string',
                'description' => 'Email address of the current user.',
            ],
            'first_name'        => [
                'type'        => 'string',
                'description' => 'First name of the current user.',
            ],
            'last_name'         => [
                'type'        => 'string',
                'description' => 'Last name of the current user.',
            ],
            'display_name'      => [
                'type'        => 'string',
                'description' => 'Full display name of the current user.',
            ],
            'phone'             => [
                'type'        => 'string',
                'description' => 'Phone number.',
            ],
            'security_question' => [
                'type'        => 'string',
                'description' => 'Question to be answered to initiate password reset.',
            ],
            'default_app_id'    => [
                'type'        => 'integer',
                'format'      => 'int32',
                'description' => 'Id of the application to be launched at login.',
            ],
        ];

        $models = [
            'ProfileRequest'  => [
                'id'         => 'ProfileRequest',
                'properties' => array_merge(
                    $_commonProfile,
                    [
                        'security_answer' => [
                            'type'        => 'string',
                            'description' => 'Answer to the security question.',
                        ],
                    ]
                ),
            ],
            'ProfileResponse' => [
                'id'         => 'ProfileResponse',
                'properties' => $_commonProfile,
            ],
        ];

        return [ 'apis' => $apis, 'models' => $models ];
    }
}