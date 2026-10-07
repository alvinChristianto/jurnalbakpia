<?php

namespace Tests\Feature;

use App\Filament\Resources\TransactionResource;
use App\Filament\Resources\TransactionResource\Pages\CreateTransaction;
use App\Filament\Resources\TransactionResource\Pages\EditTransaction;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TransactionCreateTest extends TestCase
{
    use RefreshDatabase;

    private string $outletId;

    private int $bakpiaId;

    private int $otherProductId;

    private int $paymentId;

    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_resource_pages_mount_and_render(): void
    {
        $this->seedReferenceRows();
        $this->actingAs($this->createAdminUser());

        Livewire::test(ListTransactions::class)
            ->assertOk();

        Livewire::test(CreateTransaction::class)
            ->assertOk()
            ->assertFormExists();
    }

    public function test_edit_page_allows_adding_and_editing_line_items(): void
    {
        $this->seedReferenceRows();

        // A non-admin user is scoped to their assigned outlets by getEloquentQuery().
        $admin = $this->createAdminUser(['view_any_transaction', 'update_transaction']);
        $admin->forceFill(['outlets' => [$this->outletId]])->save();

        $this->actingAs($admin);

        $transaction = Transaction::create([
            'id_transaction' => 'TRX_260929003',
            'id_customer' => $this->customerId,
            'id_payment' => $this->paymentId,
            'id_outlet' => $this->outletId,
            'transaction_details' => [
                ['product_type' => 'BAKPIA', 'product_id' => $this->bakpiaId, 'product_name' => 'Bakpia Keju Panjang', 'price_unit' => 50000, 'price_per' => 100000, 'amount' => 2],
            ],
            'total_price' => 100000,
            'status' => 'PAID',
        ]);

        $repeater = Livewire::test(EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->assertOk()
            ->assertFormExists()
            ->instance()
            ->getForm('form')
            ->getComponent('data.transaction_details');

        $this->assertFalse($repeater->isDisabled(), 'The line repeater must stay editable so same-day corrections are possible.');
        $this->assertTrue($repeater->isAddable(), 'The "+ Tambah Baris" button must be available on the edit page.');
        $this->assertTrue($repeater->isDeletable());
        $this->assertTrue($repeater->isDehydrated(), 'Line items must be saved on update, not discarded.');
    }

    public function test_editing_a_legacy_line_preserves_its_box_varian_snapshot(): void
    {
        $this->seedReferenceRows();

        $admin = $this->createAdminUser(['view_any_transaction', 'update_transaction']);
        $admin->forceFill(['outlets' => [$this->outletId]])->save();
        $this->actingAs($admin);

        // A line written before the variant was dropped: the only trace of the
        // box size is the box_varian key inside the JSON snapshot.
        $transaction = Transaction::create([
            'id_transaction' => 'TRX_260929004',
            'id_customer' => $this->customerId,
            'id_payment' => $this->paymentId,
            'id_outlet' => $this->outletId,
            'transaction_details' => [
                ['product_type' => 'BAKPIA', 'product_id' => $this->bakpiaId, 'product_name' => 'Bakpia Keju Panjang', 'box_varian' => 'box_8', 'price_unit' => 50000, 'price_per' => 100000, 'amount' => 2],
            ],
            'total_price' => 100000,
            'status' => 'PAID',
        ]);

        Livewire::test(EditTransaction::class, ['record' => $transaction->getRouteKey()])
            ->assertOk()
            ->call('save')
            ->assertHasNoErrors();

        $fresh = Transaction::find('TRX_260929004');

        $this->assertSame('box_8', $fresh->transaction_details[0]['box_varian']);
        $this->assertSame(50000, $fresh->transaction_details[0]['price_unit']);
        $this->assertSame(100000, $fresh->transaction_details[0]['price_per']);
    }

    public function test_creates_stock_sold_records_for_bakpia_items_with_sufficient_stock(): void
    {
        $this->seedReferenceRows();

        $result = Transaction::createStockSoldRecords(
            idOutlet: $this->outletId,
            idTransaction: 'TRX_260918001',
            details: [
                [
                    'product_type' => 'BAKPIA',
                    'product_id' => $this->bakpiaId,
                    'amount' => 2,
                    'price_per' => 100000,
                ],
                [
                    'product_type' => 'OTHER',
                    'product_id' => $this->otherProductId,
                    'amount' => 3,
                    'price_per' => 45000,
                ],
            ],
        );

        $this->assertSame(1, $result['created']);
        $this->assertEmpty($result['insufficient']);

        $this->assertDatabaseHas('bakpia_stocks', [
            'id_transaction' => 'TRX_260918001',
            'id_bakpia' => $this->bakpiaId,
            'id_outlet' => $this->outletId,
            'amount' => 2,
            'status' => 'STOCK_SOLD',
        ]);

        $this->assertSame(1, BakpiaStock::where('status', 'STOCK_SOLD')->count());
    }

    public function test_skips_stock_sold_when_bakpia_stock_is_insufficient(): void
    {
        $this->seedReferenceRows();

        $result = Transaction::createStockSoldRecords(
            idOutlet: $this->outletId,
            idTransaction: 'TRX_260918002',
            details: [
                [
                    'product_type' => 'BAKPIA',
                    'product_id' => $this->bakpiaId,
                    'amount' => 50,
                    'price_per' => 2500000,
                ],
            ],
        );

        $this->assertSame(0, $result['created']);
        $this->assertCount(1, $result['insufficient']);
        $this->assertDatabaseMissing('bakpia_stocks', ['status' => 'STOCK_SOLD']);
    }

    public function test_calculate_price_per_uses_on_hand_stock_and_snapshot_price(): void
    {
        $this->seedReferenceRows();

        [$price, $totalStock, $checkStock] = TransactionResource::calculatePricePer($this->outletId, $this->bakpiaId, 2);

        $this->assertSame(100000, $price);
        $this->assertSame(10, $totalStock);
        $this->assertSame(8, $checkStock);
    }

    public function test_transaction_model_stores_unified_details_as_snapshot(): void
    {
        $this->seedReferenceRows();

        $transaction = Transaction::create([
            'id_transaction' => 'TRX_260918003',
            'id_customer' => $this->customerId,
            'id_payment' => $this->paymentId,
            'id_outlet' => $this->outletId,
            'transaction_details' => [
                ['product_type' => 'BAKPIA', 'product_id' => $this->bakpiaId, 'amount' => 2, 'price_per' => 100000, 'product_name' => 'Bakpia Keju Panjang', 'price_unit' => 50000],
                ['product_type' => 'OTHER', 'product_id' => $this->otherProductId, 'amount' => 3, 'price_per' => 45000, 'product_name' => 'Air Mineral 600ml', 'price_unit' => 15000],
            ],
            'total_price' => 145000,
            'status' => 'PAID',
        ]);

        $fresh = Transaction::find($transaction->id_transaction);

        $this->assertSame('145000', (string) $fresh->total_price);
        $this->assertSame('PAID', $fresh->status);
        $this->assertCount(2, $fresh->transaction_details);
        $this->assertSame('BAKPIA', $fresh->transaction_details[0]['product_type']);
        $this->assertSame('OTHER', $fresh->transaction_details[1]['product_type']);
        $this->assertSame('Bakpia Keju Panjang', $fresh->transaction_details[0]['product_name']);
    }

    private function seedReferenceRows(): void
    {
        $outlet = Outlet::create([
            'id_outlet' => 'OUTLET-U-01',
            'name' => 'Outlet Test',
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

        $this->otherProductId = DB::table('other_products')->insertGetId([
            'name' => 'Air Mineral 600ml',
            'price' => 15000,
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
