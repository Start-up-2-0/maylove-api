<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Catalog;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'template_categories')]
#[ORM\UniqueConstraint(name: 'uniq_template_categories_slug', columns: ['slug'])]
class TemplateCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 80)]
    private string $name;

    #[ORM\Column(length: 80, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'sort_order', type: Types::SMALLINT)]
    private int $sortOrder = 0;

    public function __construct(string $name, string $slug, int $sortOrder)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->slug = $slug;
        $this->sortOrder = $sortOrder;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
