<?php

namespace App\Services;

use App\Enums\DiscountType;
use Carbon\CarbonInterface;

class EffectivePrice
{
    public function __construct(
        public readonly int $originalPrice,
        public readonly int $finalPrice,
        public readonly DiscountType $discountType,
        public readonly ?int $discountAmount,
        public readonly string $source,
        public readonly ?string $flashSaleItemId = null,
        public readonly ?CarbonInterface $flashSaleEndsAt = null,
    ) {}

    public function discountLabel(): ?string
    {
        return match ($this->discountType) {
            DiscountType::PERCENT => '-'.$this->percentReduction().'%'.($this->source === 'flash_sale' ? ' Flash Sale' : ''),
            DiscountType::FIXED => '-Rp '.number_format($this->discountAmount, 0, ',', '.').($this->source === 'flash_sale' ? ' Flash Sale' : ''),
            default => null,
        };
    }

    private function percentReduction(): int
    {
        if ($this->originalPrice <= 0) {
            return 0;
        }

        return (int) floor($this->discountAmount * 100 / $this->originalPrice);
    }
}
