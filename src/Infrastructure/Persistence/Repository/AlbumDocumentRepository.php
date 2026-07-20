<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AlbumDocument>
 */
final class AlbumDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumDocument::class);
    }

    public function findByIdAndAlbum(Uuid $id, Album $album): ?AlbumDocument
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :id')
            ->andWhere('d.album = :album')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('album', $album)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(AlbumDocument $document): void
    {
        $em = $this->getEntityManager();
        $em->persist($document);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
