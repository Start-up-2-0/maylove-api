<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Application\Billing\OrderCheckoutService;
use App\Domain\Album\AlbumStatus;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\AlbumRepository;

final class AlbumCheckoutService
{
    public function __construct(
        private readonly AlbumValidationService $validationService,
        private readonly OrderCheckoutService $orderCheckoutService,
        private readonly AlbumRepository $albumRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkout(Album $album, User $user): array
    {
        if (!$this->billingEnabled) {
            throw new AlbumException(
                'Pagamentos desativados neste ambiente.',
                'BILLING_DISABLED',
                503,
            );
        }

        if ($album->getStatus() === AlbumStatus::Published) {
            throw new AlbumException('Álbum já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$album->isEditable()) {
            throw new AlbumException('Álbum não pode iniciar checkout.', 'ALBUM_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($album);
        if (!$validation['valid']) {
            throw new AlbumException('Álbum incompleto para checkout.', 'VALIDATION_FAILED', 422);
        }

        $album->markAwaitingPayment();
        $this->albumRepository->save($album);

        return $this->orderCheckoutService->checkoutAlbum($album, $user);
    }
}
