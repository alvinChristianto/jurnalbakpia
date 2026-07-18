<?php

namespace App\Filament\Resources\OlProductResource\Pages;

use App\Filament\Resources\OlProductResource;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateOlProduct extends CreateRecord
{
    protected static string $resource = OlProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();
        // dd($data);

        return $data;
    }
}
