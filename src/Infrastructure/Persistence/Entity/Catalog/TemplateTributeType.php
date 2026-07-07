<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Catalog;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'template_tribute_types')]
#[ORM\UniqueConstraint(name: 'uniq_template_tribute_type', columns: ['template_id', 'tribute_type_id'])]
class TemplateTributeType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Template $template;

    #[ORM\ManyToOne(targetEntity: TributeType::class)]
    #[ORM\JoinColumn(name: 'tribute_type_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TributeType $tributeType;

    public function __construct(Template $template, TributeType $tributeType)
    {
        $this->id = Uuid::v7();
        $this->template = $template;
        $this->tributeType = $tributeType;
    }
}
