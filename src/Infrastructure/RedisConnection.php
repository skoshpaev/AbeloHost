<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Redis;
use RuntimeException;

final class RedisConnection
{
    public static function connect(): Redis
    {
        $redis = new Redis();
        if (!$redis->connect(getenv('REDIS_HOST') ?: 'redis', (int)(getenv('REDIS_PORT') ?: 6379), 1.0)) {
            throw new RuntimeException('Redis connection failed.');
        }
        $redis->setOption(Redis::OPT_READ_TIMEOUT, 1.0);
        $password = getenv('REDIS_PASSWORD');
        if ($password !== false && $password !== '' && !$redis->auth($password)) {
            throw new RuntimeException('Redis authentication failed.');
        }
        if (!$redis->select((int)(getenv('REDIS_DATABASE') ?: 0))) {
            throw new RuntimeException('Redis database selection failed.');
        }
        return $redis;
    }
}
