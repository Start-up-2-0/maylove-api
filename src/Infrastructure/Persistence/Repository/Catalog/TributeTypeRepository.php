<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Catalog;

use App\Infrastructure\Persistence\Entity\Catalog\TributeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TributeType>
 */
final class TributeTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TributeType::class);
    }

    /**
     * @return list<TributeType>
     */
    public function findAllActiveOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = true')
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveById(Uuid $id): ?TributeType
    {
        return $this->findOneBy(['id' => $id, 'isActive' => true]);
    }
}
