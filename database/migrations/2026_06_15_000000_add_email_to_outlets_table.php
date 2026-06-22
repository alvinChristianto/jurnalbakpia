<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone_number');
            $table->string('operational_day')->nullable()->after('email');
            $table->string('operational_hour')->nullable()->after('operational_day');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['email', 'operational_day', 'operational_hour']);
        });
    }
};
