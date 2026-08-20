<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Tribute>
 */
final class TributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tribute::class);
    }

    public function findOwnedById(Uuid $id, Uuid $userId): ?Tribute
    {
        return $this->createQueryBuilder('t')
            ->addSelect('tt', 'tpl')
            ->innerJoin('t.tributeType', 'tt')
            ->innerJoin('t.template', 'tpl')
            ->andWhere('t.id = :id')
            ->andWhere('t.user = :userId')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Tribute>
     */
    public function findByUser(User $user, ?TributeStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('tt', 'tpl')
            ->innerJoin('t.tributeType', 'tt')
            ->innerJoin('t.template', 'tpl')
            ->andWhere('t.user = :user')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('t.updatedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Tribute
    {
        return $this->createQueryBuilder('t')
            ->addSelect('tt', 'tpl')
            ->innerJoin('t.tributeType', 'tt')
            ->innerJoin('t.template', 'tpl')
            ->andWhere('t.slug = :slug')
            ->andWhere('t.status = :status')
            ->andWhere('t.deletedAt IS NULL')
            ->setParameter('slug', $slug)
            ->setParameter('status', TributeStatus::Published)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function incrementViewsCount(Uuid $id): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.viewsCount', 't.viewsCount + 1')
            ->where('t.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function incrementSharesCount(Uuid $id): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.sharesCount', 't.sharesCount + 1')
            ->where('t.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->execute();
    }

    public function existsBySlug(string $slug, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeId, 'uuid');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function save(Tribute $tribute): void
    {
        $em = $this->getEntityManager();
        $em->persist($tribute);
        $em->flush();
    }
}
