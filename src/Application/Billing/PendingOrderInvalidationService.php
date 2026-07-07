<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\OrderStatus;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\Billing\OrderRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;

final class PendingOrderInvalidationService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly TributeRepository $tributeRepository,
    ) {
    }

    public function invalidateIfAwaitingPayment(Tribute $tribute): void
    {
        if ($tribute->getStatus() !== TributeStatus::AwaitingPayment) {
            return;
        }

        $order = $this->orderRepository->findByTribute($tribute);
        if ($order !== null && $order->getStatus() === OrderStatus::Pending) {
            $order->markCancelled();
            $this->orderRepository->save($order);
        }

        $tribute->markDraft();
        $this->tributeRepository->save($tribute);
    }
}
