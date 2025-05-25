<?php declare(strict_types=1);
namespace Careminate\Databases\Dbal\EventDispatcher;

use Careminate\Databases\Dbal\EntityManager\Entity;

class PostPersist extends Event
{
    public function __construct(private Entity $subject)
    {
    }
}