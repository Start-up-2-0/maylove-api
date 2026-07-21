<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Application\Billing\OrderCheckoutService;
use App\Domain\Bouquet\DigitalBouquetStatus;
use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;

final class DigitalBouquetCheckoutService
{
    public function __construct(
        private readonly DigitalBouquetValidationService $validationService,
        private readonly OrderCheckoutService $orderCheckoutService,
        private readonly DigitalBouquetRepository $bouquetRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    /** @return array<string, mixed> */
    public function checkout(DigitalBouquet $bouquet, User $user): array
    {
        if (!$this->billingEnabled) {
            throw new DigitalBouquetException('Pagamentos desativados neste ambiente.', 'BILLING_DISABLED', 503);
        }

        if ($bouquet->getStatus() === DigitalBouquetStatus::Published) {
            throw new DigitalBouquetException('Buquê já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$bouquet->isEditable()) {
            throw new DigitalBouquetException('Buquê não pode iniciar checkout.', 'BOUQUET_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($bouquet);
        if (!$validation['valid']) {
            throw new DigitalBouquetException('Buquê incompleto para checkout.', 'VALIDATION_FAILED', 422);
        }

        $bouquet->markAwaitingPayment();
        $this->bouquetRepository->save($bouquet);

        return $this->orderCheckoutService->checkoutDigitalBouquet($bouquet, $user);
    }
}
