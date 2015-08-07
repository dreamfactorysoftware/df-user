<?php

use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Utility\Session;
use Illuminate\Support\Arr;

class UserResourceTest extends \DreamFactory\Core\Testing\UserResourceTestCase
{
    const RESOURCE = 'user';

    /************************************************
     * Testing GET
     ************************************************/

    public function testGET()
    {
        $user1 = $this->createUser(1);
        $user2 = $this->createUser(2);
        $user3 = $this->createUser(3);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $content = $rs->getContent();

        //Total 4 users including the default admin user but admin users shouldn't come up here.
        $this->assertEquals(3, count($content[static::$wrapper]));
        $this->assertTrue($this->adminCheck($content[static::$wrapper]));

        $ids = implode(',', array_column($content[static::$wrapper], 'id'));
        $this->assertEquals(implode(',', array_column([$user1, $user2, $user3], 'id')), $ids);
    }

    public function testGETWithLimitOffset()
    {
        $user1 = $this->createUser(1);
        $user2 = $this->createUser(2);
        $user3 = $this->createUser(3);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE, [ApiOptions::LIMIT => 3]);
        $content = $rs->getContent();

        $this->assertEquals(3, count($content[static::$wrapper]));

        $idsOut = implode(',', array_column($content[static::$wrapper], 'id'));
        $this->assertEquals(implode(',', array_column([$user1, $user2, $user3], 'id')), $idsOut);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE, [ApiOptions::LIMIT => 3, ApiOptions::OFFSET => 1]);
        $content = $rs->getContent();

        $this->assertEquals(2, count($content[static::$wrapper]));

        $idsOut = implode(',', array_column($content[static::$wrapper], 'id'));
        $this->assertEquals(implode(',', array_column([$user2, $user3], 'id')), $idsOut);

        $rs = $this->makeRequest(Verbs::GET, static::RESOURCE, [ApiOptions::LIMIT => 2, ApiOptions::OFFSET => 2]);
        $content = $rs->getContent();

        $this->assertEquals(1, count($content[static::$wrapper]));

        $idsOut = implode(',', array_column($content[static::$wrapper], 'id'));
        $this->assertEquals(implode(',', array_column([$user3], 'id')), $idsOut);
        $this->assertTrue($this->adminCheck($content[static::$wrapper]));
    }

    public function testPATCHPassword()
    {
        $user = $this->createUser(1);

        Arr::set($user, 'password', '1234');

        $payload = json_encode($user, JSON_UNESCAPED_SLASHES);
        $rs = $this->makeRequest(Verbs::PATCH, static::RESOURCE . '/' . $user['id'], [], $payload);
        $content = $rs->getContent();

        $this->assertFalse(Session::authenticate(['email' => $user['email'], 'password' => '1234']));
        $this->assertTrue($this->adminCheck([$content]));
    }

    protected function adminCheck($records)
    {
        if(isset($records[static::$wrapper])){
            $records = $records[static::$wrapper];
        }
        foreach ($records as $user) {
            /** @type \DreamFactory\Core\Models\User $userModel */
            $userModel = \DreamFactory\Core\Models\User::find($user['id']);

            if ($userModel->is_sys_admin) {
                return false;
            }
        }

        return true;
    }
}