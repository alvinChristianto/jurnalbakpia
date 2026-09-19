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
        Schema::create('transactions', function (Blueprint $table) {
            $table->string('id_transaction')->primary(); // TRX_YYMMDD###
            $table->foreignId('id_customer')->references('id')->on('customers');
            $table->foreignId('id_payment')->references('id')->on('payments');
            $table->foreignUuid('id_outlet')->references('id_outlet')->on('outlets');
            $table->json('transaction_details')->nullable();
            $table->unsignedInteger('total_price');
            $table->integer('discount')->nullable();
            $table->enum('status', ['PAID', 'REFUND'])->default('PAID');
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('id_payment');
            $table->index(['id_outlet', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
