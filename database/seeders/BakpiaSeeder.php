<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BakpiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bakpias')->insert([
            [
                'name' => 'Bakpia Keju',
                'price' => '20000',
                'description' => 'Bakpia isi keju',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Bakpia Abon',
                'price' => '20000',
                'description' => 'Bakpia isi abon',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Bakpia Kacang Almond',
                'price' => '20000',
                'description' => 'Bakpia isi kacang almond',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
