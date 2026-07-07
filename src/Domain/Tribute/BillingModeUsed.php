<?php

declare(strict_types=1);

namespace App\Domain\Tribute;

enum BillingModeUsed: string
{
    case PerTribute = 'per_tribute';
    case SubscriptionMonthly = 'subscription_monthly';
    case SubscriptionYearly = 'subscription_yearly';
}
