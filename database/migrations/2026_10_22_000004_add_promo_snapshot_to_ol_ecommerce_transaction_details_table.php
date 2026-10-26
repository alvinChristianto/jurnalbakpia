<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Price snapshot per order line (spec §3.4) so later promo edits
     * never change old orders. Nullable snapshot columns keep pre-promo
     * rows valid; `price_per_item` (existing) remains the charged price.
     */
    public function up(): void
    {
        Schema::table('ol_ecommerce_transaction_details', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable();
            $table->string('discount_type')->nullable(); // percent|fixed|none
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->foreignUuid('flash_sale_item_id')->nullable()->constrained('ol_flash_sale_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ol_ecommerce_transaction_details', function (Blueprint $table) {
            $table->dropForeign(['flash_sale_item_id']);
            $table->dropColumn(['original_price', 'discount_type', 'discount_amount', 'flash_sale_item_id']);
        });
    }
};
