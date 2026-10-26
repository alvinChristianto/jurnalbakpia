<?php

namespace Tests\Unit;

use App\Enums\DiscountType;
use App\Models\OlFlashSale;
use App\Models\OlFlashSaleItem;
use App\Models\OlProduct;
use App\Models\User;
use App\Services\OlPricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OlPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private OlPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = new OlPricingService;
        $this->creator = User::factory()->create();
    }

    private function product(int $price, int $discountPercent = 0): OlProduct
    {
        return OlProduct::create([
            'name' => 'Bakpia Test',
            'price' => $price,
            'discount_percent' => $discountPercent,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);
    }

    private function sale(string $status, Carbon $startAt, int $durationMinutes = 60): OlFlashSale
    {
        return OlFlashSale::create([
            'name' => 'Flash Sale Test',
            'start_at' => $startAt,
            'duration_minutes' => $durationMinutes,
            'end_at' => $startAt->copy()->addMinutes($durationMinutes),
            'status' => $status,
            'created_by' => $this->creator->id,
        ]);
    }

    private function item(OlFlashSale $sale, OlProduct $product, string $discountType, int $discountValue, ?int $quota = null, int $soldCount = 0): OlFlashSaleItem
    {
        return OlFlashSaleItem::create([
            'flash_sale_id' => $sale->id,
            'product_id' => $product->id,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'quota' => $quota,
            'sold_count' => $soldCount,
        ]);
    }

    public function test_product_without_discounts_returns_full_price(): void
    {
        $product = $this->product(10000);

        $price = $this->pricing->effectivePrice($product);

        $this->assertSame(10000, $price->finalPrice);
        $this->assertSame(10000, $price->originalPrice);
        $this->assertSame(DiscountType::NONE, $price->discountType);
        $this->assertSame('none', $price->source);
        $this->assertNull($price->discountLabel());
    }

    public function test_normal_discount_floors_the_rupiah_amount(): void
    {
        $product = $this->product(25123, 20); // 20% of 25.123 = 5.024,6 -> floor 5.024

        $price = $this->pricing->effectivePrice($product);

        $this->assertSame(20099, $price->finalPrice);
        $this->assertSame(DiscountType::PERCENT, $price->discountType);
        $this->assertSame('normal_discount', $price->source);
    }

    public function test_zero_percent_discount_is_source_none(): void
    {
        $product = $this->product(10000, 0);

        $price = $this->pricing->effectivePrice($product);

        $this->assertSame('none', $price->source);
        $this->assertSame(10000, $price->finalPrice);
    }

    public function test_hundred_percent_discount_makes_final_price_zero(): void
    {
        $product = $this->product(10000, 100);

        $price = $this->pricing->effectivePrice($product);

        $this->assertSame(0, $price->finalPrice);
        $this->assertSame(10000, $price->discountAmount);
    }

    public function test_fixed_discount_floors_at_zero_when_larger_than_price(): void
    {
        $product = $this->product(10000);
        $this->sale('published', Carbon::parse('2026-10-22 10:00:00'));
        $this->item(OlFlashSale::first(), $product, 'fixed', 15000);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame(0, $price->finalPrice);
        $this->assertSame(10000, $price->discountAmount);
        $this->assertSame('flash_sale', $price->source);
    }

    public function test_flash_sale_active_exactly_at_start_at(): void
    {
        $product = $this->product(10000);
        $startAt = Carbon::parse('2026-10-22 10:00:00');
        $this->sale('published', $startAt);
        $this->item(OlFlashSale::first(), $product, 'percent', 20);

        $price = $this->pricing->effectivePrice($product, $startAt);

        $this->assertSame('flash_sale', $price->source);
        $this->assertSame(8000, $price->finalPrice);
    }

    public function test_flash_sale_inactive_exactly_at_end_at(): void
    {
        $product = $this->product(10000);
        $startAt = Carbon::parse('2026-10-22 10:00:00');
        $this->sale('published', $startAt, 60); // ends at 11:00
        $this->item(OlFlashSale::first(), $product, 'percent', 20);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 11:00:00'));

        $this->assertSame('none', $price->source);
        $this->assertSame(10000, $price->finalPrice);
    }

    public function test_flash_sale_wins_over_normal_discount_without_stacking(): void
    {
        $product = $this->product(10000, 25);
        $this->sale('published', Carbon::parse('2026-10-22 10:00:00'));
        $this->item(OlFlashSale::first(), $product, 'fixed', 5000);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame('flash_sale', $price->source);
        $this->assertSame(5000, $price->finalPrice);
    }

    public function test_quota_reached_falls_back_to_normal_discount(): void
    {
        $product = $this->product(10000, 10);
        $this->sale('published', Carbon::parse('2026-10-22 10:00:00'), 60);
        $this->item(OlFlashSale::first(), $product, 'percent', 50, quota: 5, soldCount: 5);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame('normal_discount', $price->source);
        $this->assertSame(9000, $price->finalPrice);
    }

    public function test_draft_sale_is_ignored(): void
    {
        $product = $this->product(10000);
        $this->sale('draft', Carbon::parse('2026-10-22 10:00:00'));
        $this->item(OlFlashSale::first(), $product, 'percent', 50);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame('none', $price->source);
    }

    public function test_cancelled_sale_is_ignored(): void
    {
        $product = $this->product(10000);
        $this->sale('cancelled', Carbon::parse('2026-10-22 10:00:00'));
        $this->item(OlFlashSale::first(), $product, 'percent', 50);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame('none', $price->source);
    }

    public function test_fixed_flash_sale_discount_label_formats_rupiah(): void
    {
        $product = $this->product(10000);
        $this->sale('published', Carbon::parse('2026-10-22 10:00:00'));
        $this->item(OlFlashSale::first(), $product, 'fixed', 5000);

        $price = $this->pricing->effectivePrice($product, Carbon::parse('2026-10-22 10:30:00'));

        $this->assertSame('-Rp 5.000 Flash Sale', $price->discountLabel());
    }

    public function test_percent_label_shows_reduction_and_source(): void
    {
        $product = $this->product(10000, 25);

        $price = $this->pricing->effectivePrice($product);

        $this->assertSame('-25%', $price->discountLabel());
    }
}
