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

use DreamFactory\Rave\Enums\DataFormats;
use DreamFactory\Rave\Enums\HttpStatusCodes;
use DreamFactory\Rave\Resources\BaseRestResource;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Utility\ResponseFactory;
use DreamFactory\Rave\Components\Registrar;

class Register extends BaseRestResource
{
    const RESOURCE_NAME = 'register';

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
     * Creates new user.
     *
     * @return array|\DreamFactory\Rave\Utility\ServiceResponse
     */
    protected function handlePOST()
    {
        $payload = $this->getPayloadData();
        $login = $this->request->getParameterAsBool( 'login' );
        $registrar = new Registrar();

        $password = ArrayUtils::get( $payload, 'new_password', ArrayUtils::get( $payload, 'password' ) );
        $data = [
            'first_name'            => ArrayUtils::get( $payload, 'first_name' ),
            'last_name'             => ArrayUtils::get( $payload, 'last_name' ),
            'name'                  => ArrayUtils::get( $payload, 'name' ),
            'email'                 => ArrayUtils::get( $payload, 'email' ),
            'phone'                 => ArrayUtils::get( $payload, 'phone' ),
            'security_question'     => ArrayUtils::get( $payload, 'security_question' ),
            'security_answer'       => ArrayUtils::get( $payload, 'security_answer' ),
            'password'              => $password,
            'password_confirmation' => ArrayUtils::get( $payload, 'password_confirmation', $password )
        ];

        ArrayUtils::removeNull( $data );

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $registrar->validator( $data );

        if ( $validator->fails() )
        {
            $messages = $validator->errors()->getMessages();

            $messages = [ 'error' => $messages ];

            return ResponseFactory::create( $messages, DataFormats::PHP_ARRAY, HttpStatusCodes::HTTP_BAD_REQUEST );
        }
        else
        {
            $user = $registrar->create( $data );

            if ( $login )
            {
                \Auth::login( $user );
            }

            return [ 'success' => true ];
        }
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
                        'summary'          => 'register() - Register a new user in the system.',
                        'nickname'         => 'register',
                        'type'             => 'Success',
                        'event_name'       => [ $eventPath . '.create' ],
                        'parameters'       => [
                            [
                                'name'          => 'login',
                                'description'   => 'Login and create a session upon successful registration.',
                                'allowMultiple' => false,
                                'type'          => 'boolean',
                                'paramType'     => 'query',
                                'required'      => false,
                            ],
                            [
                                'name'          => 'body',
                                'description'   => 'Data containing name-value pairs for new user registration.',
                                'allowMultiple' => false,
                                'type'          => 'Register',
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
                        'notes'            =>
                            'The new user is created and, if required, sent an email for confirmation. ' .
                            'This also handles the registration confirmation by posting email, ' .
                            'confirmation code and new password.',
                    ],
                ],
                'description' => 'Operations to register a new user.',
            ],
        ];

        $models = [
            'Register' => [
                'id'         => 'Register',
                'properties' => [
                    'email'        => [
                        'type'        => 'string',
                        'description' => 'Email address of the new user.',
                        'required'    => true,
                    ],
                    'first_name'   => [
                        'type'        => 'string',
                        'description' => 'First name of the new user.',
                    ],
                    'last_name'    => [
                        'type'        => 'string',
                        'description' => 'Last name of the new user.',
                    ],
                    'display_name' => [
                        'type'        => 'string',
                        'description' => 'Full display name of the new user.',
                    ],
                    'new_password' => [
                        'type'        => 'string',
                        'description' => 'Password for the new user.',
                    ],
                    'code'         => [
                        'type'        => 'string',
                        'description' => 'Code required with new_password when using email confirmation.',
                    ],
                ],
            ],
        ];

        return [ 'apis' => $apis, 'models' => $models ];
    }
}