<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\FlashSaleStatus;
use App\Models\OlFlashSaleItem;
use App\Models\OlProduct;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class OlPricingService
{
    public function effectivePrice(OlProduct $product, ?CarbonInterface $now = null): EffectivePrice
    {
        return $this->effectivePrices(collect([$product]), $now)->get($product->id);
    }

    /**
     * Batch variant: one query for the active flash-sale lookup regardless
     * of collection size (no N+1 on the product list endpoint).
     *
     * @param  Collection<int, OlProduct>  $products
     * @return Collection<string, EffectivePrice> keyed by product id
     */
    public function effectivePrices(Collection $products, ?CarbonInterface $now = null): Collection
    {
        $now = $now ?: Carbon::now();

        $flashItems = OlFlashSaleItem::query()
            ->join('ol_flash_sales', 'ol_flash_sales.id', '=', 'ol_flash_sale_items.flash_sale_id')
            ->where('ol_flash_sales.status', FlashSaleStatus::PUBLISHED->value)
            ->where('ol_flash_sales.start_at', '<=', $now)
            ->where('ol_flash_sales.end_at', '>', $now)
            ->where(function ($query): void {
                $query->whereNull('ol_flash_sale_items.quota')
                    ->orWhereRaw('ol_flash_sale_items.sold_count < ol_flash_sale_items.quota');
            })
            ->whereIn('ol_flash_sale_items.product_id', $products->map(fn ($p) => $p->id))
            ->get(['ol_flash_sale_items.*', 'ol_flash_sales.end_at as sale_ends_at']);

        return $products->mapWithKeys(function (OlProduct $product) use ($flashItems): array {
            $original = (int) round((float) $product->price);

            $flashItem = $flashItems->firstWhere('product_id', $product->id);
            if ($flashItem) {
                return [$product->id => $this->apply(
                    $original,
                    $flashItem->discount_type,
                    $flashItem->discount_value,
                    'flash_sale',
                    $flashItem->id,
                    Carbon::parse($flashItem->sale_ends_at),
                )];
            }

            $productDiscount = (int) $product->discount_percent;
            if ($productDiscount > 0) {
                return [$product->id => $this->apply(
                    $original,
                    DiscountType::PERCENT,
                    $productDiscount,
                    'normal_discount',
                    null,
                    null,
                )];
            }

            return [$product->id => new EffectivePrice($original, $original, DiscountType::NONE, null, 'none')];
        });
    }

    private function apply(
        int $original,
        DiscountType $discountType,
        int $value,
        string $source,
        ?string $flashSaleItemId,
        ?CarbonInterface $endsAt,
    ): EffectivePrice {
        $discountAmount = match ($discountType) {
            DiscountType::PERCENT => (int) floor($original * $value / 100),
            DiscountType::FIXED => min($value, $original),
            DiscountType::NONE => 0,
        };

        return new EffectivePrice(
            $original,
            $original - $discountAmount,
            $discountType,
            $discountAmount,
            $source,
            $flashSaleItemId,
            $endsAt,
        );
    }
}
