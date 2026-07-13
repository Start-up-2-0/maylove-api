<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Album\AlbumStatus;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Album>
 */
final class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    public function findOwnedById(Uuid $id, Uuid $userId): ?Album
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.user = :userId')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Album>
     */
    public function findByUser(User $user, ?AlbumStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('a.updatedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Album
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.status = :status')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('a.isPublic = true')
            ->setParameter('slug', $slug)
            ->setParameter('status', AlbumStatus::Published)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsBySlug(string $slug, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeId, 'uuid');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function incrementViewsCount(Uuid $id): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.viewsCount', 'a.viewsCount + 1')
            ->where('a.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function save(Album $album): void
    {
        $em = $this->getEntityManager();
        $em->persist($album);
        $em->flush();
    }
}
