<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('outlets')->insert([
            [
                'id_outlet' => 'OUTLET_1',
                'name' => 'The Cabin Hotel Sutomo',
                'phone_number' => '089898989',
                'address' => ' address The Cabin Hotel Sutomo',
                'type' => 'CABIN',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_outlet' => 'OUTLET_2',
                'name' => 'The Cabin Hotel Wirobrajan',
                'phone_number' => '089898989',
                'address' => ' address The Cabin Hotel Sutomo',
                'type' => 'CABIN',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_outlet' => 'OUTLET_3',
                'name' => 'RUMAH BAKPIA',
                'phone_number' => '089898989',
                'address' => ' address The Cabin Hotel Sutomo',
                'type' => 'OFFICIAL',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
