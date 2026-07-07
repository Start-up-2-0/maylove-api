<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use App\Domain\Billing\PaymentStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
#[ORM\UniqueConstraint(name: 'uniq_payments_mp', columns: ['mp_payment_id'])]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: UserSubscription::class)]
    #[ORM\JoinColumn(name: 'user_subscription_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UserSubscription $userSubscription = null;

    #[ORM\Column(name: 'mp_payment_id', length: 120, unique: true)]
    private string $mpPaymentId;

    #[ORM\Column(length: 30, enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'raw_response', type: Types::JSON, nullable: true)]
    private ?array $rawResponse = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $mpPaymentId,
        PaymentStatus $status,
        int $amountCents,
        ?Order $order = null,
        ?UserSubscription $userSubscription = null,
        ?array $rawResponse = null,
    ) {
        $this->id = Uuid::v7();
        $this->mpPaymentId = $mpPaymentId;
        $this->status = $status;
        $this->amountCents = $amountCents;
        $this->order = $order;
        $this->userSubscription = $userSubscription;
        $this->rawResponse = $rawResponse;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getUserSubscription(): ?UserSubscription
    {
        return $this->userSubscription;
    }

    public function getMpPaymentId(): string
    {
        return $this->mpPaymentId;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }
}
