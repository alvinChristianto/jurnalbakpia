<?php

namespace App\Filament\Resources\BakpiaShipmentResource\Pages;

use App\Filament\Resources\BakpiaShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBakpiaShipment extends EditRecord
{
    protected static string $resource = BakpiaShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
