<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Album\Sentiment;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_memories')]
#[ORM\Index(name: 'idx_album_memories_chapter', columns: ['chapter_id', 'sort_order'])]
#[ORM\Index(name: 'idx_album_memories_album', columns: ['album_id'])]
#[ORM\Index(name: 'idx_album_memories_date', columns: ['memory_date'])]
class AlbumMemory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: AlbumChapter::class)]
    #[ORM\JoinColumn(name: 'chapter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AlbumChapter $chapter;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'memory_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $memoryDate = null;

    #[ORM\Column(name: 'memory_time', type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $memoryTime = null;

    #[ORM\Column(name: 'location_lat', type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $locationLat = null;

    #[ORM\Column(name: 'location_lng', type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $locationLng = null;

    #[ORM\Column(name: 'location_label', length: 255, nullable: true)]
    private ?string $locationLabel = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $climate = null;

    #[ORM\Column(length: 32, enumType: Sentiment::class, nullable: true)]
    private ?Sentiment $sentiment = null;

    /** @var list<string>|null */
    #[ORM\Column(name: 'tags_json', type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    /** @var list<string>|null */
    #[ORM\Column(name: 'people_json', type: Types::JSON, nullable: true)]
    private ?array $people = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'content_json', type: Types::JSON, nullable: true)]
    private ?array $contentJson = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(AlbumChapter $chapter, Album $album, int $sortOrder = 0)
    {
        $this->id = Uuid::v7();
        $this->chapter = $chapter;
        $this->album = $album;
        $this->sortOrder = $sortOrder;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getChapter(): AlbumChapter
    {
        return $this->chapter;
    }

    public function getAlbum(): Album
    {
        return $this->album;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title !== null ? trim($title) : null;
        $this->touch();
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle !== null ? trim($subtitle) : null;
        $this->touch();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description !== null ? trim($description) : null;
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

    public function getLocationLat(): ?string
    {
        return $this->locationLat;
    }

    public function setLocationLat(?string $locationLat): void
    {
        $this->locationLat = $locationLat;
        $this->touch();
    }

    public function getLocationLng(): ?string
    {
        return $this->locationLng;
    }

    public function setLocationLng(?string $locationLng): void
    {
        $this->locationLng = $locationLng;
        $this->touch();
    }

    public function getLocationLabel(): ?string
    {
        return $this->locationLabel;
    }

    public function setLocationLabel(?string $locationLabel): void
    {
        $this->locationLabel = $locationLabel !== null ? trim($locationLabel) : null;
        $this->touch();
    }

    public function getClimate(): ?string
    {
        return $this->climate;
    }

    public function setClimate(?string $climate): void
    {
        $this->climate = $climate;
        $this->touch();
    }

    public function getSentiment(): ?Sentiment
    {
        return $this->sentiment;
    }

    public function setSentiment(?Sentiment $sentiment): void
    {
        $this->sentiment = $sentiment;
        $this->touch();
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    /**
     * @param list<string> $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = array_values(array_filter(array_map('trim', $tags), static fn ($t) => $t !== ''));
        $this->touch();
    }

    /**
     * @return list<string>
     */
    public function getPeople(): array
    {
        return $this->people ?? [];
    }

    /**
     * @param list<string> $people
     */
    public function setPeople(array $people): void
    {
        $this->people = array_values(array_filter(array_map('trim', $people), static fn ($p) => $p !== ''));
        $this->touch();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContentJson(): ?array
    {
        return $this->contentJson;
    }

    /**
     * @param array<string, mixed>|null $contentJson
     */
    public function setContentJson(?array $contentJson): void
    {
        $this->contentJson = $contentJson;
        $this->touch();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
