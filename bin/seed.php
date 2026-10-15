<?php
declare(strict_types=1);

use App\Infrastructure\Database;
use App\Service\Seeder\SeederService;

require dirname(__DIR__) . '/vendor/autoload.php';
try {
    $result = new SeederService(Database::connect(), dirname(__DIR__) . '/database/fixtures/blog.php')->seed();
    fwrite(STDOUT, "Seed complete: {$result['categories']} categories, {$result['articles']} articles.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Seed failed: {$error->getMessage()}\n");
    exit(1);
}
