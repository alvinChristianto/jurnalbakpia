<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identity is the customer + email; login methods are optional. A Google-only
     * customer has no password, so password must be nullable. avatar_url stores the
     * profile picture (from Google or elsewhere).
     */
    public function up(): void
    {
        Schema::table('ol_customers', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('avatar_url')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('ol_customers', function (Blueprint $table) {
            $table->dropColumn('avatar_url');
            $table->string('password')->nullable(false)->change();
        });
    }
};
