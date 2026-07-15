<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Billing\OrderCheckoutService;
use App\Application\Billing\SubscriptionCapabilityService;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\TributeRepository;

final class TributeCheckoutService
{
    public function __construct(
        private readonly TributeValidationService $validationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly OrderCheckoutService $orderCheckoutService,
        private readonly TributeRepository $tributeRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkout(Tribute $tribute, User $user): array
    {
        if (!$this->billingEnabled) {
            throw new TributeException(
                'Pagamentos desativados neste ambiente.',
                'BILLING_DISABLED',
                503,
            );
        }
        if ($tribute->getStatus() === TributeStatus::Published) {
            throw new TributeException('Homenagem já publicada.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$tribute->isEditable()) {
            throw new TributeException('Homenagem não pode iniciar checkout.', 'TRIBUTE_NOT_EDITABLE', 403);
        }

        if ($this->subscriptionCapability->hasActiveSubscription((string) $user->getId())) {
            throw new TributeException(
                'Assinantes devem usar publicação direta.',
                'SUBSCRIPTION_ACTIVE',
                409,
            );
        }

        $validation = $this->validationService->validate($tribute);
        if (!$validation['valid']) {
            throw new TributeException('Homenagem incompleta para checkout.', 'VALIDATION_FAILED', 422);
        }

        $tribute->markAwaitingPayment();
        $this->tributeRepository->save($tribute);

        return $this->orderCheckoutService->checkoutTribute($tribute, $user);
    }
}
