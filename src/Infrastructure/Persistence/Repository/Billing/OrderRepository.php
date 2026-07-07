<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Billing;

use App\Infrastructure\Persistence\Entity\Billing\Order;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Order>
 */
final class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByTribute(Tribute $tribute): ?Order
    {
        return $this->findOneBy(['tribute' => $tribute]);
    }

    public function findByExternalReference(string $externalReference): ?Order
    {
        return $this->findOneBy(['externalReference' => $externalReference]);
    }

    public function findOwnedById(Uuid $id, Uuid $userId): ?Order
    {
        return $this->createQueryBuilder('o')
            ->addSelect('t', 'pp')
            ->innerJoin('o.tribute', 't')
            ->innerJoin('o.productPrice', 'pp')
            ->andWhere('o.id = :id')
            ->andWhere('o.user = :userId')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Order $order): void
    {
        $em = $this->getEntityManager();
        $em->persist($order);
        $em->flush();
    }
}
