<?php declare(strict_types=1);
namespace Careminate\Providers\Contracts;

interface ServiceProviderInterface
{
    public function register(): void;
}
