<?php
declare(strict_types=1);

try {
    $flusher = require dirname(__DIR__) . '/config/view-flusher.php';
    $count = $flusher->flush();
    fwrite(STDOUT, "View flush complete: $count new batches applied.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'View flush failed: ' . $error->getMessage() . "\n");
    exit(1);
}
