<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'name' => 'Alvin',
                'gender' => 'L',
                'phone_number' => '08744444',
                'email' => 'test@gmail.com',
                'address' => 'address',
                'city' => 'Sleman',
                'province' => 'Yogyakarta',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Anto',
                'gender' => 'L',
                'phone_number' => '08744444',
                'email' => 'test1@gmail.com',
                'address' => 'address',
                'city' => 'Sleman',
                'province' => 'Yogyakarta',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'aaaa',
                'gender' => 'P',
                'phone_number' => '08744444',
                'email' => 'test4@gmail.com',
                'address' => 'address',
                'city' => 'Sleman',
                'province' => 'Yogyakarta',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'bbbbb',
                'gender' => 'L',
                'phone_number' => '08744444',
                'email' => 'test3@gmail.com',
                'address' => 'address',
                'city' => 'Sleman',
                'province' => 'Yogyakarta',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ]);
    }
}
