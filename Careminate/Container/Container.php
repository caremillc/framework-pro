<?php  declare(strict_types=1);
namespace Careminate\Container;

use Psr\Container\ContainerInterface;
use Careminate\Exceptions\ContainerException;


class Container implements ContainerInterface
{
    private array $container = [];

    public function add(string $id, ?string $concrete = null)
    {
        if ($concrete === null) {
            if (!class_exists($id)) {
                throw new ContainerException("Service $id could not be found");
            }

            $concrete = $id;
        }

        $this->container[$id] = $concrete;
    }

    public function get(string $id)
    {
        return new $this->container[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->container);
    }
}
