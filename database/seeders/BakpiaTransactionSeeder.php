<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BakpiaTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bakpia_transactions')->insert([
            [
                'id_transaction' => 'BAK_20250101001',
                'id_Customer' => '1',
                'id_payment' => '1',
                'id_outlet' => 'OUTLET_1',
                'transaction_detail' => '[{"amount": "2", "id_bakpia": "1", "price_per": 40000, "box_varian": "8"}]',
                'total_price' => '40000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'id_transaction' => 'BAK_20250101002',
                'id_Customer' => '2',
                'id_payment' => '1',
                'id_outlet' => 'OUTLET_1',
                'transaction_detail' => '[{"amount": "3", "id_bakpia": "1", "price_per": 60000, "box_varian": "8"}]',
                'total_price' => '60000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'id_transaction' => 'BAK_20250101003',
                'id_Customer' => '1',
                'id_payment' => '1',
                'id_outlet' => 'OUTLET_1',
                'transaction_detail' => '[{"amount": "3", "id_bakpia": "1", "price_per": 60000, "box_varian": "8"}]',
                'total_price' => '60000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'id_transaction' => 'BAK_20250101004',
                'id_Customer' => '2',
                'id_payment' => '1',
                'id_outlet' => 'OUTLET_1',
                'transaction_detail' => '[{"amount": "30", "id_bakpia": "3", "price_per": 600000, "box_varian": "8"}]',
                'total_price' => '600000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_transaction' => 'BAK_20250101005',
                'id_Customer' => '2',
                'id_payment' => '2',
                'id_outlet' => 'OUTLET_2',
                'transaction_detail' => '[{"amount": "20", "id_bakpia": "2", "price_per": 400000, "box_varian": "8"}]',
                'total_price' => '400000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_transaction' => 'BAK_20250101006',
                'id_Customer' => '2',
                'id_payment' => '3',
                'id_outlet' => 'OUTLET_3',
                'transaction_detail' => '[{"amount": "20", "id_bakpia": "2", "price_per": 400000, "box_varian": "8"}]',
                'total_price' => '400000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_transaction' => 'BAK_20250101007',
                'id_Customer' => '2',
                'id_payment' => '2',
                'id_outlet' => 'OUTLET_1',
                'transaction_detail' => '[{"amount": "10", "id_bakpia": "2", "price_per": 200000, "box_varian": "8"}]',
                'total_price' => '200000',
                'discount' => '0',
                'status' => 'PAID',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
