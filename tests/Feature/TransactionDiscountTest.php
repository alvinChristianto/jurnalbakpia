<?php

namespace Tests\Feature;

use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Pages\CreateTransaction;
use App\Filament\Resources\TransactionResource\Pages\EditTransaction;
use App\Models\BakpiaStock;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionDiscountTest extends TestCase
{
    use RefreshDatabase;

    private string $outletId;

    private int $bakpiaId;

    private int $paymentId;

    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_discount_per_math(): void
    {
        $this->assertSame(10000, TransactionResource::discountPer(100000, 'PERCENT', 10));
        $this->assertSame(12500, TransactionResource::discountPer(100000, 'PERCENT', 12.5));
        $this->assertSame(5000, TransactionResource::discountPer(99999, 'PERCENT', 5), 'Half-up rounding to whole rupiah.');
        $this->assertSame(100000, TransactionResource::discountPer(100000, 'PERCENT', 150), 'Percent is clamped to 100.');
        $this->assertSame(25000, TransactionResource::discountPer(100000, 'NOMINAL', 25000));
        $this->assertSame(100000, TransactionResource::discountPer(100000, 'NOMINAL', 200000), 'Nominal is clamped to the gross value.');
        $this->assertSame(0, TransactionResource::discountPer(100000, 'PERCENT', 0));
        $this->assertSame(0, TransactionResource::discountPer(100000, 'PERCENT', -5));
        $this->assertSame(0, TransactionResource::discountPer(0, 'NOMINAL', 5000));
    }

    public function test_outlet_can_discount_gating(): void
    {
        $this->seedReferenceRows();
        $outlet = Outlet::find($this->outletId);

        $this->assertTrue($outlet->can_discount_line, 'New outlets default to allowing line discounts.');
        $this->assertTrue($outlet->can_discount_total);

        $this->assertTrue(TransactionResource::outletCan($this->outletId, 'line'));
        $this->assertTrue(TransactionResource::outletCan($this->outletId, 'total'));
        $this->assertFalse(TransactionResource::outletCan(null, 'line'), 'An unpicked outlet cannot be verified.');
        $this->assertFalse(TransactionResource::outletCan('OUTLET-MISSING', 'line'));

        $outlet->update(['can_discount_line' => false, 'can_discount_total' => false]);

        $this->assertFalse(TransactionResource::outletCan($this->outletId, 'line'));
        $this->assertFalse(TransactionResource::outletCan($this->outletId, 'total'));
    }

    public function test_create_with_percent_line_and_nominal_header_discount_persists_all_totals(): void
    {
        $this->seedReferenceRows();
        $this->actingAs($this->createAdminUser());

        // Line: 2 x 50000 = 100000 gross, 10% line discount = 10000.
        // Header: 5000 nominal. Net = 100000 - 10000 - 5000 = 85000.
        $component = Livewire::test(CreateTransaction::class);
        $key = $this->firstLineKey($component);

        $component
            ->set('data.id_outlet', $this->outletId)
            ->set("data.transaction_details.{$key}.product_id", $this->bakpiaId)
            ->set("data.transaction_details.{$key}.amount", 2)
            ->set("data.transaction_details.{$key}.discount_value", 10)
            ->set('data.discount_type', 'NOMINAL')
            ->set('data.discount_value', 5000)
            ->set('data.id_payment', $this->paymentId)
            ->set('data.id_customer', $this->customerId)
            ->call('create')
            ->assertHasNoErrors();

        $fresh = Transaction::query()->latest('created_at')->first();

        $this->assertNotNull($fresh);
        $this->assertSame(100000, (int) $fresh->total_price);
        $this->assertSame(5000, (int) $fresh->discount);
        $this->assertSame('NOMINAL', $fresh->discount_type);
        $this->assertSame(85000, (int) $fresh->total_price_after_discount);
        $this->assertSame(10000, (int) $fresh->transaction_details[0]['discount_per']);
        $this->assertSame(85000, $fresh->grandTotal());
        $this->assertSame(10000, $fresh->totalLineDiscount());

        // Stock reduction is unaffected by discounts.
        $this->assertDatabaseHas('bakpia_stocks', [
            'id_transaction' => $fresh->id_transaction,
            'amount' => 2,
            'status' => 'STOCK_SOLD',
        ]);
    }

    public function test_outlet_without_discount_permission_strips_all_discounts(): void
    {
        $this->seedReferenceRows();
        $this->actingAs($this->createAdminUser());

        Outlet::find($this->outletId)->update([
            'can_discount_line' => false,
            'can_discount_total' => false,
        ]);

        $component = Livewire::test(CreateTransaction::class);
        $key = $this->firstLineKey($component);

        $component
            ->set('data.id_outlet', $this->outletId)
            ->set("data.transaction_details.{$key}.product_type", 'OTHER')
            ->set("data.transaction_details.{$key}.product_id", $this->otherProductId())
            ->set("data.transaction_details.{$key}.amount", 3)
            ->set("data.transaction_details.{$key}.discount_type", 'NOMINAL')
            ->set("data.transaction_details.{$key}.discount_value", 10000)
            ->set('data.discount_type', 'NOMINAL')
            ->set('data.discount_value', 5000)
            ->set('data.id_payment', $this->paymentId)
            ->set('data.id_customer', $this->customerId)
            ->call('create')
            ->assertHasNoErrors();

        $fresh = Transaction::query()->latest('created_at')->first();

        $this->assertNotNull($fresh);
        $this->assertSame(45000, (int) $fresh->total_price);
        $this->assertSame(0, (int) $fresh->discount);
        $this->assertSame(0, (int) $fresh->transaction_details[0]['discount_per']);
        $this->assertSame(45000, (int) $fresh->total_price_after_discount);
    }

    public function test_percent_over_100_is_rejected_by_validation(): void
    {
        $this->seedReferenceRows();
        $this->actingAs($this->createAdminUser());

        $component = Livewire::test(CreateTransaction::class);
        $key = $this->firstLineKey($component);

        $component
            ->set('data.id_outlet', $this->outletId)
            ->set("data.transaction_details.{$key}.product_type", 'OTHER')
            ->set("data.transaction_details.{$key}.product_id", $this->otherProductId())
            ->set("data.transaction_details.{$key}.amount", 1)
            ->set("data.transaction_details.{$key}.discount_value", 101)
            ->set('data.id_payment', $this->paymentId)
            ->set('data.id_customer', $this->customerId)
            ->call('create')
            ->assertHasErrors(["data.transaction_details.{$key}.discount_value"]);
    }

    public function test_nominal_over_line_subtotal_is_rejected_by_validation(): void
    {
        $this->seedReferenceRows();
        $this->actingAs($this->createAdminUser());

        $component = Livewire::test(CreateTransaction::class);
        $key = $this->firstLineKey($component);

        // Subtotal resolves to 15000; a 20000 nominal discount exceeds it.
        $component
            ->set('data.id_outlet', $this->outletId)
            ->set("data.transaction_details.{$key}.product_type", 'OTHER')
            ->set("data.transaction_details.{$key}.product_id", $this->otherProductId())
            ->set("data.transaction_details.{$key}.amount", 1)
            ->set("data.transaction_details.{$key}.discount_type", 'NOMINAL')
            ->set("data.transaction_details.{$key}.discount_value", 20000)
            ->set('data.id_payment', $this->paymentId)
            ->set('data.id_customer', $this->customerId)
            ->call('create')
            ->assertHasErrors(["data.transaction_details.{$key}.discount_value"]);
    }

    public function test_editing_a_legacy_transaction_backfills_the_net_total(): void
    {
        $this->seedReferenceRows();

        $admin = $this->createAdminUser(['view_any_transaction', 'update_transaction']);
        $admin->forceFill(['outlets' => [$this->outletId]])->save();
        $this->actingAs($admin);

        $transaction = Transaction::create([
            'id_transaction' => 'TRX_261017001',
            'id_customer' => $this->customerId,
            'id_payment' => $this->paymentId,
            'id_outlet' => $this->outletId,
            'transaction_details' => [
                ['product_type' => 'BAKPIA', 'product_id' => $this->bakpiaId, 'product_name' => 'Bakpia Keju Panjang', 'price_unit' => 50000, 'price_per' => 100000, 'amount' => 2],
            ],
            'total_price' => 100000,
            'discount' => 10000,
            'status' => 'PAID',
        ]);

        // Pre-migration rows fall back to the computed net total.
        $this->assertSame(90000, $transaction->grandTotal());

        Livewire::test(EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->assertOk()
            ->call('save')
            ->assertHasNoErrors();

        $fresh = Transaction::find('TRX_261017001');

        $this->assertSame(100000, (int) $fresh->total_price);
        $this->assertSame(10000, (int) $fresh->discount);
        $this->assertSame(90000, (int) $fresh->total_price_after_discount);
        $this->assertSame(0, (int) $fresh->transaction_details[0]['discount_per']);
        $this->assertSame('Bakpia Keju Panjang', $fresh->transaction_details[0]['product_name']);
    }

    public function test_grand_total_prefers_the_stored_net_column(): void
    {
        $transaction = new Transaction([
            'total_price' => 100000,
            'discount' => 5000,
            'transaction_details' => [
                ['discount_per' => 2000],
                [],
            ],
        ]);

        $this->assertSame(93000, $transaction->grandTotal(), 'Fallback computes gross minus discounts.');
        $this->assertSame(2000, $transaction->totalLineDiscount());

        $transaction->total_price_after_discount = 90000;

        $this->assertSame(90000, $transaction->grandTotal(), 'Stored column wins when present.');
    }

    private int $otherProductCache = 0;

    private function firstLineKey(Testable $component): string
    {
        $key = array_key_first(
            $component->instance()->getForm('form')->getRawState()['transaction_details'] ?? []
        );

        $this->assertNotNull($key, 'The repeater must mount with a blank line to fill in.');

        return (string) $key;
    }

    private function otherProductId(): int
    {
        if ($this->otherProductCache !== 0) {
            return $this->otherProductCache;
        }

        $this->otherProductCache = DB::table('other_products')->insertGetId([
            'name' => 'Air Mineral 600ml',
            'price' => 15000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->otherProductCache;
    }

    private function seedReferenceRows(): void
    {
        $outlet = Outlet::create([
            'id_outlet' => 'OUTLET-D-01',
            'name' => 'Outlet Diskon Test',
            'type' => 'OFFICIAL',
            'phone_number' => '081234567890',
            'address' => 'Jalan Test 1',
        ]);
        $this->outletId = $outlet->id_outlet;

        $this->paymentId = Payment::create(['name' => 'Tunai', 'type' => 'CASH'])->id;

        $this->customerId = Customer::create(['name' => 'Pelanggan Test', 'gender' => 'L'])->id;

        $this->bakpiaId = DB::table('bakpias')->insertGetId([
            'name' => 'Bakpia Keju Panjang',
            'price' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BakpiaStock::create([
            'id_outlet' => $this->outletId,
            'id_bakpia' => $this->bakpiaId,
            'amount' => 10,
            'status' => 'STOCK_IN',
            'stock_record_date' => now(),
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function createAdminUser(array $permissions = ['view_any_transaction', 'create_transaction']): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        return UserFactory::new()
            ->create(['email' => 'admin@gmail.com'])
            ->givePermissionTo($permissions);
    }
}
