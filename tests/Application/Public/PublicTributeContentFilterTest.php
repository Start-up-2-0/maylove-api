<?php

declare(strict_types=1);

namespace App\Tests\Application\Public;

use App\Application\Public\PublicTributeContentFilter;
use PHPUnit\Framework\TestCase;

final class PublicTributeContentFilterTest extends TestCase
{
    public function testItPublishesProposalAndCompatibilityFields(): void
    {
        $result = (new PublicTributeContentFilter())->filter([
            'question' => 'Quer namorar comigo?',
            'celebration' => 'Você disse sim! 💞',
            'romance_experience_id' => 'pedido-namoro',
            'internal_debug' => 'não pode vazar',
        ]);

        self::assertSame([
            'question' => 'Quer namorar comigo?',
            'celebration' => 'Você disse sim! 💞',
            'romance_experience_id' => 'pedido-namoro',
        ], $result);
    }

    public function testItHandlesLegacyNullContent(): void
    {
        self::assertSame([], (new PublicTributeContentFilter())->filter(null));
    }
}
