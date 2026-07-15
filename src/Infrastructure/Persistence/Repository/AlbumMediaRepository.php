<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AlbumMedia>
 */
final class AlbumMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumMedia::class);
    }

    public function findActiveByIdAndAlbum(Uuid $id, Album $album): ?AlbumMedia
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.album = :album')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('album', $album)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<AlbumMedia>
     */
    public function findActiveByAlbum(Album $album): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.album = :album')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('album', $album)
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AlbumMedia>
     */
    public function findActivePhotosByAlbum(Album $album, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.album = :album')
            ->andWhere('m.mediaType = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('album', $album)
            ->setParameter('type', AlbumMediaType::Photo)
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC');

        if ($limit !== null && $limit > 0) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function countActiveByAlbumAndType(Album $album, AlbumMediaType $type): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.album = :album')
            ->andWhere('m.mediaType = :type')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('album', $album)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNextSortOrder(Album $album): int
    {
        $max = $this->createQueryBuilder('m')
            ->select('MAX(m.sortOrder)')
            ->andWhere('m.album = :album')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('album', $album)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function save(AlbumMedia $media): void
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
