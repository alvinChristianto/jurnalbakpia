<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutlet extends EditRecord
{
    protected static string $resource = OutletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['operational_hour'] = [
            'start' => $data['operational_hour_start'] ?? null,
            'end'   => $data['operational_hour_end'] ?? null,
        ];
        unset($data['operational_hour_start'], $data['operational_hour_end']);

        return $data;
    }
}
