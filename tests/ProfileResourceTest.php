<?php
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use DreamFactory\Library\Utility\Enums\Verbs;
use Illuminate\Support\Arr;

class ProfileResourceTest extends \DreamFactory\Core\Testing\TestCase
{
    const RESOURCE = 'profile';

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

    public function tearDown()
    {
        $this->deleteUser(1);

        parent::tearDown();
    }

    public function testNoProfileFound()
    {
        $this->setExpectedException('\DreamFactory\Core\Exceptions\NotFoundException');
        $this->makeRequest(Verbs::GET, static::RESOURCE);
    }

    public function testGETProfile()
    {
        $user = $this->createUser(1);
        $userModel = User::find($user['id']);
        Session::setUserInfoWithJWT($userModel);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $c = $rs->getContent();

        $e = [
            'first_name'        => Arr::get($user, 'first_name'),
            'last_name'         => Arr::get($user, 'last_name'),
            'name'              => Arr::get($user, 'name'),
            'email'             => Arr::get($user, 'email'),
            'phone'             => Arr::get($user, 'phone'),
            'security_question' => Arr::get($user, 'security_question')
        ];

        $this->assertEquals($e, $c);
    }

    public function testPOSTProfile()
    {
        $user = $this->createUser(1);
        $userModel = User::find($user['id']);
        Session::setUserInfoWithJWT($userModel);

        $fName = 'Jack';
        $lName = 'Smith';
        $name = 'Jack';
        $email = 'jsmith@example.com';
        $this->user1['email'] = $email;
        $phone = '123-475-7383';
        $sQuestion = 'Foo?';
        $sAnswer = 'bar';

        $payload = [
            'first_name'        => $fName,
            'last_name'         => $lName,
            'name'              => $name,
            'email'             => $email,
            'phone'             => $phone,
            'security_question' => $sQuestion,
            'security_answer'   => $sAnswer
        ];

        $r = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $c = $r->getContent();
        $this->assertTrue(Arr::get($c, 'success'));

        $userModel = User::find($user['id']);
        $r = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $c = $r->getContent();

        $this->assertTrue(Hash::check($sAnswer, $userModel->security_answer));

        unset($payload['security_answer']);
        $this->assertEquals($payload, $c);
    }

    /************************************************
     * Helper methods
     ************************************************/

    protected function createUser($num)
    {
        $user = $this->{'user' . $num};
        $payload = json_encode([$user], JSON_UNESCAPED_SLASHES);

        $this->service = ServiceHandler::getService('system');
        $rs = $this->makeRequest(
            Verbs::POST,
            'user',
            [ApiOptions::FIELDS => '*', ApiOptions::RELATED => 'user_lookup_by_user_id'],
            $payload
        );
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