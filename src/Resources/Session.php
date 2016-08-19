<?php
namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Resources\UserSessionResource;
use DreamFactory\Library\Utility\Inflector;

class Session extends UserSessionResource
{
    /**
     * {@inheritdoc}
     */
    public static function getApiDocInfo($service, array $resource = [])
    {
        $serviceName = strtolower($service);
        $capitalized = Inflector::camelize($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        $eventPath = $serviceName . '.' . $resourceName;
        $apis = [
            $path => [
                'get'    => [
                    'tags'              => [$serviceName],
                    'summary'           => 'get' .
                        $capitalized .
                        'Session() - Retrieve the current user session information.',
                    'operationId'       => 'getSession' . $capitalized,
                    'responses'         => [
                        '200'     => [
                            'description' => 'Session',
                            'schema'      => ['$ref' => '#/definitions/Session']
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Calling this refreshes the current session, or returns an error for timed-out or invalid sessions.',
                ],
                'post'   => [
                    'tags'              => [$serviceName],
                    'summary'           => 'login' . $capitalized . '() - Login and create a new user session.',
                    'operationId'       => 'login' . $capitalized,
                    'parameters'        => [
                        [
                            'name'        => 'body',
                            'description' => 'Data containing name-value pairs used for logging into the system.',
                            'schema'      => ['$ref' => '#/definitions/Login'],
                            'in'          => 'body',
                            'required'    => true,
                        ],
                    ],
                    'responses'         => [
                        '200'     => [
                            'description' => 'Session',
                            'schema'      => ['$ref' => '#/definitions/Session']
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Calling this creates a new session and logs in the user.',
                ],
                'delete' => [
                    'tags'              => [$serviceName],
                    'summary'           => 'logout' .
                        $capitalized .
                        '() - Logout and destroy the current user session.',
                    'operationId'       => 'logout' . $capitalized,
                    'responses'         => [
                        '200'     => [
                            'description' => 'Success',
                            'schema'      => ['$ref' => '#/definitions/Success']
                        ],
                        'default' => [
                            'description' => 'Error',
                            'schema'      => ['$ref' => '#/definitions/Error']
                        ]
                    ],
                    'description'       => 'Calling this deletes the current session and logs out the user.',
                ],
            ],
        ];

        $models = [
            'Session'    => [
                'type'       => 'object',
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
                        'type'        => 'array',
                        'description' => 'App groups and the containing apps.',
                        'items'       => [
                            '$ref' => '#/definitions/SessionApp',
                        ],
                    ],
                    'no_group_apps'   => [
                        'type'        => 'array',
                        'description' => 'Apps that are not in any app groups.',
                        'items'       => [
                            '$ref' => '#/definitions/SessionApp',
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
                'type'       => 'object',
                'required'   => ['email', 'password'],
                'properties' => [
                    'email'    => [
                        'type' => 'string'
                    ],
                    'password' => [
                        'type' => 'string'
                    ],
                    'duration' => [
                        'type'        => 'integer',
                        'format'      => 'int32',
                        'description' => 'Duration of the session, Defaults to 0, which means until browser is closed.',
                    ],
                ],
            ],
            'SessionApp' => [
                'type'       => 'object',
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

        return ['paths' => $apis, 'definitions' => $models];
    }
}