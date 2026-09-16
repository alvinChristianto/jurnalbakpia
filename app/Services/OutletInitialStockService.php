<?php

namespace App\Services;

use App\Models\Bakpia;
use App\Models\BakpiaShipment;
use App\Models\BakpiaStock;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OutletInitialStockService
{
    public function populate(Outlet $outlet, int $amount): int
    {
        $now = Carbon::now();

        return DB::transaction(function () use ($outlet, $amount, $now): int {
            $count = 0;

            foreach (Bakpia::all() as $bakpia) {
                foreach (['box_8', 'box_18'] as $boxVarian) {
                    BakpiaShipment::create([
                        'id_bakpia' => $bakpia->id,
                        'id_outlet' => $outlet->id_outlet,
                        'status' => 'SENT',
                        'box_varian' => $boxVarian,
                        'amount' => $amount,
                        'description' => 'Stok awal outlet baru',
                        'shipment_date' => $now,
                    ]);

                    BakpiaStock::create([
                        'id_outlet' => $outlet->id_outlet,
                        'id_bakpia' => $bakpia->id,
                        'id_transaction' => '',
                        'box_varian' => $boxVarian,
                        'amount' => $amount,
                        'status' => 'STOCK_IN',
                        'stock_record_date' => $now,
                    ]);

                    $count++;
                }
            }

            return $count;
        });
    }
}
