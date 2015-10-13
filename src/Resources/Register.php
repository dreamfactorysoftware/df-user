<?php
namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Utility\ApiDocUtilities;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Components\Registrar;

class Register extends BaseRestResource
{
    const RESOURCE_NAME = 'register';

    /**
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $verbAliases = [
            Verbs::PUT   => Verbs::POST,
            Verbs::MERGE => Verbs::POST,
            Verbs::PATCH => Verbs::POST
        ];
        ArrayUtils::set($settings, "verbAliases", $verbAliases);

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

        $password = ArrayUtils::get($payload, 'new_password', ArrayUtils::get($payload, 'password'));
        $data = [
            'first_name'            => ArrayUtils::get($payload, 'first_name'),
            'last_name'             => ArrayUtils::get($payload, 'last_name'),
            'name'                  => ArrayUtils::get($payload, 'name'),
            'email'                 => ArrayUtils::get($payload, 'email'),
            'phone'                 => ArrayUtils::get($payload, 'phone'),
            'security_question'     => ArrayUtils::get($payload, 'security_question'),
            'security_answer'       => ArrayUtils::get($payload, 'security_answer'),
            'password'              => $password,
            'password_confirmation' => ArrayUtils::get($payload, 'password_confirmation', $password)
        ];

        if (empty($data['first_name'])) {
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
                    Session::setUserInfoWithJWT($user);

                    return ['success' => true, 'session_token' => Session::getSessionToken()];
                }
            } else {
                return ['success' => true];
            }
        }
    }

    public function getApiDocInfo()
    {
        $path = '/' . $this->getServiceName() . '/' . $this->getFullPathName();
        $eventPath = $this->getServiceName() . '.' . $this->getFullPathName('.');
        $apis = [
            [
                'path'        => $path,
                'operations'  => [
                    [
                        'method'           => 'POST',
                        'summary'          => 'register() - Register a new user in the system.',
                        'nickname'         => 'register',
                        'type'             => 'Success',
                        'event_name'       => [$eventPath . '.create'],
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
                        'responseMessages' => ApiDocUtilities::getCommonResponses([400, 500]),
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

        return ['apis' => $apis, 'models' => $models];
    }
}