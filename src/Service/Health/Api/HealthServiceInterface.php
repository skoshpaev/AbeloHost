<?php
declare(strict_types=1);

namespace App\Service\Health\Api;

interface HealthServiceInterface
{
    public function check(): void;
}
