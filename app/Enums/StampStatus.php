<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StampStatus: string implements HasColor, HasLabel
{
    case Reserved = 'reserved';
    case Used = 'used';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => 'Reserved',
            self::Used => 'Used',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Reserved => 'warning',
            self::Used => 'success',
            self::Expired => 'danger',
        };
    }
}
