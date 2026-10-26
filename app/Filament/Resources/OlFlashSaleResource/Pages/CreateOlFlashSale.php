<?php

namespace App\Filament\Resources\OlFlashSaleResource\Pages;

use App\Filament\Resources\OlFlashSaleResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateOlFlashSale extends CreateRecord
{
    protected static string $resource = OlFlashSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $startAt = Carbon::parse($data['start_at']);
        $data['end_at'] = $startAt->copy()->addMinutes((int) $data['duration_minutes']);
        $data['created_by'] = auth()->id();

        return $data;
    }
}
