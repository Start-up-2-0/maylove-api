<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Application\Billing\OrderCheckoutService;
use App\Domain\Map\CoupleMapStatus;
use App\Domain\Map\Exception\CoupleMapException;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class CoupleMapCheckoutService
{
    public function __construct(
        private readonly CoupleMapValidationService $validationService,
        private readonly OrderCheckoutService $orderCheckoutService,
        private readonly CoupleMapRepository $mapRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    /** @return array<string, mixed> */
    public function checkout(CoupleMap $map, User $user): array
    {
        if (!$this->billingEnabled) {
            throw new CoupleMapException('Pagamentos desativados neste ambiente.', 'BILLING_DISABLED', 503);
        }

        if ($map->getStatus() === CoupleMapStatus::Published) {
            throw new CoupleMapException('Mapa já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$map->isEditable()) {
            throw new CoupleMapException('Mapa não pode iniciar checkout.', 'MAP_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($map);
        if (!$validation['valid']) {
            throw new CoupleMapException('Mapa incompleto para checkout.', 'VALIDATION_FAILED', 422);
        }

        $map->markAwaitingPayment();
        $this->mapRepository->save($map);

        return $this->orderCheckoutService->checkoutCoupleMap($map, $user);
    }
}
