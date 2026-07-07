<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use App\Domain\Billing\ProductBillingMode;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'product_prices')]
#[ORM\UniqueConstraint(name: 'uniq_product_prices_mode', columns: ['product_id', 'billing_mode'])]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private Product $product;

    #[ORM\Column(name: 'billing_mode', length: 30, enumType: ProductBillingMode::class)]
    private ProductBillingMode $billingMode;

    #[ORM\Column(name: 'price_cents', type: 'integer')]
    private int $priceCents;

    #[ORM\Column(name: 'mp_plan_id', length: 120, nullable: true)]
    private ?string $mpPlanId = null;

    public function __construct(Product $product, ProductBillingMode $billingMode, int $priceCents, ?string $mpPlanId = null)
    {
        $this->id = Uuid::v7();
        $this->product = $product;
        $this->billingMode = $billingMode;
        $this->priceCents = $priceCents;
        $this->mpPlanId = $mpPlanId;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getBillingMode(): ProductBillingMode
    {
        return $this->billingMode;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function getPriceAmount(): float
    {
        return round($this->priceCents / 100, 2);
    }

    public function getMpPlanId(): ?string
    {
        return $this->mpPlanId;
    }
}
