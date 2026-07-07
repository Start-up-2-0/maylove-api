<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'auth_sessions')]
#[ORM\Index(name: 'idx_auth_sessions_access_token_hash', columns: ['access_token_hash'])]
#[ORM\Index(name: 'idx_auth_sessions_refresh_token_hash', columns: ['refresh_token_hash'])]
#[ORM\Index(name: 'idx_auth_sessions_user_revoked', columns: ['user_id', 'revoked_at'])]
class AuthSession
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'access_token_hash', length: 64)]
    private string $accessTokenHash = '';

    #[ORM\Column(name: 'refresh_token_hash', length: 64)]
    private string $refreshTokenHash = '';

    #[ORM\Column(name: 'login_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $loginAt;

    #[ORM\Column(name: 'access_token_expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $accessTokenExpiresAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'last_renewed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastRenewedAt = null;

    #[ORM\Column(name: 'revoked_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'user_agent', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'metadata_json', type: Types::JSON)]
    private array $metadataJson = [];

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->loginAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAccessTokenHash(): string
    {
        return $this->accessTokenHash;
    }

    public function setAccessTokenHash(string $accessTokenHash): void
    {
        $this->accessTokenHash = $accessTokenHash;
    }

    public function getRefreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    public function setRefreshTokenHash(string $refreshTokenHash): void
    {
        $this->refreshTokenHash = $refreshTokenHash;
    }

    public function getLoginAt(): \DateTimeImmutable
    {
        return $this->loginAt;
    }

    public function getAccessTokenExpiresAt(): \DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(\DateTimeImmutable $accessTokenExpiresAt): void
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getLastRenewedAt(): ?\DateTimeImmutable
    {
        return $this->lastRenewedAt;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setMetadataJson(array $metadataJson): void
    {
        $this->metadataJson = $metadataJson;
    }

    public function isActive(\DateTimeImmutable $now): bool
    {
        return $this->revokedAt === null && $this->expiresAt > $now;
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt = $now;
    }

    public function renewExpiration(\DateTimeImmutable $now, \DateTimeImmutable $newExpiresAt): void
    {
        $this->lastRenewedAt = $now;
        $this->expiresAt = $newExpiresAt;
    }
}
