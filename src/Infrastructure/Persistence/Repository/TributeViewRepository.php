<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeView;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TributeView>
 */
final class TributeViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TributeView::class);
    }

    /**
     * @return array{first_viewed_at: ?\DateTimeImmutable, last_viewed_at: ?\DateTimeImmutable}
     */
    public function getViewedAtBoundsForTribute(Tribute $tribute): array
    {
        /** @var array{first_viewed_at: ?\DateTimeImmutable, last_viewed_at: ?\DateTimeImmutable}|null $result */
        $result = $this->createQueryBuilder('v')
            ->select('MIN(v.viewedAt) AS first_viewed_at, MAX(v.viewedAt) AS last_viewed_at')
            ->andWhere('v.tribute = :tribute')
            ->setParameter('tribute', $tribute)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return ['first_viewed_at' => null, 'last_viewed_at' => null];
        }

        return [
            'first_viewed_at' => $this->normalizeDateTime($result['first_viewed_at'] ?? null),
            'last_viewed_at' => $this->normalizeDateTime($result['last_viewed_at'] ?? null),
        ];
    }

    public function countUniqueSessionsForTribute(Tribute $tribute): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(DISTINCT v.sessionId)')
            ->andWhere('v.tribute = :tribute')
            ->setParameter('tribute', $tribute)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{date: string, views: int}>
     */
    public function getDailyViewsForTribute(Tribute $tribute, int $days = 90): array
    {
        $days = max(1, min(90, $days));
        $timezone = new \DateTimeZone('America/Sao_Paulo');
        $today = new \DateTimeImmutable('today', $timezone);
        $start = $today->modify(sprintf('-%d days', $days - 1));
        $startUtc = $start->setTimezone(new \DateTimeZone('UTC'));

        /** @var list<array{viewedAt: \DateTimeInterface|string}> $rows */
        $rows = $this->createQueryBuilder('v')
            ->select('v.viewedAt')
            ->andWhere('v.tribute = :tribute')
            ->andWhere('v.viewedAt >= :start')
            ->setParameter('tribute', $tribute)
            ->setParameter('start', $startUtc)
            ->orderBy('v.viewedAt', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $viewedAt = $this->normalizeDateTime($row['viewedAt'] ?? null);
            if ($viewedAt === null) {
                continue;
            }
            $date = $viewedAt->setTimezone($timezone)->format('Y-m-d');
            $counts[$date] = ($counts[$date] ?? 0) + 1;
        }

        $series = [];
        for ($offset = 0; $offset < $days; ++$offset) {
            $date = $start->modify(sprintf('+%d days', $offset))->format('Y-m-d');
            $series[] = ['date' => $date, 'views' => $counts[$date] ?? 0];
        }

        return $series;
    }

    private function normalizeDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) && $value !== '') {
            return new \DateTimeImmutable($value);
        }

        return null;
    }

    public function save(TributeView $view): void
    {
        $em = $this->getEntityManager();
        $em->persist($view);
        $em->flush();
    }
}
