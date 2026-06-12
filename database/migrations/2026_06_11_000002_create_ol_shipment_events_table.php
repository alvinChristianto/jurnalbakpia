<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ol_shipment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->index();
            $table->string('event_type');
            $table->string('awb')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('reason')->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->foreign('invoice_number')
                ->references('invoice_number')
                ->on('ol_ecommerce_transactions')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_shipment_events');
    }
};
