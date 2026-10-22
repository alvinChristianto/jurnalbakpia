<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flash sale items (spec §3.3) — one row is the atomic quota unit.
     * Discount is percent (1–100) or fixed rupiah. The unique key blocks
     * the same product twice inside one event; overlap validation (R4)
     * is enforced app-side at save time.
     */
    public function up(): void
    {
        Schema::create('ol_flash_sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('flash_sale_id')->constrained('ol_flash_sales')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('ol_products')->cascadeOnDelete();
            $table->string('discount_type'); // percent|fixed
            $table->unsignedInteger('discount_value'); // percent 1–100, or rupiah
            $table->unsignedInteger('quota')->nullable(); // NULL = unlimited
            $table->unsignedInteger('sold_count')->default(0);
            $table->timestamps();

            $table->unique(['flash_sale_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_flash_sale_items');
    }
};
