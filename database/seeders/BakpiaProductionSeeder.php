<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BakpiaProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bakpia_productions')->insert([
            [
                'id_bakpia' => '1',
                'production_status' => 'SUCCESS',
                'amount' => '230',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '1',
                'production_status' => 'SUCCESS',
                'amount' => '340',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            
            [
                'id_bakpia' => '2',
                'production_status' => 'SUCCESS',
                'amount' => '250',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '2',
                'production_status' => 'SUCCESS',
                'amount' => '100',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            
            [
                'id_bakpia' => '3',
                'production_status' => 'SUCCESS',
                'amount' => '290',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '3',
                'production_status' => 'SUCCESS',
                'amount' => '1100',
                'production_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
