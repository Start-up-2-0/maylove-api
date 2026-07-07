<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Billing;

use App\Infrastructure\Persistence\Entity\Billing\PaymentEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentEvent>
 */
final class PaymentEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentEvent::class);
    }

    public function existsByExternalEventId(string $externalEventId): bool
    {
        return $this->count(['externalEventId' => $externalEventId]) > 0;
    }

    public function save(PaymentEvent $event): void
    {
        $em = $this->getEntityManager();
        $em->persist($event);
        $em->flush();
    }
}
