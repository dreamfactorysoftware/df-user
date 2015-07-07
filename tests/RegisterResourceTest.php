<?php
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Illuminate\Support\Arr;

class RegisterResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'register';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test12345678',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => true
    ];

    public function tearDown()
    {
        $email = Arr::get($this->user1, 'email');
        User::whereEmail($email)->delete();

        parent::tearDown();
    }

    public function testPOSTRegister()
    {
        $u = $this->user1;
        $password = Arr::get($u, 'password');
        $payload = [
            'first_name'            => Arr::get($u, 'first_name'),
            'last_name'             => Arr::get($u, 'last_name'),
            'name'                  => Arr::get($u, 'name'),
            'email'                 => Arr::get($u, 'email'),
            'phone'                 => Arr::get($u, 'phone'),
            'security_question'     => Arr::get($u, 'security_question'),
            'security_answer'       => Arr::get($u, 'security_answer'),
            'password'              => $password,
            'password_confirmation' => Arr::get($u, 'password_confirmation', $password)
        ];

        Session::setUserInfoWithJWT(User::find(1));
        $r = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $c = $r->getContent();

        $this->assertTrue(Arr::get($c, 'success'));

        Session::set('role.name', 'test');
        Session::set('role.id', 1);

        $this->service = ServiceHandler::getService('user');
        $r = $this->makeRequest(
            Verbs::POST,
            'session',
            [],
            ['email' => Arr::get($u, 'email'), 'password' => Arr::get($u, 'password')]
        );
        $c = $r->getContent();

        $this->assertTrue(!empty(Arr::get($c, 'session_id')));
    }
}