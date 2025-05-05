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
        Schema::create('bakpia_shipments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('id_bakpia')->references('id')->on('bakpias'); 
            $table->foreignUuid('id_outlet')->references('id_outlet')->on('outlets'); 

            $table->enum('status', ['SENT', 'RETURNED']);
            $table->enum('box_type', ['box_8', 'box_18']);
            $table->integer('amount');
            $table->text('description')->nullable();
            $table->dateTime('shipment_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakpia_shipments');
    }
};
