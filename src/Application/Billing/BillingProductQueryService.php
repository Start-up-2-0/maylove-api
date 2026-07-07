<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Exception\BillingException;
use App\Infrastructure\Persistence\Entity\Billing\ProductPrice;
use App\Infrastructure\Persistence\Repository\Billing\ProductPriceRepository;
use App\Infrastructure\Persistence\Repository\Billing\ProductRepository;

final class BillingProductQueryService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductPriceRepository $productPriceRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getPremiumProduct(): array
    {
        $product = $this->productRepository->findActivePremium();
        if ($product === null) {
            throw new BillingException('Produto não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $prices = $this->productPriceRepository->findByProduct($product);

        return [
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'features' => $product->getFeaturesJson(),
            'prices' => array_map(
                fn (ProductPrice $price) => [
                    'billing_mode' => $price->getBillingMode()->value,
                    'price_cents' => $price->getPriceCents(),
                    'formatted' => $this->formatBrl($price->getPriceCents()),
                ],
                $prices,
            ),
        ];
    }

    private function formatBrl(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
