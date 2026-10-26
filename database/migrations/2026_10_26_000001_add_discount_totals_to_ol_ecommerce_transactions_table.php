<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header-level discount totals for online orders (plan-invoice-pdf-discount-columns).
     * The legacy `subtotal` column intentionally mirrors the grand total
     * (see plan-admin-fee-cap.md) and is left untouched; these are new columns.
     * Pre-flash-sale rows have null detail snapshot fields, so the backfill
     * falls back to `price_per_item` per line.
     */
    public function up(): void
    {
        Schema::table('ol_ecommerce_transactions', function (Blueprint $table) {
            $table->decimal('subtotal_before_discount', 12, 2)->nullable();
            $table->decimal('total_discount', 12, 2)->default(0);
        });

        // Backfill existing orders from their detail rows (chunked, memory-safe).
        DB::table('ol_ecommerce_transactions')
            ->orderBy('id')
            ->chunkById(100, function ($transactions): void {
                foreach ($transactions as $transaction) {
                    $details = DB::table('ol_ecommerce_transaction_details')
                        ->where('transaction_id', $transaction->id)
                        ->get(['quantity', 'price_per_item', 'original_price', 'discount_amount']);

                    if ($details->isEmpty()) {
                        continue; // leave null; renderers fall back defensively
                    }

                    $subtotalBeforeDiscount = 0;
                    $totalDiscount = 0;

                    foreach ($details as $detail) {
                        $qty = (int) $detail->quantity;
                        $originalPrice = $detail->original_price ?? $detail->price_per_item;

                        $subtotalBeforeDiscount += $qty * $originalPrice;
                        $totalDiscount += $qty * (float) $detail->discount_amount;
                    }

                    DB::table('ol_ecommerce_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'subtotal_before_discount' => $subtotalBeforeDiscount,
                            'total_discount' => $totalDiscount,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ol_ecommerce_transactions', function (Blueprint $table) {
            $table->dropColumn(['subtotal_before_discount', 'total_discount']);
        });
    }
};
