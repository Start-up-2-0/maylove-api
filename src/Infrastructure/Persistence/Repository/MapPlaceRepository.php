<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\MapPlace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<MapPlace> */
final class MapPlaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapPlace::class);
    }

    /** @return list<MapPlace> */
    public function findActiveByMap(CoupleMap $map): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.coupleMap = :map')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('map', $map)
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByMap(CoupleMap $map): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.coupleMap = :map')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('map', $map)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveByIdAndMap(Uuid $id, CoupleMap $map): ?MapPlace
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->andWhere('p.coupleMap = :map')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('map', $map)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextSortOrder(CoupleMap $map): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.sortOrder)')
            ->andWhere('p.coupleMap = :map')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('map', $map)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function clearHighlight(CoupleMap $map, ?Uuid $exceptId = null): void
    {
        $qb = $this->createQueryBuilder('p')
            ->update()
            ->set('p.isHighlight', ':false')
            ->where('p.coupleMap = :map')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('false', false)
            ->setParameter('map', $map);

        if ($exceptId !== null) {
            $qb->andWhere('p.id != :exceptId')->setParameter('exceptId', $exceptId, 'uuid');
        }

        $qb->getQuery()->execute();
    }

    public function save(MapPlace $place): void
    {
        $em = $this->getEntityManager();
        $em->persist($place);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
