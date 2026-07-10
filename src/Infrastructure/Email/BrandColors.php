<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

/** Cores da identidade visual MayLov (marca visível). */
final class BrandColors
{
    public const NAME = 'MayLov';

    public const PRIMARY = '#E11D7A';
    public const PRIMARY_STRONG = '#BE185D';
    public const BLUE = '#1D4ED8';
    public const RED = '#DC2626';
    public const INK = '#0F0D12';
    public const TEXT = '#1A1620';
    public const BG = '#FAF8FA';
    public const SURFACE = '#FFFFFF';
    public const MUTED = '#5C5568';

    public const GRADIENT_BUTTON = 'linear-gradient(135deg, #1D4ED8 0%, #E11D7A 55%, #DC2626 100%)';

    private function __construct()
    {
    }
}
