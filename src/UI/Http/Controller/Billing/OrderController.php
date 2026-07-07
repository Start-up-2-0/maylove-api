<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Billing;

use App\Application\Billing\OrderCheckoutService;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/orders', name: 'orders_')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderCheckoutService $orderCheckoutService,
    ) {
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success('OK', $this->orderCheckoutService->getOwnedOrder($id, $user->getUser()));
    }
}
