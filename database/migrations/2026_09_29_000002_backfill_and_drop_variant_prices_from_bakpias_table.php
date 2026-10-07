<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Collapses the two per-box variant prices into the single `price` column
     * that every other product master (`iseyas`, `other_products`) already uses.
     * The old values are backfilled from `price_8` before the columns go away.
     */
    public function up(): void
    {
        DB::table('bakpias')->update(['price' => DB::raw('price_8')]);

        Schema::table('bakpias', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable(false)->change();
        });

        Schema::table('bakpias', function (Blueprint $table) {
            $table->dropColumn(['price_8', 'price_18']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * The per-variant prices cannot be reconstructed from a single `price`, so
     * they come back empty. The data is gone either way.
     */
    public function down(): void
    {
        Schema::table('bakpias', function (Blueprint $table) {
            $table->unsignedInteger('price_8')->nullable()->after('name');
            $table->unsignedInteger('price_18')->nullable()->after('price_8');
        });

        Schema::table('bakpias', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->change();
        });
    }
};
