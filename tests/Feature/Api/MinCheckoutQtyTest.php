<?php

namespace Tests\Feature\Api;

use App\Models\OlEcommerceTransaction;
use App\Models\OlProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinCheckoutQtyTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $orderItems, array $overrides = []): array
    {
        return array_merge([
            'customer_data' => [
                'namaPenerima' => 'Test Buyer',
                'email' => 'buyer@example.com',
                'nomorTelepon' => '08123456789',
            ],
            'order_items' => $orderItems,
            'total_price' => 27500,
            'shipping_address' => ['type' => 'pickup'],
            'shipping_cost' => 0,
            'tax_amount' => 2500,
        ], $overrides);
    }

    public function test_single_item_qty_one_is_rejected(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        $this->postJson('/api/midtranstokenv1', $this->payload([
            ['id' => $product->id, 'name' => $product->name, 'price' => 25000, 'quantity' => 1],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_items');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_single_item_qty_two_passes_validation(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        // subtotal 50.000 + admin fee 5.000 (10%, under cap) = 55.000
        $response = $this->postJson('/api/midtranstokenv1', $this->payload([
            ['id' => $product->id, 'name' => $product->name, 'price' => 25000, 'quantity' => 2],
        ], ['total_price' => 55000]));

        // Validation passed (not a 422). The response status past this point depends on
        // whether real Midtrans sandbox credentials/network are available in this env.
        $this->assertNotEquals(422, $response->status());
        $this->assertSame(1, OlEcommerceTransaction::count());
    }

    public function test_two_items_qty_one_each_passes_validation(): void
    {
        $productA = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);
        $productB = OlProduct::create([
            'name' => 'Bakpia Coklat', 'price' => 27000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        // subtotal 52.000 + admin fee 5.200 (10%, under cap) = 57.200
        $response = $this->postJson('/api/midtranstokenv1', $this->payload([
            ['id' => $productA->id, 'name' => $productA->name, 'price' => 25000, 'quantity' => 1],
            ['id' => $productB->id, 'name' => $productB->name, 'price' => 27000, 'quantity' => 1],
        ], ['total_price' => 57200]));

        $this->assertNotEquals(422, $response->status());
        $this->assertSame(1, OlEcommerceTransaction::count());
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        $this->postJson('/api/midtranstokenv1', $this->payload([
            ['id' => $product->id, 'name' => $product->name, 'price' => 25000, 'quantity' => 0],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_items.0.quantity');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $product = OlProduct::create([
            'name' => 'Bakpia Keju', 'price' => 25000, 'category' => 'BAKPIA', 'status' => 'Active',
        ]);

        $this->postJson('/api/midtranstokenv1', $this->payload([
            ['id' => $product->id, 'name' => $product->name, 'price' => 25000, 'quantity' => -1],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('order_items.0.quantity');

        $this->assertSame(0, OlEcommerceTransaction::count());
    }
}
