<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Album\AlbumVisitorTributeType;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumVisitorTribute;
use App\Infrastructure\Persistence\Repository\AlbumVisitorTributeRepository;

final class AlbumVisitorTributeService
{
    public function __construct(
        private readonly AlbumVisitorTributeRepository $repository,
        private readonly AlbumTributeRateLimitService $rateLimitService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(Album $album): array
    {
        return array_map(
            fn (AlbumVisitorTribute $tribute) => $this->serialize($tribute),
            $this->repository->findActiveByAlbum($album),
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(Album $album, array $payload, ?string $clientIp): array
    {
        if (!$album->isPublic()) {
            throw new AlbumException('Este memorial não aceita homenagens públicas.', 'ALBUM_NOT_PUBLIC', 403);
        }

        $ipHash = $this->hashIp($clientIp);
        if ($ipHash !== null && !$this->rateLimitService->canSubmit($album->getSlug(), $ipHash)) {
            throw new AlbumException(
                'Muitas homenagens enviadas. Tente novamente mais tarde.',
                'TRIBUTE_RATE_LIMIT',
                429,
            );
        }

        $type = AlbumVisitorTributeType::tryFrom((string) ($payload['type'] ?? ''));
        if ($type === null) {
            throw new AlbumException('Tipo de homenagem inválido.', 'INVALID_TRIBUTE_TYPE', 422);
        }

        $author = trim((string) ($payload['author_name'] ?? $payload['author'] ?? ''));
        if ($author === '' || mb_strlen($author) < 2) {
            throw new AlbumException('Informe seu nome (mínimo 2 caracteres).', 'AUTHOR_REQUIRED', 422);
        }
        if (mb_strlen($author) > 80) {
            throw new AlbumException('Nome muito longo (máximo 80 caracteres).', 'AUTHOR_TOO_LONG', 422);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            throw new AlbumException('Escreva uma mensagem de carinho.', 'MESSAGE_REQUIRED', 422);
        }
        if (mb_strlen($message) > 500) {
            throw new AlbumException('Mensagem muito longa (máximo 500 caracteres).', 'MESSAGE_TOO_LONG', 422);
        }

        $expiresAt = null;
        $ttlDays = $type->ttlDays();
        if ($ttlDays !== null) {
            $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d days', $ttlDays));
        }

        $tribute = new AlbumVisitorTribute(
            $album,
            $type,
            $author,
            $message,
            $expiresAt,
            $ipHash,
        );
        $this->repository->save($tribute);

        return $this->serialize($tribute);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(AlbumVisitorTribute $tribute): array
    {
        return [
            'id' => (string) $tribute->getId(),
            'type' => $tribute->getType()->value,
            'author_name' => $tribute->getAuthorName(),
            'message' => $tribute->getMessage(),
            'expires_at' => $tribute->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $tribute->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function hashIp(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        return hash('sha256', trim($ip));
    }
}
