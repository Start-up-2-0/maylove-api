<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Map\MapMediaType;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\MapPlace;
use App\Infrastructure\Persistence\Entity\MapPlaceMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<MapPlaceMedia> */
final class MapPlaceMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapPlaceMedia::class);
    }

    /** @return list<MapPlaceMedia> */
    public function findActiveByPlace(MapPlace $place): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.place = :place')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('place', $place)
            ->orderBy('m.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByPlace(MapPlace $place): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.place = :place')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('place', $place)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveByIdAndMap(Uuid $id, CoupleMap $map): ?MapPlaceMedia
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.coupleMap = :map')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('map', $map)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextSortOrder(MapPlace $place): int
    {
        $max = $this->createQueryBuilder('m')
            ->select('MAX(m.sortOrder)')
            ->andWhere('m.place = :place')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('place', $place)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function save(MapPlaceMedia $media): void
    {
        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();
    }
}
