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
        Schema::create('bakpias', function (Blueprint $table) {
            $table->id();
            $table->string('name', length: 100);
            $table->unsignedInteger('price_8');
            $table->unsignedInteger('price_18');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakpias');
    }
};
