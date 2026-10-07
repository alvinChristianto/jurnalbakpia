<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the variant-free pricing columns so the backfill can run and be
     * verified before `price_8` / `price_18` are dropped in the next migration.
     */
    public function up(): void
    {
        Schema::table('bakpias', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('name');
            $table->text('description')->nullable()->after('price');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bakpias', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropColumn(['price', 'description']);
        });
    }
};
