<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
while (microtime(true) < (float) ($argv[1] ?? 0)) {
    usleep(1000);
}
require dirname(__DIR__, 2) . '/bin/seed.php';
