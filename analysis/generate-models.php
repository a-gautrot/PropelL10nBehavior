<?php

declare(strict_types=1);

use Gautrot\Propel\Behavior\L10n\Tests\GenerationTest;
use Gautrot\Propel\Behavior\L10n\Tests\Model\BookQueryTest;
use Gautrot\Propel\Behavior\L10n\Tests\Model\LocaleParameterTest;
use Gautrot\Propel\Behavior\L10n\Tests\Model\ProductTest;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

require_once dirname(__DIR__) . '/tests/bootstrap.php';
require_once dirname(__DIR__) . '/tests/model/BookQueryTest.php';
require_once dirname(__DIR__) . '/tests/model/ProductTest.php';
require_once dirname(__DIR__) . '/tests/model/LocaleParameterTest.php';
require_once dirname(__DIR__) . '/tests/QuerySecurityTest.php';
require_once dirname(__DIR__) . '/tests/GenerationTest.php';

// Export actual generated sources before QuickBuilder replaces its virtual filesystem.
$directory = dirname(__DIR__) . '/.phpstan.cache/models';
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    throw new RuntimeException('Unable to create the analysis model directory.');
}
$nullableContracts = [];
$previousDsn = getenv('TEST_DSN');
putenv('TEST_DSN=sqlite::memory:');
try {
    $fixtures = [
        BookQueryTest::class => 'setUpBeforeClass',
        ProductTest::class => 'setUpBeforeClass',
        LocaleParameterTest::class => 'setUpBeforeClass',
        QuerySecurityTest::class => 'setUpBeforeClass',
        GenerationTest::class => 'buildModels',
    ];
    foreach ($fixtures as $fixture => $build) {
        $before = get_declared_classes();
        $fixture::$build();
        $copied = [];
        foreach (array_diff(get_declared_classes(), $before) as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            if ($file === false || strpos($file, 'vfs://') !== 0 || isset($copied[$file])) {
                continue;
            }
            $code = file_get_contents($file);
            if ($code === false || file_put_contents($directory . '/' . basename($file), $code) === false) {
                throw new RuntimeException('Unable to export a generated analysis model.');
            }
            $copied[$file] = true;
        }
        // Required database columns may still be null on a new, unsaved object.
        foreach (array_diff(get_declared_classes(), $before) as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->implementsInterface(
                    ActiveRecordInterface::class
                )) {
                continue;
            }
            $instance = $reflection->newInstance();
            $tableMapClass = $class::TABLE_MAP;
            foreach ($tableMapClass::getTableMap()->getColumns() as $column) {
                $getter = 'get' . $column->getPhpName();
                if (!$reflection->hasMethod($getter)) {
                    continue;
                }
                $method = $reflection->getMethod($getter);
                $comment = $method->getDocComment();
                if ($comment === false || preg_match('/@return (string|int|float|bool)\\s/', $comment, $match) !== 1) {
                    continue;
                }
                if ($method->getNumberOfRequiredParameters() !== 0 || $method->invoke($instance) !== null) {
                    continue;
                }
                $nullableContracts[$method->getDeclaringClass()->getName()][$getter] = $match[1] . '|null';
            }
        }
    }
} finally {
    putenv($previousDsn === false ? 'TEST_DSN' : 'TEST_DSN=' . $previousDsn);
}
$stub = "<?php\n";
foreach ($nullableContracts as $class => $methods) {
    $separator = strrpos($class, '\\');
    $namespace = $separator === false ? '' : substr($class, 0, $separator);
    $name = $separator === false ? $class : substr($class, $separator + 1);
    $stub .= "namespace $namespace {\nclass $name {\n";
    foreach ($methods as $method => $type) {
        $stub .= "/** @return $type */\npublic function $method() {}\n";
    }
    $stub .= "}\n}\n";
}
if (file_put_contents(dirname(__DIR__) . '/.phpstan.cache/nullable-getters.stub', $stub) === false) {
    throw new RuntimeException('Unable to export nullable getter contracts.');
}
