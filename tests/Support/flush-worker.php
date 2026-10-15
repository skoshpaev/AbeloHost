<?php
declare(strict_types=1);

while (microtime(true) < (float) $argv[1]) {
    usleep(1000);
}
require dirname(__DIR__, 2) . '/bin/flush-views.php';
