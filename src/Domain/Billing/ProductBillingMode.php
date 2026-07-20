<?php

declare(strict_types=1);

namespace App\Domain\Billing;

enum ProductBillingMode: string
{
    case PerTribute = 'per_tribute';
    case PerAlbum = 'per_album';
    case PerMap = 'per_map';
    case SubscriptionMonthly = 'subscription_monthly';
    case SubscriptionYearly = 'subscription_yearly';

    public function toBillingModeUsed(): \App\Domain\Tribute\BillingModeUsed
    {
        return match ($this) {
            self::PerTribute, self::PerAlbum, self::PerMap => \App\Domain\Tribute\BillingModeUsed::PerTribute,
            self::SubscriptionMonthly => \App\Domain\Tribute\BillingModeUsed::SubscriptionMonthly,
            self::SubscriptionYearly => \App\Domain\Tribute\BillingModeUsed::SubscriptionYearly,
        };
    }
}
