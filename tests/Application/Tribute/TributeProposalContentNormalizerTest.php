<?php

declare(strict_types=1);

namespace App\Tests\Application\Tribute;

use App\Application\Tribute\TributeProposalContentNormalizer;
use App\Domain\Tribute\Exception\TributeException;
use PHPUnit\Framework\TestCase;

final class TributeProposalContentNormalizerTest extends TestCase
{
    private TributeProposalContentNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TributeProposalContentNormalizer();
    }

    public function testItTrimsPreservesEmojiAndLimitsProposalCopy(): void
    {
        $result = $this->normalizer->normalize([], [
            'question' => '  Você aceita? 💍  ',
            'celebration' => str_repeat('á', 305),
        ]);

        self::assertSame('Você aceita? 💍', $result['question']);
        self::assertSame(300, mb_strlen($result['celebration']));
    }

    public function testItClearsBlankCopyWithoutChangingAbsentFields(): void
    {
        $result = $this->normalizer->normalize(
            ['question' => 'Anterior', 'celebration' => 'Personalizada', 'effects' => ['hearts']],
            ['question' => '   '],
        );

        self::assertArrayNotHasKey('question', $result);
        self::assertSame('Personalizada', $result['celebration']);
        self::assertSame(['hearts'], $result['effects']);
    }

    public function testItRejectsNonStringCopy(): void
    {
        $this->expectException(TributeException::class);
        $this->normalizer->normalize([], ['question' => ['inválido']]);
    }
}
