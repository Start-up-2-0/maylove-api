<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Album\AlbumVisitorTributeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_visitor_tributes')]
#[ORM\Index(name: 'idx_album_tributes_album_created', columns: ['album_id', 'created_at'])]
class AlbumVisitorTribute
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\Column(length: 16, enumType: AlbumVisitorTributeType::class)]
    private AlbumVisitorTributeType $type;

    #[ORM\Column(name: 'author_name', length: 80)]
    private string $authorName;

    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(name: 'ip_hash', length: 64, nullable: true)]
    private ?string $ipHash = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Album $album,
        AlbumVisitorTributeType $type,
        string $authorName,
        string $message,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $ipHash = null,
    ) {
        $this->id = Uuid::v7();
        $this->album = $album;
        $this->type = $type;
        $this->authorName = $authorName;
        $this->message = $message;
        $this->expiresAt = $expiresAt;
        $this->ipHash = $ipHash;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getType(): AlbumVisitorTributeType
    {
        return $this->type;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isActive(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->expiresAt === null || $this->expiresAt > $now;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
