<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provider-agnostic linked login methods for a storefront identity.
     * One row per (provider, provider_user_id); each provider linked once per customer.
     */
    public function up(): void
    {
        Schema::create('ol_customer_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('customer_id')->constrained('ol_customers')->cascadeOnDelete();
            $table->string('provider');            // e.g. 'google'
            $table->string('provider_user_id');    // stable id from the provider
            $table->string('provider_email')->nullable();
            $table->string('provider_avatar')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']); // 1 provider account -> 1 customer
            $table->unique(['customer_id', 'provider']);       // each provider linked once
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_customer_social_accounts');
    }
};
