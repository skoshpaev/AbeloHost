<?php
declare(strict_types=1);

use App\Infrastructure\RedisConnection;
use App\Repository\RedisViewBufferRepository;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$buffer = new RedisViewBufferRepository(RedisConnection::connect());
while (microtime(true) < (float) $argv[4]) {
    usleep(1000);
}
$buffer->record((int) $argv[1], $argv[2], (int) $argv[3]);
