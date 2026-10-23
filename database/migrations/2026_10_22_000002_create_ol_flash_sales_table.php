<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flash sale master (spec bakpia-discount-flashsale.md §3.2).
     * No derived running/ended status — the active window is derived
     * from time (start_at <= now < end_at) so nothing depends on cron.
     * Times are stored in the app timezone (Asia/Jakarta, WIB).
     */
    public function up(): void
    {
        Schema::create('ol_flash_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('banner_path')->nullable();
            $table->string('banner_alt')->nullable();
            $table->dateTime('start_at');
            $table->integer('duration_minutes');
            $table->dateTime('end_at'); // computed on save: start_at + duration_minutes
            $table->string('status')->default('draft'); // draft|published|cancelled
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ol_flash_sales');
    }
};
