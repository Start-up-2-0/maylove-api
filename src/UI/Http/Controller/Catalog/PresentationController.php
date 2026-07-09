<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Catalog;

use App\Application\Wizard\PresentationSchemaProvider;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'presentation_')]
final class PresentationController extends AbstractController
{
    public function __construct(
        private readonly PresentationSchemaProvider $schemaProvider,
    ) {
    }

    /**
     * Catálogo de estilos de apresentação com o schema que dirige o wizard.
     * Público, como o restante do catálogo.
     */
    #[Route('/presentations', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return ApiResponse::success('OK', $this->schemaProvider->list());
    }
}
