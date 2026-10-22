<?php

namespace App\Enums;

enum DiscountType: string
{
    case PERCENT = 'percent';
    case FIXED = 'fixed';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::PERCENT => 'Persen',
            self::FIXED => 'Nominal Rupiah',
            self::NONE => 'Tanpa Diskon',
        };
    }
}
