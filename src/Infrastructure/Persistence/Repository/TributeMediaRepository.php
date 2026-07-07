<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TributeMedia>
 */
final class TributeMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TributeMedia::class);
    }

    public function findActiveByIdAndTribute(Uuid $id, Tribute $tribute): ?TributeMedia
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.tribute = :tribute')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('tribute', $tribute)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<TributeMedia>
     */
    public function findActiveByTribute(Tribute $tribute): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.tribute = :tribute')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('tribute', $tribute)
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByTributeAndType(Tribute $tribute, TributeMediaType $type): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.tribute = :tribute')
            ->andWhere('m.mediaType = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('tribute', $tribute)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNextSortOrder(Tribute $tribute): int
    {
        $max = $this->createQueryBuilder('m')
            ->select('MAX(m.sortOrder)')
            ->andWhere('m.tribute = :tribute')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('tribute', $tribute)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function save(TributeMedia $media): void
    {
        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
