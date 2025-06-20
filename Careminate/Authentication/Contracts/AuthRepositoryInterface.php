<?php declare(strict_types=1);
namespace Careminate\Authentication\Contracts;

interface AuthRepositoryInterface
{
     public function findByEmail(string $email): ?AuthUserInterface;
     public function findById(int|string $id): ?AuthUserInterface;
}