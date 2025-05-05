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
        Schema::create('bakpia_productions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_bakpia')->references('id')->on('bakpias'); 

            $table->enum('production_status', ['SUCCESS', 'FAIL']);
            $table->integer('amount');
            $table->text('description')->nullable();
            $table->dateTime('production_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bakpia_productions');
    }
};
