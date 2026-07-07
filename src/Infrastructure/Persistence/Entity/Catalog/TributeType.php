<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Catalog;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tribute_types')]
#[ORM\UniqueConstraint(name: 'uniq_tribute_types_slug', columns: ['slug'])]
class TributeType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 80, unique: true)]
    private string $slug;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 16)]
    private string $icon;

    #[ORM\Column(name: 'default_palette', type: Types::JSON)]
    private array $defaultPalette = [];

    #[ORM\Column(name: 'default_texts', type: Types::JSON)]
    private array $defaultTexts = [];

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT)]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    public function __construct(
        string $slug,
        string $name,
        string $icon,
        array $defaultPalette,
        array $defaultTexts,
        int $sortOrder,
    ) {
        $this->id = Uuid::v7();
        $this->slug = $slug;
        $this->name = $name;
        $this->icon = $icon;
        $this->defaultPalette = $defaultPalette;
        $this->defaultTexts = $defaultTexts;
        $this->sortOrder = $sortOrder;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getDefaultPalette(): array
    {
        return $this->defaultPalette;
    }

    public function getDefaultTexts(): array
    {
        return $this->defaultTexts;
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
