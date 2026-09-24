<?php

namespace Tests\Feature;

use App\Models\Bakpia;
use App\Models\BakpiaShipment;
use App\Models\BakpiaStock;
use App\Models\Outlet;
use App\Services\MassBakpiaShipmentService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MassBakpiaShipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_creates_shipments_and_stock_in_rows_for_every_item(): void
    {
        $bakpiaOne = $this->createBakpia('Bakpia Keju Panjang');
        $bakpiaTwo = $this->createBakpia('Bakpia Coklat');

        $outlet = Outlet::create([
            'id_outlet' => 'outlet_mass_260924001',
            'name' => 'Outlet Tujuan',
            'type' => 'CABIN',
            'address' => 'Jalan Tujuan No 1',
            'phone_number' => '081234567890',
        ]);

        $items = [
            ['id_bakpia' => $bakpiaOne->id, 'box_varian' => 'box_8', 'amount' => 12],
            ['id_bakpia' => $bakpiaOne->id, 'box_varian' => 'box_18', 'amount' => 7],
            ['id_bakpia' => $bakpiaTwo->id, 'box_varian' => 'box_8', 'amount' => 3],
        ];

        $shipmentDate = now()->subDay();

        $count = app(MassBakpiaShipmentService::class)->dispatch($outlet, $items, 'Kiriman cabang', $shipmentDate);

        $this->assertSame(3, $count);

        foreach ($items as $item) {
            $this->assertDatabaseHas('bakpia_shipments', [
                'id_bakpia' => $item['id_bakpia'],
                'id_outlet' => $outlet->id_outlet,
                'status' => 'SENT',
                'box_varian' => $item['box_varian'],
                'amount' => $item['amount'],
                'description' => 'Kiriman cabang',
                'shipment_date' => $shipmentDate,
            ]);

            $this->assertDatabaseHas('bakpia_stocks', [
                'id_bakpia' => $item['id_bakpia'],
                'id_outlet' => $outlet->id_outlet,
                'id_transaction' => '',
                'box_varian' => $item['box_varian'],
                'amount' => $item['amount'],
                'status' => 'STOCK_IN',
            ]);
        }

        $this->assertSame(3, BakpiaShipment::count());
        $this->assertSame(3, BakpiaStock::count());
    }

    public function test_dispatch_uses_now_as_default_shipment_date(): void
    {
        $bakpia = $this->createBakpia('Bakpia Original');

        $outlet = Outlet::create([
            'id_outlet' => 'outlet_mass_260924002',
            'name' => 'Outlet Default Tanggal',
            'type' => 'OFFICIAL',
            'address' => 'Jalan Default No 1',
            'phone_number' => '081298765432',
        ]);

        $items = [
            ['id_bakpia' => $bakpia->id, 'box_varian' => 'box_8', 'amount' => 5],
        ];

        $now = now();

        app(MassBakpiaShipmentService::class)->dispatch($outlet, $items);

        $shipment = BakpiaShipment::first();

        $this->assertEqualsWithDelta($now->timestamp, Carbon::parse($shipment->shipment_date)->timestamp, 5);
        $this->assertSame('5', (string) $shipment->amount);
    }

    public function test_dispatch_rolls_back_everything_when_a_later_item_throws(): void
    {
        $bakpia = $this->createBakpia('Bakpia Coklat Keju');

        $outlet = Outlet::create([
            'id_outlet' => 'outlet_mass_260924003',
            'name' => 'Outlet Rollback',
            'type' => 'DENTES',
            'address' => 'Jalan Rollback No 1',
            'phone_number' => '081211223344',
        ]);

        $items = [
            ['id_bakpia' => $bakpia->id, 'box_varian' => 'box_8', 'amount' => 10],
            ['id_bakpia' => PHP_INT_MAX, 'box_varian' => 'box_18', 'amount' => 99],
        ];

        $this->expectException(QueryException::class);

        app(MassBakpiaShipmentService::class)->dispatch($outlet, $items);

        $this->assertSame(0, BakpiaShipment::count());
        $this->assertSame(0, BakpiaStock::count());
    }

    private function createBakpia(string $name): Bakpia
    {
        $id = DB::table('bakpias')->insertGetId([
            'name' => $name,
            'price_8' => 50000,
            'price_18' => 95000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Bakpia::find($id);
    }
}
