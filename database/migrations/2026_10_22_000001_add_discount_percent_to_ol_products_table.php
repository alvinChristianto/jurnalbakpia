<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normal percent discount (0–100) for online products only.
     * Range is enforced app-side (Filament + pricing service) so the
     * schema stays portable across MySQL / PostgreSQL / SQLite.
     */
    public function up(): void
    {
        Schema::table('ol_products', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('ol_products', function (Blueprint $table) {
            $table->dropColumn(['discount_percent']);
        });
    }
};
