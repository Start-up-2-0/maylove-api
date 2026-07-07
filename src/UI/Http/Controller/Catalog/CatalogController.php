<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Catalog;

use App\Application\Catalog\CatalogQueryService;
use App\Infrastructure\Persistence\Entity\Catalog\Template;
use App\Infrastructure\Persistence\Entity\Catalog\TributeType;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'catalog_')]
final class CatalogController extends AbstractController
{
    public function __construct(
        private readonly CatalogQueryService $catalogQueryService,
    ) {
    }

    #[Route('/tribute-types', name: 'tribute_types_list', methods: ['GET'])]
    public function listTributeTypes(): JsonResponse
    {
        $types = $this->catalogQueryService->listTributeTypes();

        return ApiResponse::success('OK', array_map(
            fn (TributeType $type) => $this->serializeTributeType($type),
            $types,
        ));
    }

    #[Route('/tribute-types/{id}', name: 'tribute_types_show', methods: ['GET'])]
    public function showTributeType(string $id): JsonResponse
    {
        $type = $this->catalogQueryService->getTributeType($id);
        if ($type === null) {
            return ApiResponse::error('Tipo de homenagem não encontrado.', 'TRIBUTE_TYPE_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success('OK', $this->serializeTributeType($type));
    }

    #[Route('/templates', name: 'templates_list', methods: ['GET'])]
    public function listTemplates(Request $request): JsonResponse
    {
        $tributeTypeId = $request->query->get('tribute_type_id');
        $templates = $this->catalogQueryService->listTemplates(
            is_string($tributeTypeId) ? $tributeTypeId : null,
        );

        return ApiResponse::success('OK', array_map(
            fn (Template $template) => $this->serializeTemplate($template, includeConfig: false),
            $templates,
        ));
    }

    #[Route('/templates/{id}', name: 'templates_show', methods: ['GET'])]
    public function showTemplate(string $id): JsonResponse
    {
        $template = $this->catalogQueryService->getTemplate($id);
        if ($template === null) {
            return ApiResponse::error('Template não encontrado.', 'TEMPLATE_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return ApiResponse::success('OK', $this->serializeTemplate($template, includeConfig: true));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTributeType(TributeType $type): array
    {
        return [
            'id' => (string) $type->getId(),
            'slug' => $type->getSlug(),
            'name' => $type->getName(),
            'icon' => $type->getIcon(),
            'default_palette' => $type->getDefaultPalette(),
            'default_texts' => $type->getDefaultTexts(),
            'sort_order' => $type->getSortOrder(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTemplate(Template $template, bool $includeConfig): array
    {
        $data = [
            'id' => (string) $template->getId(),
            'slug' => $template->getSlug(),
            'name' => $template->getName(),
            'category' => [
                'id' => (string) $template->getCategory()->getId(),
                'slug' => $template->getCategory()->getSlug(),
                'name' => $template->getCategory()->getName(),
            ],
            'thumbnail_url' => $template->getThumbnailUrl(),
            'primary_color' => $template->getPrimaryColor(),
            'max_photos' => $template->getMaxPhotos(),
            'supports_video' => $template->supportsVideo(),
            'supports_music' => $template->supportsMusic(),
            'has_animation' => $template->hasAnimation(),
        ];

        if ($includeConfig) {
            $data['config_json'] = $template->getConfigJson();
        }

        return $data;
    }
}
