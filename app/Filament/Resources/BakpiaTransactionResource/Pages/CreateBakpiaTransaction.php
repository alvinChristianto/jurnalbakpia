<?php

namespace App\Filament\Resources\BakpiaTransactionResource\Pages;

use App\Filament\Resources\BakpiaTransactionResource;
use App\Models\BakpiaStock;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateBakpiaTransaction extends CreateRecord
{
    protected static string $resource = BakpiaTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();

        $year = $now->format('y'); // Use 'y' for two-digit year representation
        $month = $now->format('m'); // Use 'm' for zero-padded month number
        $day = $now->format('d'); // Use 'm' for zero-padded month number

        // Generate three random digits
        $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

        $transformId = 'BAK_'.$year.$month.$day.$randomDigits;
        $data['id_transaction'] = $transformId;
        foreach ($data['transaction_detail'] as $item) {

            $totalStock = BakpiaStock::onHand($data['id_outlet'], (int) $item['id_bakpia']);
            $checkStockBakpia = $totalStock - $item['amount'];

            if ($checkStockBakpia >= 0) {
                BakpiaStock::create([
                    'id_outlet' => $data['id_outlet'],
                    'id_bakpia' => $item['id_bakpia'],
                    'id_transaction' => $data['id_transaction'],
                    'amount' => $item['amount'],
                    'status' => 'STOCK_SOLD',
                    'stock_record_date' => $now,
                ]);
            } else {
                // failed
            }
            //         DB::table('transactions')
            // ->join('categories', 'transactions.category_id', '=', 'categories.id')
            // ->where('categories.kind', '=', 1)
            // ->sum('transactions.amount')

        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Simpan Transaksi'),
            $this->getCancelFormAction(),
        ];
    }
}
