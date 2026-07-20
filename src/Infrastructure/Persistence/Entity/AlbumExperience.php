<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use App\Domain\Album\ExperienceType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'album_experiences')]
#[ORM\Index(name: 'idx_album_experiences_album', columns: ['album_id', 'sort_order'])]
class AlbumExperience
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Album $album;

    #[ORM\ManyToOne(targetEntity: AlbumMemory::class)]
    #[ORM\JoinColumn(name: 'memory_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?AlbumMemory $memory = null;

    #[ORM\Column(length: 32, enumType: ExperienceType::class)]
    private ExperienceType $type;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'config_json', type: Types::JSON)]
    private array $configJson;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Album $album, ExperienceType $type, array $configJson, ?AlbumMemory $memory = null)
    {
        $this->id = Uuid::v7();
        $this->album = $album;
        $this->type = $type;
        $this->configJson = $configJson;
        $this->memory = $memory;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getMemory(): ?AlbumMemory
    {
        return $this->memory;
    }

    public function setMemory(?AlbumMemory $memory): void
    {
        $this->memory = $memory;
        $this->touch();
    }

    public function getType(): ExperienceType
    {
        return $this->type;
    }

    public function setType(ExperienceType $type): void
    {
        $this->type = $type;
        $this->touch();
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigJson(): array
    {
        return $this->configJson;
    }

    /**
     * @param array<string, mixed> $configJson
     */
    public function setConfigJson(array $configJson): void
    {
        $this->configJson = $configJson;
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
