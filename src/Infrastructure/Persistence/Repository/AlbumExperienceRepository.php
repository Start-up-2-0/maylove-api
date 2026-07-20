<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumExperience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AlbumExperience>
 */
final class AlbumExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbumExperience::class);
    }

    public function findByIdAndAlbum(Uuid $id, Album $album): ?AlbumExperience
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id = :id')
            ->andWhere('e.album = :album')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('album', $album)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<AlbumExperience>
     */
    public function findOrderedByAlbum(Album $album): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.album = :album')
            ->setParameter('album', $album)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(AlbumExperience $experience): void
    {
        $em = $this->getEntityManager();
        $em->persist($experience);
        $em->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
