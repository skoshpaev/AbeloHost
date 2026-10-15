<?php
declare(strict_types=1);

use App\Infrastructure\Database;
use App\Infrastructure\NamedLock;
use App\Repository\RedisViewBufferRepository;
use App\Repository\ViewBatchRepository;
use App\Service\ViewFlusher\ViewFlusherService;

require_once dirname(__DIR__) . '/vendor/autoload.php';
$pdo = Database::connect();

return new ViewFlusherService(new RedisViewBufferRepository(), new ViewBatchRepository($pdo), new NamedLock($pdo));
