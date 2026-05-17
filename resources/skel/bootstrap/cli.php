<?php

declare(strict_types=1);

use DevinciIT\Blprnt\Core\CLIBootstrap;

foreach (
    [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php',
    ] as $autoloadFile
) {
    if (is_file($autoloadFile)) {
        require $autoloadFile;
        break;
    }
}

if (is_file(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

if (!class_exists(\DevinciIT\Blprnt\Core\CLIBootstrap::class)) {
    throw new \RuntimeException('Unable to load Blprnt CLI bootstrap dependencies.');
}

$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
$basePath = dirname($scriptFile);

return CLIBootstrap::builder($basePath)->build();
