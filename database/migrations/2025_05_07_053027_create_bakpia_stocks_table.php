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
        Schema::create('bakpia_stocks', function (Blueprint $table) {
            $table->id();
            
            $table->foreignUuid('id_outlet')->references('id_outlet')->on('outlets'); 
            $table->foreignId('id_bakpia')->references('id')->on('bakpias'); 

            $table->string('id_transaction')->nullable();
            $table->enum('box_varian', ['box_8', 'box_18']);
            $table->enum('status', ['STOCK_IN', 'STOCK_SOLD', 'RETURNED']);
            $table->integer('amount')->nullable();
            $table->dateTime('stock_record_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakpia_stocks');
    }
};
