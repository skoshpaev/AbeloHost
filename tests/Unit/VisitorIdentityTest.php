<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Service\VisitorIdentity\VisitorIdentityService;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

final class VisitorIdentityTest extends TestCase
{
    /**
     * @throws RandomException
     */
    public function testSignedCookieRestoresVisitorAndRejectsTampering(): void
    {
        $identity = new VisitorIdentityService(str_repeat('secret', 10));
        $first = $identity->resolve(null);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $first['id']);
        self::assertSame($first, $identity->resolve($first['cookie']));
        $tampered = str_repeat('a', 64) . substr($first['cookie'], 64);
        self::assertNotSame($first['id'], $identity->resolve($tampered)['id']);
        self::assertNotSame($first['id'], $identity->resolve([$first['cookie']])['id']);
        self::assertNotSame($first['id'], $identity->resolve('invalid')['id']);
    }

    public function testCookieAttributes(): void
    {
        $identity = new VisitorIdentityService(str_repeat('secret', 10));
        $cookie = $identity->header('value', true);
        self::assertStringContainsString('HttpOnly', $cookie);
        self::assertStringContainsString('SameSite=Lax', $cookie);
        self::assertStringContainsString('Max-Age=86400', $cookie);
        self::assertStringContainsString('; Secure', $cookie);
        self::assertStringNotContainsString('; Secure', $identity->header('value', false));
    }
}
