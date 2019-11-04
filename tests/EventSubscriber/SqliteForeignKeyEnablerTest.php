<?php

declare(strict_types=1);

namespace Camelot\DoctrineSqliteForeignKeys\Tests\EventSubscriber;

use Camelot\DoctrineSqliteForeignKeys\EventSubscriber\SqliteForeignKeyEnabler;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\DBAL\Event\ConnectionEventArgs;

/**
 * @covers \Camelot\DoctrineSqliteForeignKeys\EventSubscriber\SqliteForeignKeyEnabler
 *
 * @internal
 */
final class SqliteForeignKeyEnablerTest extends TestCase
{
    private static array $connectionParams = [
        'memory' => true,
        'driver' => 'pdo_sqlite',
    ];

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([Events::postConnect], (new SqliteForeignKeyEnabler())->getSubscribedEvents());
    }

    public function testPostConnectForeignKeyNotEnabled(): void
    {
        static::assertConnectionPragmaForeignKeys('0', $this->getConnection(new EventManager()));
    }

    /** @depends testPostConnectForeignKeyNotEnabled */
    public function testPostConnect(): void
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SqliteForeignKeyEnabler());
        $connection = $this->getConnection($evm);

        static::assertConnectionPragmaForeignKeys('1', $connection);
    }

    public function testSqlitePlatformOnly(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform
            ->expects(static::once())
            ->method('getName')
            ->willReturn('pandas')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(static::once())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;
        $connection
            ->expects(static::never())
            ->method('exec')
        ;

        $args = new ConnectionEventArgs($connection);
        $foreignKeyEnabler = new SqliteForeignKeyEnabler();
        $foreignKeyEnabler->postConnect($args);
    }

    private function getConnection(EventManager $evm): Connection
    {
        $fs = new Filesystem();
        if (self::$connectionParams['path'] ?? false && $fs->exists(self::$connectionParams['path'])) {
            $fs->remove(self::$connectionParams['path']);
        }

        $config = new Configuration();
        $connection = DriverManager::getConnection(self::$connectionParams, $config, $evm);
        $connection->connect();

        static::assertEmpty($connection->getSchemaManager()->listTables());

        return $connection;
    }

    private static function assertConnectionPragmaForeignKeys(string $expected, Connection $connection): void
    {
        $stmt = $connection->prepare('PRAGMA foreign_keys;');
        $stmt->execute();
        $result = $stmt->fetch();

        static::assertSame($expected, $result['foreign_keys'] ?? null, 'PRAGMA foreign_keys was not "1"');
    }
}

