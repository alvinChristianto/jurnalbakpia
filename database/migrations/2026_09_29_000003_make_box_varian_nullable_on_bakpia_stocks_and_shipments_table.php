<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables whose `box_varian` is relaxed from enum to a nullable string.
     *
     * @var list<string>
     */
    private array $tables = ['bakpia_stocks', 'bakpia_shipments'];

    /**
     * Run the migrations.
     *
     * `box_varian` is kept as a read-only audit trail for rows written before
     * the box-size variant was dropped from the product master. Existing
     * `box_8` / `box_18` values are preserved; new rows leave it NULL.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('box_varian', 20)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * The enum constraint can only be restored while every row still carries a
     * value, so it is left nullable when NULLs are present.
     */
    public function down(): void
    {
        $hasNulls = false;

        foreach ($this->tables as $table) {
            $hasNulls = $hasNulls || DB::table($table)->whereNull('box_varian')->exists();
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($hasNulls) {
                $column = $blueprint->enum('box_varian', ['box_8', 'box_18']);

                $hasNulls ? $column->nullable() : $column->nullable(false);

                $column->change();
            });
        }
    }
};
