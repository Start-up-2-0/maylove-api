<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Infrastructure\Persistence\Entity\AuthSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AuthSession>
 */
final class AuthSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthSession::class);
    }

    public function findActiveByAccessTokenHash(string $hash, Uuid $sessionId): ?AuthSession
    {
        return $this->createQueryBuilder('s')
            ->addSelect('u')
            ->innerJoin('s.user', 'u')
            ->andWhere('s.accessTokenHash = :hash')
            ->andWhere('s.id = :sessionId')
            ->andWhere('s.revokedAt IS NULL')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('hash', $hash)
            ->setParameter('sessionId', $sessionId, 'uuid')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByRefreshTokenHash(string $hash): ?AuthSession
    {
        return $this->createQueryBuilder('s')
            ->addSelect('u')
            ->innerJoin('s.user', 'u')
            ->andWhere('s.refreshTokenHash = :hash')
            ->andWhere('s.revokedAt IS NULL')
            ->andWhere('s.expiresAt > :now')
            ->setParameter('hash', $hash)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(Uuid $sessionId): ?AuthSession
    {
        return $this->find($sessionId);
    }

    public function save(AuthSession $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }
}
