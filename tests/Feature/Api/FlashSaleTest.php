<?php

namespace Tests\Feature\Api;

use App\Exceptions\OlFlashSaleSoldOutException;
use App\Models\OlCustomer;
use App\Models\OlEcommerceTransaction;
use App\Models\OlEcommerceTransactionDetail;
use App\Models\OlFlashSale;
use App\Models\OlFlashSaleItem;
use App\Models\OlProduct;
use App\Models\User;
use App\Services\OlFlashSaleQuotaService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create();
    }

    private function product(int $price, int $discountPercent = 0, string $name = 'Bakpia Test'): OlProduct
    {
        return OlProduct::create([
            'name' => $name,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);
    }

    private function sale(string $status = 'published', ?CarbonInterface $startAt = null, int $durationMinutes = 60): OlFlashSale
    {
        $startAt = $startAt ?? now();

        return OlFlashSale::create([
            'name' => 'Grebeg Bakpia',
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

    private function checkoutPayload(Collection $lines, int $totalPrice, int $shippingCost = 0): array
    {
        return [
            'customer_data' => [
                'namaPenerima' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'nomorTelepon' => '08123456789',
            ],
            'order_items' => $lines->map(fn (array $line) => [
                'id' => $line['product']->id,
                'name' => $line['product']->name,
                'price' => $line['product']->price,
                'quantity' => $line['qty'],
            ])->all(),
            'total_price' => $totalPrice,
            'shipping_address' => ['type' => 'pickup'],
            'shipping_cost' => $shippingCost,
            'tax_amount' => 0,
        ];
    }

    // ------------------------------------------------------------------
    // Public endpoints
    // ------------------------------------------------------------------

    public function test_active_endpoint_returns_sale_with_effective_prices_and_progress(): void
    {
        $sale = $this->sale('published');
        $p1 = $this->product(10000, 0, 'Keju Flash');
        $p2 = $this->product(20000, 0, 'Abon Flash');
        $this->item($sale, $p1, 'percent', 20, quota: 10, soldCount: 3);
        $this->item($sale, $p2, 'fixed', 5000, quota: 1, soldCount: 1);

        $response = $this->getJson('/api/flash-sales/active');

        $response->assertOk()
            ->assertJsonPath('data.name', 'Grebeg Bakpia')
            ->assertJsonPath('data.server_time', fn ($serverTime) => filled($serverTime))
            ->assertJsonPath('data.remaining_seconds', fn ($seconds) => $seconds > 0 && $seconds <= 3600);

        $items = collect($response->json('data.items'));
        $keju = $items->firstWhere('product_id', $p1->id);
        $abon = $items->firstWhere('product_id', $p2->id);

        $this->assertSame(8000, $keju['price']);
        $this->assertSame(10000, $keju['original_price']);
        $this->assertSame(30, $keju['progress_percent']);
        $this->assertFalse($keju['sold_out']);
        $this->assertSame('flash_sale', $keju['discount_source']);

        $this->assertSame(20000, $abon['price']);
        $this->assertTrue($abon['sold_out']);
        $this->assertSame(100, $abon['progress_percent']);
    }

    public function test_active_endpoint_returns_null_when_no_sale_is_running(): void
    {
        $this->getJson('/api/flash-sales/active')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_upcoming_endpoint_returns_published_future_sales_only(): void
    {
        $this->sale('published'); // running now, not upcoming
        $upcoming = $this->sale('published', now()->addHours(3));
        $this->sale('draft', now()->addHours(3)); // draft must not leak

        $this->getJson('/api/flash-sales/upcoming')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $upcoming->id)
            ->assertJsonPath('data.0.start_at', fn ($startAt) => filled($startAt));
    }

    public function test_products_endpoint_injects_normal_discount_price(): void
    {
        $this->product(10000, 20, 'Keju Normal');

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.data.0.price', 8000)
            ->assertJsonPath('data.data.0.original_price', 10000)
            ->assertJsonPath('data.data.0.discount_source', 'normal_discount')
            ->assertJsonPath('data.data.0.discount_type', 'percent');
    }

    public function test_products_endpoint_injects_flash_sale_price_and_countdown(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000, 10, 'Keju Flash');
        $this->item($sale, $product, 'percent', 50);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonPath('data.data.0.price', 5000)
            ->assertJsonPath('data.data.0.original_price', 10000)
            ->assertJsonPath('data.data.0.discount_source', 'flash_sale')
            ->assertJsonPath('data.data.0.discount_type', 'percent')
            ->assertJsonPath('data.data.0.discount_label', '-50% Flash Sale')
            ->assertJsonPath('data.data.0.flash_sale_ends_at', fn ($endsAt) => filled($endsAt));
    }

    // ------------------------------------------------------------------
    // Quota reservation service (unit-ish behavior through the container)
    // ------------------------------------------------------------------

    public function test_quota_reservation_increments_sold_count_atomically(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 5, soldCount: 3);

        OlFlashSaleQuotaService::reserve(collect([['item' => $item, 'qty' => 2]]));

        $this->assertDatabaseHas('ol_flash_sale_items', ['id' => $item->id, 'sold_count' => 5]);
    }

    public function test_quota_reservation_beyond_quota_is_rejected(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 5, soldCount: 4);

        $this->expectException(OlFlashSaleSoldOutException::class);

        OlFlashSaleQuotaService::reserve(collect([['item' => $item, 'qty' => 2]]));
    }

    public function test_unlimited_quota_reservation_never_rejects(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: null, soldCount: 100);

        OlFlashSaleQuotaService::reserve(collect([['item' => $item, 'qty' => 50]]));

        $this->assertDatabaseHas('ol_flash_sale_items', ['id' => $item->id, 'sold_count' => 150]);
    }

    public function test_release_decrements_sold_count_per_transaction_quantity(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 5, soldCount: 3);

        $transaction = OlEcommerceTransaction::create([
            'invoice_number' => 'INV0-RELTEST01',
            'ol_customer_id' => OlCustomer::create(['name' => 'Test Buyer', 'email' => uniqid('buyer-').'@example.com'])->id,
            'subtotal' => 100, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 100,
            'status' => 'pending',
            'shipping_address_snapshot' => ['type' => 'pickup'],
        ]);
        OlEcommerceTransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'quantity' => 3,
            'price_per_item' => 8000,
            'flash_sale_item_id' => $item->id,
        ]);

        OlFlashSaleQuotaService::releaseForTransaction($transaction);

        $this->assertDatabaseHas('ol_flash_sale_items', ['id' => $item->id, 'sold_count' => 0]);
    }

    // ------------------------------------------------------------------
    // Checkout integration
    // ------------------------------------------------------------------

    public function test_checkout_reserves_quota_and_snapshots_discount_on_details(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 10, soldCount: 0);

        // Subtotal 2 × 8000 = 16000 + 10% fee (1600) = 17600
        $payload = $this->checkoutPayload(collect([['product' => $product, 'qty' => 2]]), 17600);

        // Force an invalid server key so Snap fails deterministically (no network
        // dependence). The midtrans error must NOT undo the committed reservation
        // bookkeeping on details; quota release is asserted separately below.
        putenv('MIDTRANS_SERVER_KEY=test-invalid-key');

        $this->postJson('/api/midtranstokenv1', $payload);

        $detail = OlEcommerceTransactionDetail::first();
        $this->assertNotNull($detail);
        $this->assertEquals('8000.00', $detail->price_per_item);
        $this->assertEquals('10000.00', $detail->original_price);
        $this->assertSame('percent', $detail->getRawOriginal('discount_type'));
        $this->assertEquals('2000.00', $detail->discount_amount);
        $this->assertSame($item->id, $detail->flash_sale_item_id);

        putenv('MIDTRANS_SERVER_KEY');
    }

    public function test_sold_out_flash_sale_rejects_checkout_and_rolls_back_order(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $this->item($sale, $product, 'percent', 20, quota: 5, soldCount: 4);

        // Needs 2 items at flash price 8000 → 4 + 2 > 5 => sold out
        putenv('MIDTRANS_SERVER_KEY=test-invalid-key');

        $this->postJson('/api/midtranstokenv1', $this->checkoutPayload(
            collect([['product' => $product, 'qty' => 2]]),
            17600,
        ))->assertStatus(422);

        $this->assertSame(0, OlEcommerceTransaction::count());
        $this->assertDatabaseHas('ol_flash_sale_items', ['sold_count' => 4, 'product_id' => $product->id]);

        putenv('MIDTRANS_SERVER_KEY');
    }

    public function test_checkout_without_discounts_is_unchanged(): void
    {
        $product = $this->product(50000);

        // 2 × 50000 + 10000 fee (10% of 100000, at cap) = 110000
        putenv('MIDTRANS_SERVER_KEY=test-invalid-key');

        $this->postJson('/api/midtranstokenv1', $this->checkoutPayload(
            collect([['product' => $product, 'qty' => 2]]),
            110000,
        ));

        $detail = OlEcommerceTransactionDetail::first();
        $this->assertNotNull($detail);
        $this->assertEquals('50000.00', $detail->price_per_item);
        $this->assertNull($detail->original_price);
        $this->assertNull($detail->getRawOriginal('discount_type'));
        $this->assertNull($detail->flash_sale_item_id);

        putenv('MIDTRANS_SERVER_KEY');
    }

    // ------------------------------------------------------------------
    // Midtrans expire callback + sweep
    // ------------------------------------------------------------------

    private function signedCallback(array $payload): TestResponse
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');

        return $this->postJson('/api/midtrans-callback/', array_merge($payload, [
            'signature_key' => hash(
                'sha512',
                $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey,
            ),
        ]));
    }

    public function test_expire_callback_releases_quota_and_marks_failed(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 5, soldCount: 2);

        $transaction = OlEcommerceTransaction::create([
            'invoice_number' => 'INV0-EXPIRE01',
            'ol_customer_id' => OlCustomer::create(['name' => 'Test Buyer', 'email' => uniqid('buyer-').'@example.com'])->id,
            'subtotal' => 100, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 100,
            'status' => 'pending',
            'shipping_address_snapshot' => ['type' => 'pickup'],
        ]);
        OlEcommerceTransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'quantity' => 2,
            'price_per_item' => 8000,
            'flash_sale_item_id' => $item->id,
        ]);

        $this->signedCallback([
            'order_id' => 'INV0-EXPIRE01-AABB',
            'status_code' => '200',
            'gross_amount' => '8000',
            'transaction_status' => 'expire',
        ])->assertOk();

        $this->assertDatabaseHas('ol_ecommerce_transactions', [
            'id' => $transaction->id, 'status' => 'failed',
        ]);
        $this->assertDatabaseHas('ol_flash_sale_items', ['id' => $item->id, 'sold_count' => 0]);
    }

    public function test_callback_with_invalid_signature_is_rejected(): void
    {
        $this->postJson('/api/midtrans-callback/', [
            'order_id' => 'INV0-NOAUTH01-ABCD',
            'status_code' => '200',
            'gross_amount' => '1000',
            'transaction_status' => 'expire',
            'signature_key' => 'bad-signature',
        ])->assertStatus(400);

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_sweep_command_releases_stale_reservations_and_keeps_recent_ones(): void
    {
        $sale = $this->sale('published');
        $product = $this->product(10000);
        $item = $this->item($sale, $product, 'percent', 20, quota: 10, soldCount: 4); // 3 stale + 1 recent

        $stale = OlEcommerceTransaction::create([
            'invoice_number' => 'INV0-STALE001',
            'ol_customer_id' => OlCustomer::create(['name' => 'Test Buyer', 'email' => uniqid('buyer-').'@example.com'])->id,
            'subtotal' => 100, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 100,
            'status' => 'pending',
            'shipping_address_snapshot' => ['type' => 'pickup'],
        ]);
        $stale->created_at = now()->subHour();
        $stale->save();
        OlEcommerceTransactionDetail::create([
            'transaction_id' => $stale->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'quantity' => 3,
            'price_per_item' => 8000,
            'flash_sale_item_id' => $item->id,
        ]);

        $recent = OlEcommerceTransaction::create([
            'invoice_number' => 'INV0-RECENT01',
            'ol_customer_id' => OlCustomer::create(['name' => 'Test Buyer', 'email' => uniqid('buyer-').'@example.com'])->id,
            'subtotal' => 100, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 100,
            'status' => 'pending',
            'shipping_address_snapshot' => ['type' => 'pickup'],
        ]);
        OlEcommerceTransactionDetail::create([
            'transaction_id' => $recent->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'quantity' => 1,
            'price_per_item' => 8000,
            'flash_sale_item_id' => $item->id,
        ]);

        Artisan::call('flash-sales:release-expired');

        $this->assertDatabaseHas('ol_ecommerce_transactions', [
            'id' => $stale->id, 'status' => 'failed',
        ]);
        $this->assertDatabaseHas('ol_ecommerce_transactions', [
            'id' => $recent->id, 'status' => 'pending',
        ]);
        $this->assertDatabaseHas('ol_flash_sale_items', ['id' => $item->id, 'sold_count' => 1]);
    }
}
