<?php declare(strict_types=1);
namespace Careminate\Authentication\Contracts;

interface SessionAuthInterface
{
    public function authenticate(string $email, string $password): bool;

    public function login(AuthUserInterface $user);

    public function logout();

    public function getUser(): AuthUserInterface;
}