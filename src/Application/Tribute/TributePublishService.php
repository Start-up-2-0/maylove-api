<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Billing\SubscriptionCapabilityService;
use App\Domain\Tribute\BillingModeUsed;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\TributeRepository;

final class TributePublishService
{
    public function __construct(
        private readonly TributeValidationService $validationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly TributeRepository $tributeRepository,
        private readonly TributePublishedDispatcher $publishedDispatcher,
    ) {
    }

    public function publish(Tribute $tribute, User $user): Tribute
    {
        if ($tribute->getStatus() === TributeStatus::Published) {
            throw new TributeException('Homenagem já publicada.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$tribute->isEditable()) {
            throw new TributeException('Homenagem não pode ser publicada.', 'TRIBUTE_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($tribute);
        if (!$validation['valid']) {
            throw new TributeException('Homenagem incompleta para publicação.', 'VALIDATION_FAILED', 422);
        }

        $userId = (string) $user->getId();
        if (!$this->subscriptionCapability->canPublishFree($userId)) {
            throw new TributeException(
                'Assinatura ativa necessária. Use o checkout para pagamento avulso.',
                'SUBSCRIPTION_REQUIRED',
                402,
            );
        }

        $billingMode = $this->subscriptionCapability->getBillingMode($userId)
            ?? BillingModeUsed::SubscriptionMonthly;

        $tribute->markPublished($billingMode);
        $this->tributeRepository->save($tribute);
        $this->publishedDispatcher->dispatch($tribute, $user);

        return $tribute;
    }
}
