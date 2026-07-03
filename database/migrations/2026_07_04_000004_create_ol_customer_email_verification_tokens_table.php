<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Email-verification tokens for storefront customers. Token is stored hashed;
     * created_at drives expiry (24 h).
     */
    public function up(): void
    {
        Schema::create('ol_customer_email_verification_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_customer_email_verification_tokens');
    }
};
