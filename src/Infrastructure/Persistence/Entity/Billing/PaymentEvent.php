<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payment_events')]
#[ORM\UniqueConstraint(name: 'uniq_payment_events_external', columns: ['external_event_id'])]
class PaymentEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: 'external_event_id', length: 150, unique: true)]
    private string $externalEventId;

    #[ORM\Column(name: 'event_type', length: 120)]
    private string $eventType;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload;

    #[ORM\Column(name: 'processed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $externalEventId, string $eventType, array $payload)
    {
        $this->id = Uuid::v7();
        $this->externalEventId = $externalEventId;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getExternalEventId(): string
    {
        return $this->externalEventId;
    }

    public function markProcessed(): void
    {
        $this->processedAt = new \DateTimeImmutable();
    }

    public function isProcessed(): bool
    {
        return $this->processedAt !== null;
    }
}
