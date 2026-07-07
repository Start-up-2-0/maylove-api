<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository\Billing;

use App\Domain\Billing\ProductBillingMode;
use App\Infrastructure\Persistence\Entity\Billing\Product;
use App\Infrastructure\Persistence\Entity\Billing\ProductPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ProductPrice>
 */
final class ProductPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPrice::class);
    }

    /**
     * @return list<ProductPrice>
     */
    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('pp')
            ->andWhere('pp.product = :product')
            ->setParameter('product', $product)
            ->orderBy('pp.priceCents', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProductAndMode(Product $product, ProductBillingMode $mode): ?ProductPrice
    {
        return $this->createQueryBuilder('pp')
            ->andWhere('pp.product = :product')
            ->andWhere('pp.billingMode = :mode')
            ->setParameter('product', $product)
            ->setParameter('mode', $mode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findById(Uuid $id): ?ProductPrice
    {
        return $this->find($id);
    }
}
