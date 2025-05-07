<?php

namespace App\Filament\Resources\BakpiaStockResource\Pages;

use App\Filament\Resources\BakpiaStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBakpiaStocks extends ListRecords
{
    protected static string $resource = BakpiaStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
