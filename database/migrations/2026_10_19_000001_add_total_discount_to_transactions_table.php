<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('total_discount')->nullable()->after('discount_value'); // header + all line discounts combined
        });

        // Portable PHP backfill: JSON array sums are dialect-specific in SQL
        // (JSON_TABLE on MySQL, jsonb on Postgres, JSON1 on SQLite), so walk
        // the rows here instead. Legacy lines carry no `discount_per` key and
        // contribute zero, so old receipts backfill to their header discount.
        DB::table('transactions')
            ->whereNull('total_discount')
            ->orderBy('id_transaction')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $lineDiscount = collect(json_decode((string) $row->transaction_details, true) ?? [])
                        ->sum(fn (array $item): int => (int) ($item['discount_per'] ?? 0));

                    DB::table('transactions')
                        ->where('id_transaction', $row->id_transaction)
                        ->update(['total_discount' => (int) $row->discount + $lineDiscount]);
                }
            }, 'id_transaction');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['total_discount']);
        });
    }
};
