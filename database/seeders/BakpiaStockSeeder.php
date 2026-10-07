<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BakpiaStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bakpia_stocks')->insert([
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_1',
                'id_transaction' => '',
                'status' => 'STOCK_IN',
                'amount' => '30',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'status' => 'STOCK_IN',
                'amount' => '15',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'status' => 'STOCK_IN',
                'amount' => '10',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_1',
                'id_transaction' => '',
                'status' => 'STOCK_IN',
                'amount' => '15',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_1',
                'id_transaction' => '',
                'status' => 'STOCK_SOLD',
                'amount' => '4',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'status' => 'STOCK_SOLD',
                'amount' => '8',

                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
