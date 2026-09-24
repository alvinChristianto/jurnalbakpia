<?php

namespace App\Services;

use App\Models\BakpiaShipment;
use App\Models\BakpiaStock;
use App\Models\Outlet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MassBakpiaShipmentService
{
    public function dispatch(Outlet $outlet, array $items, ?string $description = null, ?Carbon $shipmentDate = null): int
    {
        $now = Carbon::now();
        $shipmentDate = $shipmentDate ?? $now;

        return DB::transaction(function () use ($outlet, $items, $description, $shipmentDate, $now): int {
            $count = 0;

            foreach ($items as $item) {
                BakpiaShipment::create([
                    'id_bakpia' => $item['id_bakpia'],
                    'id_outlet' => $outlet->id_outlet,
                    'status' => 'SENT',
                    'box_varian' => $item['box_varian'],
                    'amount' => $item['amount'],
                    'description' => $description,
                    'shipment_date' => $shipmentDate,
                ]);

                BakpiaStock::create([
                    'id_outlet' => $outlet->id_outlet,
                    'id_bakpia' => $item['id_bakpia'],
                    'id_transaction' => '',
                    'box_varian' => $item['box_varian'],
                    'amount' => $item['amount'],
                    'status' => 'STOCK_IN',
                    'stock_record_date' => $now,
                ]);

                $count++;
            }

            return $count;
        });
    }
}
