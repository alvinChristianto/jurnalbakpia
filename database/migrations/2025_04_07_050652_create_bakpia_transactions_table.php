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
        Schema::create('bakpia_transactions', function (Blueprint $table) {
            $table->string('id_transaction')->primary();
            $table->foreignId('id_bakpia')->references('id')->on('bakpias');
            $table->foreignUuid('id_customer')->references('id_customer')->on('customers');
            $table->foreignId('id_payment')->references('id')->on('payments');
            $table->foreignUuid('id_outlet')->references('id_outlet')->on('outlets'); 
            $table->json('transaction_detail')->nullable();     //tipe, amount
            $table->unsignedInteger('total_price');
            $table->integer('discount');
            $table->enum('status', ['PAID', 'REFUND']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakpia_transactions');
    }
};
