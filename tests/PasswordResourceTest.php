<?php
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Illuminate\Support\Arr;

class PasswordResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'password';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test1234',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => true
    ];

    protected $user2 = [
        'name'                   => 'Jane Doe',
        'first_name'             => 'Jane',
        'last_name'              => 'Doe',
        'email'                  => 'jadoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => true,
        'user_lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => false
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => true
            ]
        ]
    ];

    protected $user3 = [
        'name'                   => 'Dan Doe',
        'first_name'             => 'Dan',
        'last_name'              => 'Doe',
        'email'                  => 'ddoe@dreamfactory.com',
        'password'               => 'test1234',
        'is_active'              => true,
        'user_lookup_by_user_id' => [
            [
                'name'    => 'test',
                'value'   => '1234',
                'private' => false
            ],
            [
                'name'    => 'test2',
                'value'   => '5678',
                'private' => true
            ],
            [
                'name'    => 'test3',
                'value'   => '56789',
                'private' => true
            ]
        ]
    ];

    public function tearDown()
    {
        $this->deleteUser(1);
        $this->deleteUser(2);
        $this->deleteUser(3);

        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();

        Session::set('role.name', 'test');
        Session::set('role.id', 1);
    }

    /************************************************
     * Password sub-resource test
     ************************************************/

    public function testGET()
    {
        $this->setExpectedException('\DreamFactory\Core\Exceptions\BadRequestException');
        $this->makeRequest(Verbs::GET, static::RESOURCE);
    }

    public function testDELETE()
    {
        $this->setExpectedException('\DreamFactory\Core\Exceptions\BadRequestException');
        $this->makeRequest(Verbs::DELETE, static::RESOURCE);
    }

    public function testPasswordChange()
    {
        $user = $this->createUser(1);

        $this->makeRequest(
            Verbs::POST,
            'session',
            [],
            ['email' => $user['email'], 'password' => $this->user1['password']]);

        $this->service = ServiceHandler::getService($this->serviceId);
        $rs = $this->makeRequest(
            Verbs::POST,
            static::RESOURCE,
            [],
            ['old_password' => $this->user1['password'], 'new_password' => '123456']
        );
        $content = $rs->getContent();
        $this->assertTrue($content['success']);

        $this->service = ServiceHandler::getService($this->serviceId);
        $this->makeRequest(Verbs::DELETE, 'session');

        $rs = $this->makeRequest(Verbs::POST, 'session', [], ['email' => $user['email'], 'password' => '123456']);
        $content = $rs->getContent();
        $this->assertTrue(!empty($content['session_id']));
    }

    public function testPasswordResetUsingSecurityQuestion()
    {
        $user = $this->createUser(1);

        $rs = $this->makeRequest(Verbs::POST, static::RESOURCE, ['reset' => 'true'], ['email' => $user['email']]);
        $content = $rs->getContent();

        $this->assertEquals($this->user1['security_question'], $content['security_question']);

        $rs = $this->makeRequest(
            Verbs::POST,
            static::RESOURCE,
            [],
            ['email'           => $user['email'],
             'security_answer' => $this->user1['security_answer'],
             'new_password'    => '778877'
            ]
        );
        $content = $rs->getContent();
        $this->assertTrue($content['success']);

        $this->service = ServiceHandler::getService($this->serviceId);
        $rs = $this->makeRequest(Verbs::POST, 'session', [], ['email' => $user['email'], 'password' => '778877']);
        $content = $rs->getContent();
        $this->assertTrue(!empty($content['session_id']));
    }

    public function testPasswordResetUsingConfirmationCode()
    {
        if (!$this->serviceExists('mymail')) {
            $emailService = \DreamFactory\Core\Models\Service::create(
                [
                    "name"        => "mymail",
                    "label"       => "Test mail service",
                    "description" => "Test mail service",
                    "is_active"   => true,
                    "type"        => "local_email",
                    "mutable"     => true,
                    "deletable"   => true,
                    "config"      => [
                        "driver"  => "sendmail",
                        "command" => "/usr/sbin/sendmail -bs"
                    ]
                ]
            );

            $userConfig = \DreamFactory\Core\User\Models\UserConfig::find(4);
            $userConfig->password_email_service_id = $emailService->id;
            $userConfig->save();
        }

        if (!\DreamFactory\Core\Models\EmailTemplate::whereName('mytemplate')->exists()) {
            $template = \DreamFactory\Core\Models\EmailTemplate::create(
                [
                    'name'        => 'mytemplate',
                    'description' => 'test',
                    'to'          => $this->user2['email'],
                    'subject'     => 'rest password test',
                    'body_text'   => 'link {link}'
                ]
            );

            $userConfig = \DreamFactory\Core\User\Models\UserConfig::find(4);
            $userConfig->password_email_template_id = $template->id;
            $userConfig->save();
        }

        Arr::set($this->user2, 'email', 'arif@dreamfactory.com');
        $user = $this->createUser(2);

        Config::set('mail.pretend', true);

        $rs = $this->makeRequest(Verbs::POST, static::RESOURCE, ['reset' => 'true'], ['email' => $user['email']]);
        $content = $rs->getContent();
        $this->assertTrue($content['success']);

        /** @var User $userModel */
        $userModel = User::find($user['id']);
        $code = $userModel->confirm_code;

        $rs = $this->makeRequest(
            Verbs::POST,
            static::RESOURCE,
            ['login' => 'true'],
            ['email' => $user['email'], 'code' => $code, 'new_password' => '778877']
        );
        $content = $rs->getContent();
        $this->assertTrue($content['success']);
        $this->assertTrue(Session::isAuthenticated());

        $userModel = User::find($user['id']);
        $this->assertEquals('y', $userModel->confirm_code);

        $this->service = ServiceHandler::getService($this->serviceId);
        $rs = $this->makeRequest(Verbs::POST, 'session', [], ['email' => $user['email'], 'password' => '778877']);
        $content = $rs->getContent();
        $this->assertTrue(!empty($content['session_id']));
    }

    /************************************************
     * Helper methods
     ************************************************/

    protected function createUser($num)
    {
        $user = $this->{'user' . $num};
        $payload = json_encode([$user], JSON_UNESCAPED_SLASHES);

        $this->service = ServiceHandler::getService('system');
        $rs =
            $this->makeRequest(Verbs::POST, 'user', [ApiOptions::FIELDS => '*', ApiOptions::RELATED => 'user_lookup_by_user_id'], $payload);
        $this->service = ServiceHandler::getService($this->serviceId);

        $data = $rs->getContent();

        return Arr::get($data, static::$wrapper . '.0');
    }

    protected function deleteUser($num)
    {
        $user = $this->{'user' . $num};
        $email = Arr::get($user, 'email');
        \DreamFactory\Core\Models\User::whereEmail($email)->delete();
    }
}