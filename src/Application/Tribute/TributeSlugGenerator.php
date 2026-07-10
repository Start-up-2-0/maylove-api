<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Infrastructure\Persistence\Repository\TributeRepository;
use Symfony\Component\Uid\Uuid;

final class TributeSlugGenerator
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
    ) {
    }

    public function generateUnique(?string $base = null): string
    {
        do {
            $candidate = $this->truncate('homenagem-'.Uuid::v4()->toRfc4122());
        } while ($this->tributeRepository->existsBySlug($candidate));

        return $candidate;
    }

    public function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    public function isValidFormat(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    private function truncate(string $slug): string
    {
        return substr($slug, 0, 100);
    }
}
