<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Billing;

use App\Infrastructure\Persistence\Entity\Billing\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
final class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findByMpPaymentId(string $mpPaymentId): ?Payment
    {
        return $this->findOneBy(['mpPaymentId' => $mpPaymentId]);
    }

    public function save(Payment $payment): void
    {
        $em = $this->getEntityManager();
        $em->persist($payment);
        $em->flush();
    }
}
