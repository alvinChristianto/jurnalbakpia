<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BakpiaTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/seeder_data/BakpiaTransactionDataSeeder.json'));
        $transactions = json_decode($json);

        foreach ($transactions as $transaction) {
            DB::table('bakpia_transactions')->insert([
                'id_transaction' => $transaction->id_transaction,
                'id_Customer' => $transaction->id_Customer,
                'id_payment' => $transaction->id_payment,
                'id_outlet' => $transaction->id_outlet,
                'transaction_detail' => $transaction->transaction_detail,
                'total_price' => $transaction->total_price,
                'discount' => $transaction->discount,
                'status' => $transaction->status,
                'created_at' => Carbon::parse($transaction->created_at),
                'updated_at' => Carbon::parse($transaction->updated_at),
            ]);
        }
    }
}
