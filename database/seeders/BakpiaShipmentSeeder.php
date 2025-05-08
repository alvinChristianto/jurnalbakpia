<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BakpiaShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        DB::table('bakpia_shipments')->insert([
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_1',
                'status' => 'SENT',
                'box_varian' => 'box_8',
                'amount' => '20',
                
                'shipment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_2',
                'status' => 'SENT',
                'box_varian' => 'box_8',
                'amount' => '15',
                
                'shipment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            [
                'id_bakpia' => '1',
                'id_outlet' => 'OUTLET_1',
                'status' => 'SENT',
                'box_varian' => 'box_18',
                'amount' => '10',
                
                'shipment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_2',
                'status' => 'SENT',
                'box_varian' => 'box_18',
                'amount' => '10',
                
                'shipment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id_bakpia' => '2',
                'id_outlet' => 'OUTLET_1',
                'status' => 'SENT',
                'box_varian' => 'box_18',
                'amount' => '15',
                
                'shipment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
