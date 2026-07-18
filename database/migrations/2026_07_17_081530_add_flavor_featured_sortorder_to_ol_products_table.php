<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ol_products', function (Blueprint $table) {
            $table->string('flavor', 100)->nullable()->after('category');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->unsignedInteger('sort_order')->default(0)->after('is_featured');

            $table->index(['flavor', 'status']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('ol_products', function (Blueprint $table) {
            $table->dropIndex(['flavor', 'status']);
            $table->dropIndex(['sort_order']);
            $table->dropColumn(['flavor', 'is_featured', 'sort_order']);
        });
    }
};
