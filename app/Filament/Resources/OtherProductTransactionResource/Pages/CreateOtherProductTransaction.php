<?php

namespace App\Filament\Resources\OtherProductTransactionResource\Pages;

use App\Filament\Resources\OtherProductTransactionResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOtherProductTransaction extends CreateRecord
{
    protected static string $resource = OtherProductTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();

        $year = $now->format('y'); // Use 'y' for two-digit year representation
        $month = $now->format('m'); // Use 'm' for zero-padded month number
        $day = $now->format('d'); // Use 'm' for zero-padded month number

        // Generate three random digits
        $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

        $transformId = "Other_" . $year . $month . $day . $randomDigits;
        $data['id_transaction'] = $transformId;

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
            $this->getCancelFormAction()
        ];
    }
}
