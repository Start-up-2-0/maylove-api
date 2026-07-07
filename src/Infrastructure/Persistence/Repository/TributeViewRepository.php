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
            'first_viewed_at' => $result['first_viewed_at'],
            'last_viewed_at' => $result['last_viewed_at'],
        ];
    }

    public function save(TributeView $view): void
    {
        $em = $this->getEntityManager();
        $em->persist($view);
        $em->flush();
    }
}
