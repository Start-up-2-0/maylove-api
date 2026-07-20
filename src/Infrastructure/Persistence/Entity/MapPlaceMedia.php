<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Map\MapMediaType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'map_place_media')]
#[ORM\Index(name: 'idx_map_place_media_place', columns: ['place_id', 'sort_order'])]
class MapPlaceMedia
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: CoupleMap::class)]
    #[ORM\JoinColumn(name: 'couple_map_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CoupleMap $coupleMap;

    #[ORM\ManyToOne(targetEntity: MapPlace::class)]
    #[ORM\JoinColumn(name: 'place_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private MapPlace $place;

    #[ORM\Column(name: 'storage_file_id', length: 64)]
    private string $storageFileId;

    #[ORM\Column(name: 'media_type', length: 20, enumType: MapMediaType::class)]
    private MapMediaType $mediaType;

    #[ORM\Column(name: 'original_filename', length: 255)]
    private string $originalFilename;

    #[ORM\Column(name: 'mime_type', length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(name: 'size_bytes', type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $sizeBytes = null;

    #[ORM\Column(name: 'url_thumbnail', length: 500, nullable: true)]
    private ?string $urlThumbnail = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        CoupleMap $coupleMap,
        MapPlace $place,
        string $storageFileId,
        MapMediaType $mediaType,
        string $originalFilename,
        int $sortOrder,
    ) {
        $this->id = Uuid::v7();
        $this->coupleMap = $coupleMap;
        $this->place = $place;
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

    public function getCoupleMap(): CoupleMap
    {
        return $this->coupleMap;
    }

    public function getPlace(): MapPlace
    {
        return $this->place;
    }

    public function getStorageFileId(): string
    {
        return $this->storageFileId;
    }

    public function getMediaType(): MapMediaType
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

    public function getUrlThumbnail(): ?string
    {
        return $this->urlThumbnail;
    }

    public function setUrlThumbnail(?string $urlThumbnail): void
    {
        $this->urlThumbnail = $urlThumbnail;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
