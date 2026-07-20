<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum AlbumCategory: string
{
    case Couple = 'couple';
    case Wedding = 'wedding';
    case Proposal = 'proposal';
    case MarriageProposal = 'marriage_proposal';
    case Birthday = 'birthday';
    case Family = 'family';
    case Parents = 'parents';
    case Mother = 'mother';
    case Children = 'children';
    case Friends = 'friends';
    case Graduation = 'graduation';
    case Company = 'company';
    case Pet = 'pet';
    case Memorial = 'memorial';
    case Posthumous = 'posthumous';
    case Holiday = 'holiday';
    case Custom = 'custom';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
