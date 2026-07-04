<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move existing Google identities from the bare ol_customers.google_id column
     * into the provider-agnostic ol_customer_social_accounts table, then drop the
     * column. Runs last so no code path still reads google_id.
     *
     * Deploy order in production (Octane caches model code): deploy the new code,
     * `php artisan octane:reload`, THEN run this migration.
     */
    public function up(): void
    {
        DB::table('ol_customers')
            ->whereNotNull('google_id')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('ol_customer_social_accounts')->updateOrInsert(
                    ['provider' => 'google', 'provider_user_id' => $row->google_id],
                    [
                        'customer_id' => $row->id,
                        'provider_email' => $row->email,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            });

        Schema::table('ol_customers', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('ol_customers', function (Blueprint $table) {
            $table->string('google_id')->nullable();
        });

        // Restore google_id from linked google social accounts (best-effort).
        DB::table('ol_customer_social_accounts')
            ->where('provider', 'google')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('ol_customers')
                    ->where('id', $row->customer_id)
                    ->update(['google_id' => $row->provider_user_id]);
            });
    }
};
