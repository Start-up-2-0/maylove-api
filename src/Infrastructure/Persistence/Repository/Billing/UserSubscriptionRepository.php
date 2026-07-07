<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Billing;

use App\Infrastructure\Persistence\Entity\Billing\UserSubscription;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<UserSubscription>
 */
final class UserSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSubscription::class);
    }

    public function findByUser(User $user): ?UserSubscription
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findByUserId(Uuid $userId): ?UserSubscription
    {
        return $this->createQueryBuilder('s')
            ->addSelect('pp', 'p')
            ->innerJoin('s.productPrice', 'pp')
            ->innerJoin('s.product', 'p')
            ->andWhere('s.user = :userId')
            ->setParameter('userId', $userId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByMpSubscriptionId(string $mpSubscriptionId): ?UserSubscription
    {
        return $this->findOneBy(['mpSubscriptionId' => $mpSubscriptionId]);
    }

    public function save(UserSubscription $subscription): void
    {
        $em = $this->getEntityManager();
        $em->persist($subscription);
        $em->flush();
    }
}
