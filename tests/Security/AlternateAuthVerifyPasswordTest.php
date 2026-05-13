<?php

namespace DreamFactory\Core\User\Tests\Security;

use DreamFactory\Core\User\Components\AlternateAuth;
use PHPUnit\Framework\TestCase;

/**
 * Security: AlternateAuth::verifyPassword() must NOT accept plaintext or MD5
 * password hashes.
 *
 * The April 2026 audit (df-user U-02) found:
 *
 *     // Check plain password.
 *     if($password === $hash){ return true; }
 *     // Check md5 hash
 *     if (md5($password) === $hash) { return true; }
 *     return password_verify($password, $hash);
 *
 * Plaintext acceptance silently allows any DB row whose `password` column
 * was populated as cleartext to authenticate. MD5 acceptance is similarly
 * insecure: MD5 is collision-prone, fast on commodity GPUs, and obsolete
 * for password storage.
 *
 * After the fix, only password_verify() (bcrypt/argon2) is honored — and
 * the function is timing-safe via password_verify itself.
 */
class AlternateAuthVerifyPasswordTest extends TestCase
{
    /**
     * Invoke protected verifyPassword() via a tiny anonymous subclass so we
     * don't need the parent constructor args.
     */
    private function verify(string $password, string $hash): bool
    {
        $auth = new class extends AlternateAuth {
            public function __construct() { /* no-op */ }
            public function callVerify(string $p, string $h): bool
            {
                return $this->verifyPassword($p, $h);
            }
        };
        return $auth->callVerify($password, $hash);
    }

    public function testRejectsPlaintextEqualPasswordAndHash(): void
    {
        // Old behaviour: returned true for $password === $hash.
        // Fixed behaviour: must return false because the stored value is
        // not a valid bcrypt/argon2 hash.
        $this->assertFalse(
            $this->verify('hunter2', 'hunter2'),
            'verifyPassword() must NOT accept plaintext-equality matches'
        );
    }

    public function testRejectsMd5HashMatch(): void
    {
        $password = 'hunter2';
        $md5Hash = md5($password);
        $this->assertFalse(
            $this->verify($password, $md5Hash),
            'verifyPassword() must NOT accept MD5 hashes (obsolete + GPU-cracked)'
        );
    }

    public function testAcceptsBcryptHash(): void
    {
        $password = 'hunter2';
        $bcryptHash = password_hash($password, PASSWORD_BCRYPT);
        $this->assertTrue(
            $this->verify($password, $bcryptHash),
            'verifyPassword() must still accept bcrypt hashes (the only legitimate path)'
        );
    }

    public function testRejectsBcryptWithWrongPassword(): void
    {
        $bcryptHash = password_hash('hunter2', PASSWORD_BCRYPT);
        $this->assertFalse(
            $this->verify('wrong', $bcryptHash),
            'verifyPassword() must reject wrong password against bcrypt hash'
        );
    }
}
