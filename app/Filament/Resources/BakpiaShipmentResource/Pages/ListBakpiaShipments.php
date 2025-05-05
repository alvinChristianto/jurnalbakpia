<?php

namespace App\Filament\Resources\BakpiaShipmentResource\Pages;

use App\Filament\Resources\BakpiaShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBakpiaShipments extends ListRecords
{
    protected static string $resource = BakpiaShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
