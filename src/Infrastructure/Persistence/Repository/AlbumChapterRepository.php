<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumChapter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AlbumChapter>
 */
final class AlbumChapterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumChapter::class);
    }

    public function findByIdAndAlbum(Uuid $id, Album $album): ?AlbumChapter
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.album = :album')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('album', $album)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<AlbumChapter>
     */
    public function findOrderedByAlbum(Album $album): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.album = :album')
            ->setParameter('album', $album)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSortOrder(Album $album): int
    {
        $max = $this->createQueryBuilder('c')
            ->select('MAX(c.sortOrder)')
            ->andWhere('c.album = :album')
            ->setParameter('album', $album)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function save(AlbumChapter $chapter): void
    {
        $em = $this->getEntityManager();
        $em->persist($chapter);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
