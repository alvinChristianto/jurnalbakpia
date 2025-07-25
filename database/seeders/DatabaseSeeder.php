<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            PaymentSeeder::class,
            OutletSeeder::class,
            BakpiaSeeder::class,
            CustomerSeeder::class,
            BakpiaTransactionSeeder::class,
            BakpiaProductionSeeder::class,
            BakpiaStockSeeder::class,
            BakpiaShipmentSeeder::class
        ]);
    }
}
