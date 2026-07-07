<?php

declare(strict_types=1);

namespace App\Application\Catalog;

use App\Infrastructure\Persistence\Entity\Catalog\Template;
use App\Infrastructure\Persistence\Entity\Catalog\TributeType;
use App\Infrastructure\Persistence\Repository\Catalog\TemplateRepository;
use App\Infrastructure\Persistence\Repository\Catalog\TributeTypeRepository;
use Symfony\Component\Uid\Uuid;

final class CatalogQueryService
{
    public function __construct(
        private readonly TributeTypeRepository $tributeTypeRepository,
        private readonly TemplateRepository $templateRepository,
    ) {
    }

    /**
     * @return list<TributeType>
     */
    public function listTributeTypes(): array
    {
        return $this->tributeTypeRepository->findAllActiveOrdered();
    }

    public function getTributeType(string $id): ?TributeType
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->tributeTypeRepository->findActiveById(Uuid::fromString($id));
    }

    /**
     * @return list<Template>
     */
    public function listTemplates(?string $tributeTypeId = null): array
    {
        $typeUuid = null;
        if ($tributeTypeId !== null && $tributeTypeId !== '') {
            if (!Uuid::isValid($tributeTypeId)) {
                return [];
            }

            $typeUuid = Uuid::fromString($tributeTypeId);
            if ($this->tributeTypeRepository->findActiveById($typeUuid) === null) {
                return [];
            }
        }

        return $this->templateRepository->findAllActiveOrdered($typeUuid);
    }

    public function getTemplate(string $id): ?Template
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->templateRepository->findActiveById(Uuid::fromString($id));
    }
}
