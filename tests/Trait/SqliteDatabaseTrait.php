<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\Trait;

use Contenir\Db\Model\EntityManager;
use Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Database\Schema;
use PDO;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sqlite\AdapterPlatform;
use PhpDb\Sqlite\Pdo\Connection;
use PhpDb\Sqlite\Pdo\Driver;

use function array_keys;
use function array_map;
use function implode;
use function sprintf;

/**
 * A fresh in-memory SQLite database with the resource tables per test, so
 * nothing survives between tests. Call setUpDatabase() from setUp().
 */
trait SqliteDatabaseTrait
{
    private AdapterInterface $adapter;

    private EntityManager $em;

    private PDO $pdo;

    /**
     * @param array<string, int|string|null> $row
     */
    private function insert(string $table, array $row): void
    {
        $statement = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_keys($row)),
            implode(', ', array_map(static fn(string $column): string => ":{$column}", array_keys($row))),
        ));
        $statement->execute($row);
    }

    /**
     * Insert a resource row; unspecified columns take page defaults.
     *
     * @param array<string, int|string|null> $row
     */
    private function insertResource(array $row): void
    {
        $this->insert('resource', [
            'resource_type_id' => 'page',
            'active'           => 'active',
            'visible'          => 1,
            ...$row,
        ]);
    }

    private function setUpDatabase(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach (Schema::create() as $statement) {
            $this->pdo->exec($statement);
        }

        $driver        = new Driver(connection: new Connection($this->pdo));
        $this->adapter = new Adapter($driver, new AdapterPlatform($driver));
        $this->em      = new EntityManager($this->adapter);
    }
}
