<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Album\AlbumMediaType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_media')]
#[ORM\Index(name: 'idx_album_media_album', columns: ['album_id', 'sort_order'])]
class AlbumMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\Column(name: 'storage_file_id', length: 64)]
    private string $storageFileId;

    #[ORM\Column(name: 'media_type', length: 20, enumType: AlbumMediaType::class)]
    private AlbumMediaType $mediaType;

    #[ORM\Column(name: 'original_filename', length: 255)]
    private string $originalFilename;

    #[ORM\Column(name: 'mime_type', length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $sizeBytes = null;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT)]
    private int $sortOrder = 0;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(name: 'memory_date', length: 50, nullable: true)]
    private ?string $memoryDate = null;

    #[ORM\Column(name: 'url_thumbnail', length: 500, nullable: true)]
    private ?string $urlThumbnail = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Album $album,
        string $storageFileId,
        AlbumMediaType $mediaType,
        string $originalFilename,
        int $sortOrder,
    ) {
        $this->id = Uuid::v7();
        $this->album = $album;
        $this->storageFileId = $storageFileId;
        $this->mediaType = $mediaType;
        $this->originalFilename = $originalFilename;
        $this->sortOrder = $sortOrder;
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

    public function getStorageFileId(): string
    {
        return $this->storageFileId;
    }

    public function getMediaType(): AlbumMediaType
    {
        return $this->mediaType;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getSizeBytes(): ?int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(?int $sizeBytes): void
    {
        $this->sizeBytes = $sizeBytes;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title !== null ? trim($title) : null;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): void
    {
        $this->caption = $caption;
    }

    public function getMemoryDate(): ?string
    {
        return $this->memoryDate;
    }

    public function setMemoryDate(?string $memoryDate): void
    {
        $this->memoryDate = $memoryDate !== null ? trim($memoryDate) : null;
    }

    public function getUrlThumbnail(): ?string
    {
        return $this->urlThumbnail;
    }

    public function setUrlThumbnail(?string $urlThumbnail): void
    {
        $this->urlThumbnail = $urlThumbnail;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
