<?php
declare(strict_types=1);

namespace App\Service\ViewFlusher\Api;

interface ViewFlusherServiceInterface
{
    public function flush(bool $sealActive = true): int;
}
