<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumChapter;
use App\Infrastructure\Persistence\Entity\AlbumMemory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AlbumMemory>
 */
final class AlbumMemoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumMemory::class);
    }

    public function findByIdAndChapter(Uuid $id, AlbumChapter $chapter): ?AlbumMemory
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.chapter = :chapter')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('chapter', $chapter)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByIdAndAlbum(Uuid $id, Album $album): ?AlbumMemory
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->andWhere('m.album = :album')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('album', $album)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<AlbumMemory>
     */
    public function findOrderedByChapter(AlbumChapter $chapter): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.chapter = :chapter')
            ->setParameter('chapter', $chapter)
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AlbumMemory>
     */
    public function findOrderedByAlbum(Album $album): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.album = :album')
            ->setParameter('album', $album)
            ->orderBy('m.memoryDate', 'ASC')
            ->addOrderBy('m.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextSortOrder(AlbumChapter $chapter): int
    {
        $max = $this->createQueryBuilder('m')
            ->select('MAX(m.sortOrder)')
            ->andWhere('m.chapter = :chapter')
            ->setParameter('chapter', $chapter)
            ->getQuery()
            ->getSingleScalarResult();

        return $max !== null ? ((int) $max) + 1 : 0;
    }

    public function save(AlbumMemory $memory): void
    {
        $em = $this->getEntityManager();
        $em->persist($memory);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
