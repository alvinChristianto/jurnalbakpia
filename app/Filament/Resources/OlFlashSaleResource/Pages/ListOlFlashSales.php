<?php

namespace App\Filament\Resources\OlFlashSaleResource\Pages;

use App\Filament\Resources\OlFlashSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOlFlashSales extends ListRecords
{
    protected static string $resource = OlFlashSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
