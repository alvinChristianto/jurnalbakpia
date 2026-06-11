<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ol_shipment_events', function (Blueprint $table) {
            $table->id();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_shipment_events');
    }
};
