<?php

declare(strict_types=1);

namespace Camelot\DoctrineSqliteForeignKeys\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class SqliteForeignKeyEnabler implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [Events::postConnect];
    }

    /** @throws \Doctrine\DBAL\DBALException */
    public function postConnect(ConnectionEventArgs $args): void
    {
        if (strtolower($args->getConnection()->getDatabasePlatform()->getName()) !== 'sqlite') {
            return;
        }

        $args->getConnection()->exec('PRAGMA foreign_keys = ON;');
    }
}
