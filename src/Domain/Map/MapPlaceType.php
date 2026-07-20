<?php

declare(strict_types=1);

namespace App\Domain\Map;

enum MapPlaceType: string
{
    case FirstMeeting = 'first_meeting';
    case FirstDate = 'first_date';
    case FirstKiss = 'first_kiss';
    case Proposal = 'proposal';
    case Wedding = 'wedding';
    case Anniversary = 'anniversary';
    case Trip = 'trip';
    case Vacation = 'vacation';
    case Restaurant = 'restaurant';
    case Home = 'home';
    case Milestone = 'milestone';
    case Other = 'other';
}
