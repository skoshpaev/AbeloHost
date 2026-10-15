<?php
declare(strict_types=1);

use App\Infrastructure\Database;
use App\Infrastructure\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';
try {
    (new Migrator(Database::connect(), dirname(__DIR__) . '/database/migrations'))->migrate();
    fwrite(STDOUT, "Migrations applied.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Migration failed: {$error->getMessage()}\n");
    exit(1);
}
