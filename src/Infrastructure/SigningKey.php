<?php
declare(strict_types=1);

namespace App\Infrastructure;

use RuntimeException;

final class SigningKey
{
    public static function load(string $file): string
    {
        $configured = getenv('VISITOR_SECRET');
        if (is_string($configured) && $configured !== '') {
            if (strlen($configured) < 32) {
                throw new RuntimeException('VISITOR_SECRET must contain at least 32 bytes.');
            }
            return $configured;
        }
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open visitor signing key.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock visitor signing key.');
            }
            $key = stream_get_contents($handle);
            if ($key === '') {
                $key = bin2hex(random_bytes(32));
                if (fwrite($handle, $key) !== strlen($key) || !fflush($handle)) {
                    throw new RuntimeException('Cannot persist visitor signing key.');
                }
                chmod($file, 0600);
            }
            if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key)) {
                throw new RuntimeException('Visitor signing key is invalid.');
            }
            return $key;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
