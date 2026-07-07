<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Music;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'music_tracks')]
#[ORM\UniqueConstraint(name: 'uniq_music_tracks_slug', columns: ['slug'])]
class MusicTrack
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 80, unique: true)]
    private string $slug;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(length: 120)]
    private string $artist;

    #[ORM\Column(name: 'duration_seconds', type: Types::SMALLINT)]
    private int $durationSeconds;

    #[ORM\Column(name: 'relative_path', length: 255)]
    private string $relativePath;

    #[ORM\Column(name: 'preview_url', length: 500, nullable: true)]
    private ?string $previewUrl = null;

    #[ORM\Column(length: 50)]
    private string $category;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT)]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $slug,
        string $title,
        string $artist,
        int $durationSeconds,
        string $relativePath,
        string $category,
        int $sortOrder,
        ?string $previewUrl = null,
    ) {
        $this->id = Uuid::v7();
        $this->slug = $slug;
        $this->title = $title;
        $this->artist = $artist;
        $this->durationSeconds = $durationSeconds;
        $this->relativePath = $relativePath;
        $this->category = $category;
        $this->sortOrder = $sortOrder;
        $this->previewUrl = $previewUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getArtist(): string
    {
        return $this->artist;
    }

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getPreviewUrl(): ?string
    {
        return $this->previewUrl;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
