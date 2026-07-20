<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Map\CoupleMapStatus;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<CoupleMap> */
final class CoupleMapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoupleMap::class);
    }

    public function findOwnedById(Uuid $id, Uuid $userId): ?CoupleMap
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.user = :userId')
            ->andWhere('m.archivedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<CoupleMap> */
    public function findByUser(User $user, ?CoupleMapStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.user = :user')
            ->andWhere('m.archivedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('m.updatedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('m.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedBySlug(string $slug): ?CoupleMap
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.slug = :slug')
            ->andWhere('m.status = :status')
            ->andWhere('m.archivedAt IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('status', CoupleMapStatus::Published)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsBySlug(string $slug, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('m.id != :excludeId')->setParameter('excludeId', $excludeId, 'uuid');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function incrementViewsCount(Uuid $id): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.viewsCount', 'm.viewsCount + 1')
            ->where('m.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function save(CoupleMap $map): void
    {
        $em = $this->getEntityManager();
        $em->persist($map);
        $em->flush();
    }
}
