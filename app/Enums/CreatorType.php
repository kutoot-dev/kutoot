<?php

namespace App\Enums;

enum CreatorType: string
{
    case Admin = 'admin';
    case Merchant = 'merchant';
    case ThirdParty = 'third_party';
}
