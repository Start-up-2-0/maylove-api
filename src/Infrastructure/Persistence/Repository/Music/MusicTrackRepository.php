<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Music;

use App\Infrastructure\Persistence\Entity\Music\MusicTrack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<MusicTrack>
 */
final class MusicTrackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MusicTrack::class);
    }

    /**
     * @return list<MusicTrack>
     */
    public function findAllActive(?string $category = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.isActive = true')
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.title', 'ASC');

        if ($category !== null && $category !== '') {
            $qb->andWhere('m.category = :category')
                ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function findActiveById(Uuid $id): ?MusicTrack
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.isActive = true')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<string>
     */
    public function findActiveCategories(): array
    {
        /** @var list<array{category: string}> $rows */
        $rows = $this->createQueryBuilder('m')
            ->select('DISTINCT m.category AS category')
            ->andWhere('m.isActive = true')
            ->orderBy('m.category', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row) => $row['category'], $rows);
    }
}
