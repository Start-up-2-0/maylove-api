<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Application\Billing\SubscriptionCapabilityService;
use App\Domain\Map\CoupleMapStatus;
use App\Domain\Map\Exception\CoupleMapException;
use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class CoupleMapPublishService
{
    public function __construct(
        private readonly CoupleMapValidationService $validationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly CoupleMapRepository $mapRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    public function publish(CoupleMap $map, User $user): CoupleMap
    {
        if ($map->getStatus() === CoupleMapStatus::Published) {
            throw new CoupleMapException('Mapa já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$map->isEditable()) {
            throw new CoupleMapException('Mapa não pode ser publicado.', 'MAP_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($map);
        if (!$validation['valid']) {
            throw new CoupleMapException('Mapa incompleto para publicação.', 'VALIDATION_FAILED', 422);
        }

        $userId = (string) $user->getId();
        if ($this->billingEnabled && !$this->subscriptionCapability->canPublishFree($userId)) {
            throw new CoupleMapException(
                'Assinatura ativa necessária. Use o checkout para pagamento avulso.',
                'SUBSCRIPTION_REQUIRED',
                402,
            );
        }

        $billingMode = $this->billingEnabled
            ? ($this->subscriptionCapability->getBillingMode($userId) ?? BillingModeUsed::SubscriptionMonthly)
            : BillingModeUsed::PerTribute;

        $map->markPublished($billingMode);
        $this->mapRepository->save($map);

        return $map;
    }
}
