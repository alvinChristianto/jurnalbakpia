<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();

        $year = $now->format('y');
        $month = $now->format('m');
        $day = $now->format('d');

        $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

        $data['id_transaction'] = 'TRX_'.$year.$month.$day.$randomDigits;

        $result = Transaction::createStockSoldRecords(
            idOutlet: $data['id_outlet'],
            idTransaction: $data['id_transaction'],
            details: $data['transaction_details'],
            date: $now,
        );

        foreach ($result['insufficient'] as $item) {
            Notification::make()
                ->title('No Bakpia Stock left')
                ->body('Stock tidak mencukupi untuk item `'.($item['product_name'] ?? $item['product_id']).'`. Transaksi tetap disimpan.')
                ->warning()
                ->send();
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
