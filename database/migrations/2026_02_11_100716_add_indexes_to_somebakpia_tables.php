<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- Customers Table ---
        Schema::table('customers', function (Blueprint $table) {
            // Indexing name and phone for Filament Global Search
            $table->index('name');
            $table->index('phone_number');
        });

        // --- Bakpia Transactions ---
        Schema::table('bakpia_transactions', function (Blueprint $table) {
            // Crucial for Filament Filters (Filter by Status or Outlet)
            $table->index('status');
            $table->index('created_at'); // Great for "Date Range" filters

            // Composite index for "Orders by this customer with this status"
            $table->index(['id_customer', 'status']);
            $table->index('id_payment');
        });

        // --- Bakpia Stocks ---
        Schema::table('bakpia_stocks', function (Blueprint $table) {
            // You will likely query stock by outlet AND specific bakpia flavor often
            $table->index(['id_outlet', 'id_bakpia']);

            // Indexing the date for stock history reports
            $table->index('stock_record_date');

            // If you search for stock records linked to a specific transaction
            $table->index('id_transaction');
            $table->index('box_varian');
            $table->index('status');
        });

        // --- Bakpia Shipments ---
        Schema::table('bakpia_shipments', function (Blueprint $table) {
            $table->index('status');
            $table->index('shipment_date');
            $table->index('box_varian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['phone_number']);
        });

        Schema::table('bakpia_transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['id_customer', 'status']);
            $table->dropIndex(['id_payment']); // Fix: added to down
        });

        Schema::table('bakpia_stocks', function (Blueprint $table) {
            $table->dropIndex(['id_outlet', 'id_bakpia']);
            $table->dropIndex(['stock_record_date']);
            $table->dropIndex(['id_transaction']);
            $table->dropIndex(['box_varian']); // Fix: added to down
            $table->dropIndex(['status']);     // Fix: added to down
        });

        Schema::table('bakpia_shipments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['shipment_date']);
            $table->dropIndex(['box_varian']); // Fix: added to down
        });
    }
};
