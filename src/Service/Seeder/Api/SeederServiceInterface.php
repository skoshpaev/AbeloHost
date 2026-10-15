<?php
declare(strict_types=1);

namespace App\Service\Seeder\Api;

interface SeederServiceInterface
{
    public function seed(): array;
}
