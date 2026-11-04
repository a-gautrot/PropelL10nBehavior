<?php

declare(strict_types=1);

use PHPUnit\Runner\Version;
use PHPUnit\TextUI\Application;
use PHPUnit\TextUI\Command;

require dirname(__DIR__) . '/vendor/autoload.php';

$configuration = version_compare(Version::id(), '10.0.0', '<') ? 'phpunit-legacy.dist.xml' : 'phpunit.dist.xml';

$arguments = $_SERVER['argv'] ?? null;
if (!is_array($arguments) || $arguments === []) {
    throw new RuntimeException('The test runner requires CLI arguments.');
}
$cliArguments = [];
foreach ($arguments as $argument) {
    if (!is_string($argument)) {
        throw new RuntimeException('CLI arguments must be strings.');
    }
    $cliArguments[] = $argument;
}
$configuredArguments = array_merge(
    [$cliArguments[0], '--configuration', dirname(__DIR__) . '/' . $configuration],
    array_slice($cliArguments, 1)
);
$_SERVER['argv'] = $configuredArguments;
$_SERVER['argc'] = count($_SERVER['argv']);

if (version_compare(Version::id(), '10.0.0', '<')) {
    Command::main();
} else {
    exit((new Application())->run($configuredArguments));
}
