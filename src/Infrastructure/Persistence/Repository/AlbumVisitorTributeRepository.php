<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumVisitorTribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlbumVisitorTribute>
 */
final class AlbumVisitorTributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumVisitorTribute::class);
    }

    public function save(AlbumVisitorTribute $tribute): void
    {
        $this->getEntityManager()->persist($tribute);
        $this->getEntityManager()->flush();
    }

    /**
     * @return list<AlbumVisitorTribute>
     */
    public function findActiveByAlbum(Album $album, int $limit = 100): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('t')
            ->andWhere('t.album = :album')
            ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
            ->setParameter('album', $album)
            ->setParameter('now', $now)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
