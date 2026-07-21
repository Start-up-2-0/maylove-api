<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Bouquet\DigitalBouquetStatus;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<DigitalBouquet> */
final class DigitalBouquetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigitalBouquet::class);
    }

    public function findOwnedById(Uuid $id, Uuid $userId): ?DigitalBouquet
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.id = :id')
            ->andWhere('b.user = :userId')
            ->andWhere('b.archivedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<DigitalBouquet> */
    public function findByUser(User $user, ?DigitalBouquetStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.archivedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('b.updatedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('b.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedBySlug(string $slug): ?DigitalBouquet
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.slug = :slug')
            ->andWhere('b.status = :status')
            ->andWhere('b.archivedAt IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('status', DigitalBouquetStatus::Published)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsBySlug(string $slug, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('b.id != :excludeId')->setParameter('excludeId', $excludeId, 'uuid');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function incrementViewsCount(Uuid $id): void
    {
        $this->createQueryBuilder('b')
            ->update()
            ->set('b.viewsCount', 'b.viewsCount + 1')
            ->where('b.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function save(DigitalBouquet $bouquet): void
    {
        $em = $this->getEntityManager();
        $em->persist($bouquet);
        $em->flush();
    }
}
