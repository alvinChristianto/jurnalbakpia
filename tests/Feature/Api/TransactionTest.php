<?php

namespace Tests\Feature\Api;

use App\Models\OlCustomer;
use App\Models\OlEcommerceTransaction;
use App\Models\OlEcommerceTransactionDetail;
use App\Models\OlProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_lookup_returns_404_when_invoice_unknown(): void
    {
        $this->getJson('/api/transaction/INV-DOES-NOT-EXIST')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_transaction_lookup_returns_transaction_with_details(): void
    {
        $customer = OlCustomer::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password1'),
        ]);
        $product = OlProduct::create([
            'name' => 'Bakpia Keju',
            'price' => 35000,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);
        $tx = OlEcommerceTransaction::create([
            'invoice_number' => 'INV-001',
            'ol_customer_id' => $customer->id,
            'subtotal' => 70000,
            'shipping_cost' => 10000,
            'service_fee' => 7000,
            'grand_total' => 87000,
            'shipping_address_snapshot' => ['name' => 'Buyer', 'address' => 'Jl. Test 1'],
        ]);
        OlEcommerceTransactionDetail::create([
            'transaction_id' => $tx->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'quantity' => 2,
            'price_per_item' => 35000,
        ]);

        $this->getJson('/api/transaction/INV-001')
            ->assertOk()
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.data.invoice_number', 'INV-001')
            ->assertJsonPath('data.data.grand_total', '87000.00')
            ->assertJsonCount(1, 'data.details');
    }

    public function test_orderlists_requires_authentication(): void
    {
        $this->getJson('/api/orderlists')->assertStatus(401);
    }

    public function test_orderlists_returns_only_current_customer_orders(): void
    {
        $alice = OlCustomer::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password1'),
        ]);
        $bob = OlCustomer::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password1'),
        ]);

        OlEcommerceTransaction::create([
            'invoice_number' => 'INV-ALICE-1',
            'ol_customer_id' => $alice->id,
            'subtotal' => 10000, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 10000,
            'shipping_address_snapshot' => [],
        ]);
        OlEcommerceTransaction::create([
            'invoice_number' => 'INV-ALICE-2',
            'ol_customer_id' => $alice->id,
            'subtotal' => 20000, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 20000,
            'shipping_address_snapshot' => [],
        ]);
        OlEcommerceTransaction::create([
            'invoice_number' => 'INV-BOB-1',
            'ol_customer_id' => $bob->id,
            'subtotal' => 30000, 'shipping_cost' => 0, 'service_fee' => 0, 'grand_total' => 30000,
            'shipping_address_snapshot' => [],
        ]);

        $token = $alice->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/orderlists')
            ->assertOk()
            ->assertJsonPath('data.success', true)
            ->assertJsonCount(2, 'data.orders');

        $invoices = collect($response->json('data.orders'))->pluck('invoice_number')->all();
        $this->assertEqualsCanonicalizing(['INV-ALICE-1', 'INV-ALICE-2'], $invoices);
    }
}
