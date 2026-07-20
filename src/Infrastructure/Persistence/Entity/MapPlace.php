<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Map\MapPlaceType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'map_places')]
#[ORM\Index(name: 'idx_map_places_map', columns: ['couple_map_id', 'sort_order'])]
class MapPlace
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: CoupleMap::class)]
    #[ORM\JoinColumn(name: 'couple_map_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CoupleMap $coupleMap;

    #[ORM\Column(name: 'place_type', length: 32, enumType: MapPlaceType::class)]
    private MapPlaceType $placeType;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'memory_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $memoryDate = null;

    #[ORM\Column(name: 'memory_time', type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $memoryTime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8)]
    private string $longitude;

    #[ORM\Column(name: 'address_label', length: 255, nullable: true)]
    private ?string $addressLabel = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $sentiment = null;

    #[ORM\Column(name: 'is_highlight', type: Types::BOOLEAN)]
    private bool $isHighlight = false;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $sortOrder = 0;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'content_json', type: Types::JSON)]
    private array $contentJson = [];

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        CoupleMap $coupleMap,
        MapPlaceType $placeType,
        string $title,
        float $latitude,
        float $longitude,
        int $sortOrder,
    ) {
        $this->id = Uuid::v7();
        $this->coupleMap = $coupleMap;
        $this->placeType = $placeType;
        $this->title = $title;
        $this->latitude = (string) $latitude;
        $this->longitude = (string) $longitude;
        $this->sortOrder = $sortOrder;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCoupleMap(): CoupleMap
    {
        return $this->coupleMap;
    }

    public function getPlaceType(): MapPlaceType
    {
        return $this->placeType;
    }

    public function setPlaceType(MapPlaceType $placeType): void
    {
        $this->placeType = $placeType;
        $this->touch();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle;
        $this->touch();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function getMemoryDate(): ?\DateTimeImmutable
    {
        return $this->memoryDate;
    }

    public function setMemoryDate(?\DateTimeImmutable $memoryDate): void
    {
        $this->memoryDate = $memoryDate;
        $this->touch();
    }

    public function getMemoryTime(): ?\DateTimeImmutable
    {
        return $this->memoryTime;
    }

    public function setMemoryTime(?\DateTimeImmutable $memoryTime): void
    {
        $this->memoryTime = $memoryTime;
        $this->touch();
    }

    public function getLatitude(): float
    {
        return (float) $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = (string) $latitude;
        $this->touch();
    }

    public function getLongitude(): float
    {
        return (float) $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = (string) $longitude;
        $this->touch();
    }

    public function getAddressLabel(): ?string
    {
        return $this->addressLabel;
    }

    public function setAddressLabel(?string $addressLabel): void
    {
        $this->addressLabel = $addressLabel;
        $this->touch();
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
        $this->touch();
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
        $this->touch();
    }

    public function getSentiment(): ?string
    {
        return $this->sentiment;
    }

    public function setSentiment(?string $sentiment): void
    {
        $this->sentiment = $sentiment;
        $this->touch();
    }

    public function isHighlight(): bool
    {
        return $this->isHighlight;
    }

    public function setIsHighlight(bool $isHighlight): void
    {
        $this->isHighlight = $isHighlight;
        $this->touch();
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
        $this->touch();
    }

    /** @return array<string, mixed> */
    public function getContentJson(): array
    {
        return $this->contentJson;
    }

    /** @param array<string, mixed> $contentJson */
    public function setContentJson(array $contentJson): void
    {
        $this->contentJson = $contentJson;
        $this->touch();
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
