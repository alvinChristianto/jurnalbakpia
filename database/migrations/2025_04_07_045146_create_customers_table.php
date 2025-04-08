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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', length: 100);
            $table->enum('gender', ['-', 'L', 'P']);
            $table->string('phone_number', length: 20)->nullable();
            $table->string('email', length: 100)->nullable();
            $table->string('address', length: 100)->nullable();
            $table->string('city', length: 100)->nullable();
            $table->string('province', length: 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
