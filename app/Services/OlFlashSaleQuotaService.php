<?php

namespace App\Services;

use App\Exceptions\OlFlashSaleSoldOutException;
use App\Models\OlEcommerceTransaction;
use App\Models\OlFlashSaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OlFlashSaleQuotaService
{
    /**
     * Atomically reserve one unit-batch per flash-sale line.
     * Uses a conditional UPDATE so concurrent checkouts can never oversell;
     * 0 affected rows means sold out (spec bakpia-discount-flashsale.md §7.4).
     *
     * @param  Collection<int, array{item: OlFlashSaleItem, qty: int}>  $reservations
     */
    public static function reserve(Collection $reservations): void
    {
        foreach ($reservations as $reservation) {
            $qty = (int) $reservation['qty'];
            $item = $reservation['item'];

            $affected = DB::table('ol_flash_sale_items')
                ->where('id', $item->id)
                ->whereRaw('(quota IS NULL OR sold_count + ? <= quota)', [$qty])
                ->update(['sold_count' => DB::raw('sold_count + '.$qty)]);

            if (! $affected) {
                throw new OlFlashSaleSoldOutException($item->product_id, $item->id);
            }
        }
    }

    /**
     * Release the reservation held by a pending transaction (expiry, deny,
     * cancel, failure). Clamped at zero so it is safe to run twice.
     */
    public static function releaseForTransaction(OlEcommerceTransaction $transaction): void
    {
        $details = $transaction->details()
            ->whereNotNull('flash_sale_item_id')
            ->get(['flash_sale_item_id', 'quantity']);

        foreach ($details as $detail) {
            $qty = (int) $detail->quantity;

            $exact = DB::table('ol_flash_sale_items')
                ->where('id', $detail->flash_sale_item_id)
                ->whereRaw('sold_count >= ?', [$qty])
                ->decrement('sold_count', $qty);

            if (! $exact) {
                DB::table('ol_flash_sale_items')
                    ->where('id', $detail->flash_sale_item_id)
                    ->whereRaw('sold_count > 0')
                    ->update(['sold_count' => 0]);
            }
        }
    }
}
