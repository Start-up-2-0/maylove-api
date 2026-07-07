<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\UserRole;
use App\Infrastructure\Persistence\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

final class MayLoveUser implements UserInterface
{
    public function __construct(
        private readonly User $user,
        private readonly string $sessionId,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getRoles(): array
    {
        return match ($this->user->getRole()) {
            UserRole::Admin => ['ROLE_ADMIN', 'ROLE_USER'],
            UserRole::User => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->user->getId();
    }
}
