<?php
declare(strict_types=1);

namespace App\Service\VisitorIdentity\Api;

interface VisitorIdentityServiceInterface
{
    public function resolve(mixed $cookie): array;

    public function cookieName(): string;

    public function header(string $cookie, bool $secure): string;
}
