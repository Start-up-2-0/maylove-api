<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\User\UserRole;
use App\Domain\User\UserStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $email = '';

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(name: 'email_verified_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 30, enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::PendingVerification;

    #[ORM\Column(length: 20, enumType: UserRole::class)]
    private UserRole $role = UserRole::User;

    #[ORM\Column(name: 'login_attempts', type: Types::SMALLINT, options: ['default' => 0])]
    private int $loginAttempts = 0;

    #[ORM\Column(name: 'locked_until', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, AuthSession> */
    #[ORM\OneToMany(targetEntity: AuthSession::class, mappedBy: 'user')]
    private Collection $sessions;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->touch();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = mb_strtolower(trim($email));
        $this->touch();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
        $this->touch();
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function markEmailVerified(): void
    {
        $this->emailVerifiedAt = new \DateTimeImmutable();
        $this->status = UserStatus::Active;
        $this->touch();
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getLoginAttempts(): int
    {
        return $this->loginAttempts;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function registerFailedLoginAttempt(): void
    {
        ++$this->loginAttempts;
        $this->touch();
    }

    public function resetLoginAttempts(): void
    {
        $this->loginAttempts = 0;
        $this->lockedUntil = null;
        $this->touch();
    }

    public function applyTemporaryLockout(int $lockoutMinutes): void
    {
        $this->lockedUntil = new \DateTimeImmutable(sprintf('+%d minutes', $lockoutMinutes));
        $this->touch();
    }

    public function isBlocked(int $maxLoginAttempts): bool
    {
        if ($this->lockedUntil !== null && $this->lockedUntil > new \DateTimeImmutable()) {
            return true;
        }

        return $this->loginAttempts >= $maxLoginAttempts;
    }

    public function canAuthenticate(int $maxLoginAttempts): bool
    {
        if ($this->isBlocked($maxLoginAttempts)) {
            return false;
        }

        return $this->status === UserStatus::Active
            || $this->status === UserStatus::PendingVerification;
    }

    public function isActiveAccount(): bool
    {
        return $this->status === UserStatus::Active;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
