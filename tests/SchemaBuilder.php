<?php

declare(strict_types=1);

namespace Gossi\Propel\Behavior\L10n\Tests;

use DOMDocument;
use InvalidArgumentException;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Generator\Util\QuickBuilder;
use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Adapter\Pdo\PgsqlAdapter;
use Propel\Runtime\Adapter\Pdo\SqliteAdapter;
use Propel\Runtime\Connection\ConnectionWrapper;

/**
 * # SchemaBuilder
 * Builds the fixtures against the database selected by TEST_DSN.
 */
final class SchemaBuilder
{
    /**
     * @param string $schema
     * @return ConnectionWrapper
     * @throws \Random\RandomException
     */
    public static function buildSchema(string $schema): ConnectionWrapper
    {
        $dsn = getenv('TEST_DSN') ?: 'sqlite::memory:';

        switch (strtok($dsn, ':')) {
            case 'mysql':
                $platform = new MysqlPlatform();
                $adapter = new MysqlAdapter();
                break;
            case 'pgsql':
                $platform = new PgsqlPlatform();
                $adapter = new PgsqlAdapter();
                break;
            case 'sqlite':
                $platform = new SqlitePlatform();
                $adapter = new SqliteAdapter();
                break;
            default:
                throw new InvalidArgumentException('TEST_DSN must use mysql, pgsql, or sqlite.');
        }

        // Keep model names stable while isolating tables between test runs.
        $document = new DOMDocument();
        $document->loadXML($schema);
        $suffix = '_' . bin2hex(random_bytes(6));

        foreach ($document->getElementsByTagName('table') as $table) {
            $name = $table->getAttribute('name');

            if (!$table->hasAttribute('phpName')) {
                $table->setAttribute('phpName', str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
            }

            $table->setAttribute('name', $name . $suffix);
        }

        foreach ($document->getElementsByTagName('foreign-key') as $foreignKey) {
            $foreignKey->setAttribute('foreignTable', $foreignKey->getAttribute('foreignTable') . $suffix);
        }

        $builder = new QuickBuilder();
        $builder->setPlatform($platform);
        $builder->setSchema($document->saveXML());

        return $builder->build($dsn, (getenv('TEST_USER')) ?: null, (getenv('TEST_PASSWORD')) ?: null, $adapter);
    }
}
