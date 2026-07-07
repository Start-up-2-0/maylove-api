<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Health\HealthCheckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health', name: 'health_', methods: ['GET'])]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService,
    ) {
    }

    #[Route('', name: 'check', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $result = $this->healthCheckService->run();
        $statusCode = $result['status'] === 'ok' ? 200 : 503;

        return $this->json($result, $statusCode);
    }
}
