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
            $table->string('discount_type')->nullable()->after('discount'); // PERCENT | NOMINAL (header)
            $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type'); // header discount input
            $table->unsignedInteger('total_price_after_discount')->nullable()->after('discount_value'); // net grand total
        });

        // Exact backfill for legacy rows: they carry no per-line discount keys,
        // so the net total is simply gross minus the (possibly null) header discount.
        DB::statement('UPDATE transactions SET total_price_after_discount = total_price - COALESCE(discount, 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'total_price_after_discount']);
        });
    }
};
