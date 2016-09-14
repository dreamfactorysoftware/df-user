<?php
namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Components\Registrar;
use DreamFactory\Library\Utility\Inflector;

class Register extends BaseRestResource
{
    const RESOURCE_NAME = 'register';

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $settings = (array)$settings;
        $settings['verbAliases'] = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::POST,
            Verbs::PATCH => Verbs::POST
        ];

        parent::__construct($settings);
    }

    /**
     * Registers new user.
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\ForbiddenException
     */
    protected function handlePOST()
    {
        $payload = $this->getPayloadData();
        $login = $this->request->getParameterAsBool('login');
        $registrar = new Registrar();

        $password = array_get($payload, 'new_password', array_get($payload, 'password'));
        $data = [
            'first_name'            => array_get($payload, 'first_name'),
            'last_name'             => array_get($payload, 'last_name'),
            'name'                  => array_get($payload, 'name'),
            'email'                 => array_get($payload, 'email'),
            'phone'                 => array_get($payload, 'phone'),
            'security_question'     => array_get($payload, 'security_question'),
            'security_answer'       => array_get($payload, 'security_answer'),
            'password'              => $password,
            'password_confirmation' => array_get($payload, 'password_confirmation', $password)
        ];

        if (empty($data['first_name'])) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            list($username, $domain) = explode('@', $data['email']);
            $data['first_name'] = $username;
        }
        if (empty($data['last_name'])) {
            $names = explode('.', $data['first_name']);
            if (isset($names[1])) {
                $data['last_name'] = $names[1];
                $data['first_name'] = $names[0];
            } else {
                $data['last_name'] = $names[0];
            }
        }
        if (empty($data['name'])) {
            $data['name'] = $data['first_name'] . ' ' . $data['last_name'];
        }

        ArrayUtils::removeNull($data);

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $registrar->validator($data);

        if ($validator->fails()) {
            $messages = $validator->errors()->getMessages();

            throw new BadRequestException('Validation failed', null, null, $messages);
        } else {
            $user = $registrar->create($data);

            if ($login) {
                if ($user->confirm_code !== 'y' && !is_null($user->confirm_code)) {
                    return ['success' => true, 'confirmation_required' => true];
                } else {
                    $appId = Session::get('app.id');
                    Session::setUserInfoWithJWT($user, false, $appId);

                    return ['success' => true, 'session_token' => Session::getSessionToken()];
                }
            } else {
                return ['success' => true];
            }
        }
    }

    public static function getApiDocInfo($service, array $resource = [])
    {
        $serviceName = strtolower($service);
        $capitalized = Inflector::camelize($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        $apis = [
            $path => [
                'post' => [
                    'tags'              => [$serviceName],
                    'summary'           => 'register' . $capitalized . '() - Register a new user in the system.',
                    'operationId'       => 'register' . $capitalized,
                    'parameters'        => [
                        [
                            'name'        => 'body',
                            'description' => 'Data containing name-value pairs for new user registration.',
                            'schema'      => ['$ref' => '#/definitions/Register'],
                            'in'          => 'body',
                            'required'    => true,
                        ],
                        [
                            'name'        => 'login',
                            'description' => 'Login and create a session upon successful registration.',
                            'type'        => 'boolean',
                            'in'          => 'query',
                            'required'    => false,
                        ],
                    ],
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
                    'description'       =>
                        'The new user is created and, if required, sent an email for confirmation. ' .
                        'This also handles the registration confirmation by posting email, ' .
                        'confirmation code and new password.',
                ],
            ],
        ];

        $models = [
            'Register' => [
                'type'       => 'object',
                'required'   => ['email'],
                'properties' => [
                    'email'        => [
                        'type'        => 'string',
                        'description' => 'Email address of the new user.',
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

        return ['paths' => $apis, 'definitions' => $models];
    }
}