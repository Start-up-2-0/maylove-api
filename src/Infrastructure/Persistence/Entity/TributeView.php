<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tribute_views')]
#[ORM\Index(name: 'idx_tribute_views_tribute', columns: ['tribute_id', 'viewed_at'])]
class TributeView
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tribute::class)]
    #[ORM\JoinColumn(name: 'tribute_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Tribute $tribute;

    #[ORM\Column(name: 'session_id', length: 64)]
    private string $sessionId;

    #[ORM\Column(name: 'viewed_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $viewedAt;

    public function __construct(Tribute $tribute, string $sessionId)
    {
        $this->id = Uuid::v7();
        $this->tribute = $tribute;
        $this->sessionId = $sessionId;
        $this->viewedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTribute(): Tribute
    {
        return $this->tribute;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getViewedAt(): \DateTimeImmutable
    {
        return $this->viewedAt;
    }
}
