<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'bakpia_transactions' => [
            ['created_at'],
            ['status'],
            ['id_payment'],
            ['id_outlet', 'created_at'],
        ],
        'other_product_transactions' => [
            ['created_at'],
            ['status'],
            ['id_payment'],
            ['id_outlet', 'created_at'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $columns) {
                if (Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($columns): void {
                    $table->index($columns);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_reverse($indexes) as $columns) {
                if (! Schema::hasIndex($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($columns): void {
                    $table->dropIndex($columns);
                });
            }
        }
    }
};
