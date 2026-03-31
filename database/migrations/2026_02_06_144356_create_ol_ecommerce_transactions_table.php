<?php

use App\Enums\TransactionStatus;
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
        Schema::create('ol_ecommerce_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('ol_customer_id')->constrained('ol_customers');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 12, 2);
            $table->decimal('service_fee', 12, 2);
            $table->decimal('grand_total', 12, 2);

            // Status using the Enum values
            $table->string('status')->default(TransactionStatus::PENDING->value);

            // Track delivery time (Image 5 context)
            $table->dateTime('shipping_datetime')->nullable();

            $table->json('shipping_address_snapshot');
            $table->string('courier_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('invoice_number_backend')->nullable();
            $table->string('payment_token_midtrans')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // ... (transaction_details remains the same)
        // We also need a Detail table to store the Bakpia items bought
        Schema::create('ol_ecommerce_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ol_ecommerce_transactions')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name_snapshot');
            $table->integer('quantity');
            $table->decimal('price_per_item', 12, 2);
            $table->string('note')->nullable(); // From "Tambahkan catatan" in Image 0
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ol_ecommerce_transactions');
    }
};
