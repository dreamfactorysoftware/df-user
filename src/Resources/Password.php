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
use DreamFactory\Rave\Exceptions\BadRequestException;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Resources\BaseRestResource;
use DreamFactory\Rave\Models\User;

class Password extends BaseRestResource
{
    const RESOURCE_NAME = 'password';

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
     * Resets user password.
     *
     * @return array|bool
     * @throws BadRequestException
     * @throws \Exception
     */
    protected function handlePOST()
    {
        $reset = $this->getQueryBool( 'reset' );
        $login = $this->getQueryBool( 'login' );

        $payload = $this->getPayloadData();

        $oldPassword = ArrayUtils::get( $payload, 'old_password' );
        $newPassword = ArrayUtils::get( $payload, 'new_password' );
        $email = ArrayUtils::get( $payload, 'email' );
        $code = ArrayUtils::get( $payload, 'code' );
        $answer = ArrayUtils::get( $payload, 'security_answer' );

        if ( $reset && $oldPassword && \Auth::check() )
        {
            /** @var User $user */
            $user = \Auth::getUser();
            $userPassword = $user->getAuthPassword();

            if ( \Hash::check( $oldPassword, $userPassword ) )
            {
                $user->update( [ 'password' => bcrypt( $newPassword ) ] );

                return [ 'success' => true ];
            }
            else
            {
                throw new BadRequestException( 'Error validating old password.' );
            }
        }

        return false;
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
                        'method'           => 'POST',
                        'summary'          => 'changePassword() - Change or reset the current user\'s password.',
                        'nickname'         => 'changePassword',
                        'type'             => 'PasswordResponse',
                        'event_name'       => $eventPath . '.update',
                        'parameters'       => [
                            [
                                'name'          => 'reset',
                                'description'   => 'Set to true to perform password reset.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                            [
                                'name'          => 'login',
                                'description'   => 'Login and create a session upon successful password reset.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                            [
                                'name'          => 'body',
                                'description'   => 'Data containing name-value pairs for password change.',
                                'allowMultiple' => false,
                                'type'          => 'PasswordRequest',
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
                                'message' => 'Unauthorized Access - No currently valid session available.',
                                'code'    => 401,
                            ],
                            [
                                'message' => 'System Error - Specific reason is included in the error message.',
                                'code'    => 500,
                            ],
                        ],
                        'notes'            =>
                            'A valid current session along with old and new password are required to change ' .
                            'the password directly posting \'old_password\' and \'new_password\'. <br/>' .
                            'To request password reset, post \'email\' and set \'reset\' to true. <br/>' .
                            'To reset the password from an email confirmation, post \'email\', \'code\', and \'new_password\'. <br/>' .
                            'To reset the password from a security question, post \'email\', \'security_answer\', and \'new_password\'.',
                    ],
                ],
                'description' => 'Operations on a user\'s password.',
            ],
        ];

        $models = [
            'PasswordRequest'  => [
                'id'         => 'PasswordRequest',
                'properties' => [
                    'old_password' => [
                        'type'        => 'string',
                        'description' => 'Old password to validate change during a session.',
                    ],
                    'new_password' => [
                        'type'        => 'string',
                        'description' => 'New password to be set.',
                    ],
                    'email'        => [
                        'type'        => 'string',
                        'description' => 'User\'s email to be used with code to validate email confirmation.',
                    ],
                    'code'         => [
                        'type'        => 'string',
                        'description' => 'Code required with new_password when using email confirmation.',
                    ],
                ],
            ],
            'PasswordResponse' => [
                'id'         => 'PasswordResponse',
                'properties' => [
                    'security_question' => [
                        'type'        => 'string',
                        'description' => 'User\'s security question, returned on reset request when no email confirmation required.',
                    ],
                    'success'           => [
                        'type'        => 'boolean',
                        'description' => 'True if password updated or reset request granted via email confirmation.',
                    ],
                ],
            ],
        ];

        return [ 'apis' => $apis, 'models' => $models ];
    }
}