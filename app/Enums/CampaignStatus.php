<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Completed = 'completed';
}
