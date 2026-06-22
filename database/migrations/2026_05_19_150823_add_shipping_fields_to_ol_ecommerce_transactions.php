<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ol_ecommerce_transactions', function (Blueprint $table) {
            $table->renameColumn('shipping_datetime', 'requested_shipping_datetime');
            $table->string('courier_service')->nullable()->after('courier_name');
            $table->string('tracking_number')->nullable()->unique()->after('courier_service');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('completed_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('ol_ecommerce_transactions', function (Blueprint $table) {
            $table->renameColumn('requested_shipping_datetime', 'shipping_datetime');
            $table->dropColumn(['courier_service', 'tracking_number', 'shipped_at', 'completed_at']);
        });
    }
};
