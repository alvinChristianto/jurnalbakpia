<?php

namespace App\Filament\Resources\BakpiaTransactionResource\Pages;

use App\Filament\Resources\BakpiaTransactionResource;
use Carbon\Carbon;
use Filament\Actions;
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

        $transformId = "BAK_" . $day . $month . $year . $randomDigits;
        $data['id'] = $transformId;

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
