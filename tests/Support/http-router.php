<?php
declare(strict_types=1);

$root = realpath(dirname(__DIR__, 2) . '/public');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($root . $path);
if (is_string($file) && str_starts_with($file, $root . '/assets/') && is_file($file)) {
    return false;
}
require $root . '/index.php';
