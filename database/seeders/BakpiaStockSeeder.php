<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'box_varian' => 'box_8',
                'status' => 'STOCK_IN',
                'amount' => '20',
                
                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'box_varian' => 'box_8',
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
                'box_varian' => 'box_18',
                'status' => 'STOCK_IN',
                'amount' => '10',
                
                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'box_varian' => 'box_18',
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
                'box_varian' => 'box_18',
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
                'box_varian' => 'box_8',
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
                'box_varian' => 'box_18',
                'status' => 'STOCK_SOLD',
                'amount' => '5',
                
                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_2',
                'id_transaction' => '',
                'box_varian' => 'box_18',
                'status' => 'STOCK_SOLD',
                'amount' => '3',
                
                'stock_record_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
