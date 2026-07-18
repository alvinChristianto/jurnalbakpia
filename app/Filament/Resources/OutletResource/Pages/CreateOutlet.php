<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateOutlet extends CreateRecord
{
    protected static string $resource = OutletResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();

        $year = $now->format('y'); // Use 'y' for two-digit year representation
        $month = $now->format('m'); // Use 'm' for zero-padded month number
        $day = $now->format('d'); // Use 'm' for zero-padded month number

        // Generate three random digits
        $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

        $transformId = 'outlet_'.$year.$month.$day.$randomDigits;
        $data['id_outlet'] = $transformId;

        $data['operational_hour'] = [
            'start' => $data['operational_hour_start'] ?? null,
            'end' => $data['operational_hour_end'] ?? null,
        ];
        unset($data['operational_hour_start'], $data['operational_hour_end']);

        return $data;
    }
}
