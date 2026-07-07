<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Catalog;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'templates')]
#[ORM\UniqueConstraint(name: 'uniq_templates_slug', columns: ['slug'])]
class Template
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: TemplateCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false)]
    private TemplateCategory $category;

    #[ORM\Column(length: 80, unique: true)]
    private string $slug;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(name: 'thumbnail_url', length: 500, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(name: 'primary_color', length: 7)]
    private string $primaryColor;

    #[ORM\Column(name: 'max_photos', type: Types::SMALLINT)]
    private int $maxPhotos = 50;

    #[ORM\Column(name: 'supports_video', options: ['default' => true])]
    private bool $supportsVideo = true;

    #[ORM\Column(name: 'supports_music', options: ['default' => true])]
    private bool $supportsMusic = true;

    #[ORM\Column(name: 'has_animation', options: ['default' => false])]
    private bool $hasAnimation = false;

    #[ORM\Column(name: 'config_json', type: Types::JSON)]
    private array $configJson = [];

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        TemplateCategory $category,
        string $slug,
        string $name,
        string $primaryColor,
        int $maxPhotos,
        bool $supportsVideo,
        bool $supportsMusic,
        bool $hasAnimation,
        array $configJson,
        ?string $thumbnailUrl = null,
    ) {
        $this->id = Uuid::v7();
        $this->category = $category;
        $this->slug = $slug;
        $this->name = $name;
        $this->primaryColor = $primaryColor;
        $this->maxPhotos = $maxPhotos;
        $this->supportsVideo = $supportsVideo;
        $this->supportsMusic = $supportsMusic;
        $this->hasAnimation = $hasAnimation;
        $this->configJson = $configJson;
        $this->thumbnailUrl = $thumbnailUrl;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): TemplateCategory
    {
        return $this->category;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function getPrimaryColor(): string
    {
        return $this->primaryColor;
    }

    public function getMaxPhotos(): int
    {
        return $this->maxPhotos;
    }

    public function supportsVideo(): bool
    {
        return $this->supportsVideo;
    }

    public function supportsMusic(): bool
    {
        return $this->supportsMusic;
    }

    public function hasAnimation(): bool
    {
        return $this->hasAnimation;
    }

    public function getConfigJson(): array
    {
        return $this->configJson;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
