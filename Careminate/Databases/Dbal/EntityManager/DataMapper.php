<?php declare(strict_types=1);
namespace Careminate\Databases\Dbal\EntityManager;

use Doctrine\DBAL\Connection;
use Careminate\Databases\Dbal\EventDispatcher\PostPersist;
use Careminate\Databases\Dbal\EventDispatcher\EventDispatcher;

class DataMapper
{
    public function __construct(
        private Connection $connection,
        private EventDispatcher $eventDispatcher
    ){}

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function save(Entity $subject): int|string|null
    {
        // Dispatch PostPersist event
        $this->eventDispatcher->dispatch(new PostPersist($subject));

        // Return lastInsertId
        return $this->connection->lastInsertId();
    }
    
}
