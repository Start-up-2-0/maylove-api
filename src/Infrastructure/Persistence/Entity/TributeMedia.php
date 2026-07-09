<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\TributeMediaType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tribute_media')]
#[ORM\Index(name: 'idx_tribute_media_tribute', columns: ['tribute_id', 'sort_order'])]
class TributeMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tribute::class)]
    #[ORM\JoinColumn(name: 'tribute_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tribute $tribute;

    #[ORM\Column(name: 'storage_file_id', length: 64)]
    private string $storageFileId;

    #[ORM\Column(name: 'media_type', length: 20, enumType: TributeMediaType::class)]
    private TributeMediaType $mediaType;

    #[ORM\Column(name: 'original_filename', length: 255)]
    private string $originalFilename;

    #[ORM\Column(name: 'mime_type', length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $sizeBytes = null;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT)]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'url_thumbnail', length: 500, nullable: true)]
    private ?string $urlThumbnail = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'processing_status', length: 20, enumType: MediaProcessingStatus::class, options: ['default' => 'ready'])]
    private MediaProcessingStatus $processingStatus = MediaProcessingStatus::Ready;

    #[ORM\Column(name: 'processing_error', length: 500, nullable: true)]
    private ?string $processingError = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tribute $tribute,
        string $storageFileId,
        TributeMediaType $mediaType,
        string $originalFilename,
        int $sortOrder,
    ) {
        $this->id = Uuid::v7();
        $this->tribute = $tribute;
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

    public function getTribute(): Tribute
    {
        return $this->tribute;
    }

    public function getStorageFileId(): string
    {
        return $this->storageFileId;
    }

    public function getMediaType(): TributeMediaType
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

    public function getProcessingStatus(): MediaProcessingStatus
    {
        return $this->processingStatus;
    }

    public function setProcessingStatus(MediaProcessingStatus $processingStatus): void
    {
        $this->processingStatus = $processingStatus;
    }

    public function getProcessingError(): ?string
    {
        return $this->processingError;
    }

    public function setProcessingError(?string $processingError): void
    {
        $this->processingError = $processingError;
    }
}
