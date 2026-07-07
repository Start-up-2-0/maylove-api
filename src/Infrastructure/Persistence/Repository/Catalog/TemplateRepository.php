<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Catalog;

use App\Infrastructure\Persistence\Entity\Catalog\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Template>
 */
final class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * @return list<Template>
     */
    public function findAllActiveOrdered(?Uuid $tributeTypeId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('c')
            ->innerJoin('t.category', 'c')
            ->andWhere('t.isActive = true')
            ->orderBy('t.name', 'ASC');

        if ($tributeTypeId !== null) {
            $qb
                ->innerJoin('App\Infrastructure\Persistence\Entity\Catalog\TemplateTributeType', 'ttt', 'WITH', 'ttt.template = t')
                ->andWhere('ttt.tributeType = :tributeTypeId')
                ->setParameter('tributeTypeId', $tributeTypeId, 'uuid');
        }

        return $qb->getQuery()->getResult();
    }

    public function findActiveById(Uuid $id): ?Template
    {
        return $this->createQueryBuilder('t')
            ->addSelect('c')
            ->innerJoin('t.category', 'c')
            ->andWhere('t.id = :id')
            ->andWhere('t.isActive = true')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function isLinkedToTributeType(Uuid $templateId, Uuid $tributeTypeId): bool
    {
        $count = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(ttt.id)')
            ->from('App\Infrastructure\Persistence\Entity\Catalog\TemplateTributeType', 'ttt')
            ->andWhere('ttt.template = :templateId')
            ->andWhere('ttt.tributeType = :tributeTypeId')
            ->setParameter('templateId', $templateId, 'uuid')
            ->setParameter('tributeTypeId', $tributeTypeId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
