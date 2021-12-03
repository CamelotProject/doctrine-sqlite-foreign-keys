<?php

declare(strict_types=1);

namespace Camelot\DoctrineSqliteForeignKeys\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\SqlitePlatform;

final class SqliteForeignKeyEnabler implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [Events::postConnect];
    }

    public function postConnect(ConnectionEventArgs $args): void
    {
        if (!$args->getConnection()->getDatabasePlatform() instanceof SqlitePlatform) {
            return;
        }

        $args->getConnection()->executeQuery('PRAGMA foreign_keys = ON;');
    }
}
