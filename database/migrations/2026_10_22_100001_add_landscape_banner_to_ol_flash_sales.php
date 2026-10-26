<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flash sales get a second, landscape banner crop. The square banner
     * (`banner_path`) is shown in the storefront's welcome modal; the
     * landscape one (`banner_rect_path`) fits the wide homepage section.
     */
    public function up(): void
    {
        Schema::table('ol_flash_sales', function (Blueprint $table) {
            $table->string('banner_rect_path')->nullable()->after('banner_path');
            $table->string('banner_rect_alt')->nullable()->after('banner_alt');
        });
    }

    public function down(): void
    {
        Schema::table('ol_flash_sales', function (Blueprint $table) {
            $table->dropColumn(['banner_rect_path', 'banner_rect_alt']);
        });
    }
};
