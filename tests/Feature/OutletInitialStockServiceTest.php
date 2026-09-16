<?php

namespace Tests\Feature;

use App\Models\Bakpia;
use App\Models\BakpiaShipment;
use App\Models\BakpiaStock;
use App\Models\Outlet;
use App\Services\OutletInitialStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OutletInitialStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_populate_creates_shipments_and_stock_for_every_bakpia_and_variant(): void
    {
        $bakpiaOne = $this->createBakpia('Bakpia Keju Panjang');
        $bakpiaTwo = $this->createBakpia('Bakpia Coklat');

        $outlet = Outlet::create([
            'id_outlet' => 'outlet_260916123',
            'name' => 'Outlet Baru',
            'type' => 'OFFICIAL',
            'address' => 'Jalan Baru No 1',
            'phone_number' => '081234567890',
        ]);

        $count = app(OutletInitialStockService::class)->populate($outlet, 1000);

        $this->assertSame(4, $count);

        foreach ([$bakpiaOne, $bakpiaTwo] as $bakpia) {
            foreach (['box_8', 'box_18'] as $boxVarian) {
                $this->assertDatabaseHas('bakpia_shipments', [
                    'id_bakpia' => $bakpia->id,
                    'id_outlet' => $outlet->id_outlet,
                    'status' => 'SENT',
                    'box_varian' => $boxVarian,
                    'amount' => 1000,
                ]);

                $this->assertDatabaseHas('bakpia_stocks', [
                    'id_bakpia' => $bakpia->id,
                    'id_outlet' => $outlet->id_outlet,
                    'status' => 'STOCK_IN',
                    'box_varian' => $boxVarian,
                    'amount' => 1000,
                ]);
            }
        }

        $this->assertSame(4, BakpiaShipment::count());
        $this->assertSame(4, BakpiaStock::count());
    }

    public function test_populate_with_zero_bakpias_creates_nothing(): void
    {
        $outlet = Outlet::create([
            'id_outlet' => 'outlet_260916456',
            'name' => 'Outlet Kosong',
            'type' => 'CABIN',
            'address' => 'Jalan Kosong No 1',
            'phone_number' => '081298765432',
        ]);

        $count = app(OutletInitialStockService::class)->populate($outlet, 500);

        $this->assertSame(0, $count);
        $this->assertDatabaseMissing('bakpia_shipments', ['id_outlet' => $outlet->id_outlet]);
        $this->assertDatabaseMissing('bakpia_stocks', ['id_outlet' => $outlet->id_outlet]);
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
