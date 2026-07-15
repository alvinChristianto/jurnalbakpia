<?php

namespace Tests\Feature\Api;

use App\Models\OlEcommerceTransaction;
use App\Models\OlProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeeCapTest extends TestCase
{
    use RefreshDatabase;

    private function payload(OlProduct $product, int $quantity, int $shippingCost, int $totalPrice): array
    {
        return [
            'customer_data' => [
                'namaPenerima' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'nomorTelepon' => '08123456789',
            ],
            'order_items' => [
                ['id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'quantity' => $quantity],
            ],
            'total_price' => $totalPrice,
            'shipping_address' => ['type' => 'pickup'],
            'shipping_cost' => $shippingCost,
            'tax_amount' => 0, // ignored server-side; fee is derived, not trusted from the client
        ];
    }

    /** @dataProvider capBoundaryProvider */
    public function test_admin_fee_is_capped_at_the_configured_max(int $subtotal, int $expectedFee): void
    {
        // quantity 2 to satisfy the separate min-checkout-qty rule (config('order.min_checkout_qty'))
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => intdiv($subtotal, 2), 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        $totalPrice = $subtotal + $expectedFee;

        $response = $this->postJson('/api/midtranstokenv1', $this->payload($product, 2, 0, $totalPrice));

        $this->assertNotEquals(422, $response->status());

        $transaction = OlEcommerceTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame($expectedFee, (int) $transaction->service_fee);
        $this->assertSame($totalPrice, (int) $transaction->grand_total);
    }

    public static function capBoundaryProvider(): array
    {
        return [
            'under cap, 10%' => [50000, 5000],
            'exactly at cap boundary' => [100000, 10000],
            'above cap' => [250000, 10000],
            'far above cap' => [1000000, 10000],
        ];
    }

    public function test_tampered_total_price_is_rejected_with_price_mismatch(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 50000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        // Real total should be 100.000 (2x50.000) + 10.000 (capped fee) = 110.000; client claims 100.000.
        $this->postJson('/api/midtranstokenv1', $this->payload($product, 2, 0, 100000))
            ->assertStatus(422)
            ->assertJsonValidationErrors('price_mismatch');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_unknown_product_id_is_rejected(): void
    {
        $this->postJson('/api/midtranstokenv1', [
            'customer_data' => [
                'namaPenerima' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'nomorTelepon' => '08123456789',
            ],
            'order_items' => [
                ['id' => 'does-not-exist', 'name' => 'Ghost Product', 'price' => 25000, 'quantity' => 2],
            ],
            'total_price' => 50000,
            'shipping_address' => ['type' => 'pickup'],
            'shipping_cost' => 0,
            'tax_amount' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_items');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_inactive_product_is_rejected(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Inactive',
        ]);

        $this->postJson('/api/midtranstokenv1', $this->payload($product, 2, 0, 50000))
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_items');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_checkout_config_is_public_and_returns_fee_rules(): void
    {
        $this->getJson('/api/checkout/config')
            ->assertOk()
            ->assertJsonPath('admin_fee_percent', 10)
            ->assertJsonPath('admin_fee_max', 10000);
    }
}
