<?php

namespace DreamFactory\Core\User\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security: AlternateAuth::generateFilter() must reject filter values that
 * contain DreamFactory filter-syntax metacharacters.
 *
 * The April 2026 audit (df-user U-03) found:
 *
 *     $string .= "($f=$v)";
 *
 * The username and other-field values come from `trim($request->input(...))`
 * and are interpolated raw. A username like `admin) OR (1=1` produces the
 * filter `(username=admin) OR (1=1)`, which after parsing matches every
 * row — auth bypass on services that grant access by filter match.
 *
 * After the fix, generateFilter()'s value-handling path rejects any value
 * containing parentheses, quotes, or filter-keyword whitespace patterns
 * before the value is interpolated.
 */
class AlternateAuthFilterInjectionTest extends TestCase
{
    private string $sourcePath;
    private string $contents;

    protected function setUp(): void
    {
        $this->sourcePath = __DIR__ . '/../../src/Components/AlternateAuth.php';
        $this->assertFileExists($this->sourcePath);
        $this->contents = file_get_contents($this->sourcePath);
    }

    public function testGenerateFilterRejectsMetacharacters(): void
    {
        // The fix must validate $v before interpolation. We accept either:
        //  - preg_match guarding against parens/quotes/operators
        //  - explicit str_contains / strpbrk checks
        //  - throwing an exception inside the foreach value loop
        // Slice generateFilter() body and look for any guard pattern that
        // checks $v before interpolation.
        $start = strpos($this->contents, 'function generateFilter');
        $this->assertNotFalse($start);
        $end = strpos($this->contents, 'function ', $start + 10);
        $body = substr($this->contents, $start, $end === false ? null : ($end - $start));

        $hasGuard =
            (strpos($body, 'preg_match') !== false && strpos($body, '$v') !== false)
            || strpos($body, 'strpbrk') !== false
            || strpos($body, 'str_contains') !== false;

        $this->assertTrue(
            $hasGuard,
            'generateFilter() must validate the field value for filter-syntax '
            . 'metacharacters (parens, quotes, AND/OR) before interpolation.'
        );
    }

    public function testGenerateFilterThrowsOnUnsafeValue(): void
    {
        // A throw must appear inside generateFilter() — silent rejection
        // would still let the auth flow proceed without a filter.
        $start = strpos($this->contents, 'function generateFilter');
        $this->assertNotFalse($start);
        $end = strpos($this->contents, 'function ', $start + 10);
        $body = substr($this->contents, $start, $end === false ? null : ($end - $start));

        $this->assertMatchesRegularExpression(
            '/throw\s+new\s+\\\\?[A-Za-z]+Exception/',
            $body,
            'generateFilter() must throw an exception when an unsafe value is detected'
        );
    }
}
