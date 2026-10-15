<?php
declare(strict_types=1);

namespace App\Service\VisitorIdentity;

use App\Service\VisitorIdentity\Api\VisitorIdentityServiceInterface;
use InvalidArgumentException;
use Random\RandomException;

final class VisitorIdentityService implements VisitorIdentityServiceInterface
{
    private const string COOKIE_NAME = 'blog_visitor';
    private const int COOKIE_TTL_SECONDS = 86400;

    public function __construct(private readonly string $secret)
    {
        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('Visitor secret must contain at least 32 bytes.');
        }
    }

    /**
     * @throws RandomException
     */
    public function resolve(mixed $cookie): array
    {
        if (is_string($cookie) && preg_match('/^([a-f0-9]{64})\.([a-f0-9]{64})$/D', $cookie, $parts)
            && hash_equals(hash_hmac('sha256', $parts[1], $this->secret), $parts[2])) {
            return ['id' => $parts[1], 'cookie' => $cookie];
        }
        $id = bin2hex(random_bytes(32));
        return ['id' => $id, 'cookie' => $id . '.' . hash_hmac('sha256', $id, $this->secret)];
    }

    public function cookieName(): string
    {
        return self::COOKIE_NAME;
    }

    public function header(string $cookie, bool $secure): string
    {
        return self::COOKIE_NAME . '=' . $cookie . '; Path=/; Max-Age=' . self::COOKIE_TTL_SECONDS . '; HttpOnly; SameSite=Lax'
            . ($secure ? '; Secure' : '');
    }
}
