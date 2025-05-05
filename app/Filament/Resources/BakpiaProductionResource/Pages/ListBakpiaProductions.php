<?php

namespace App\Filament\Resources\BakpiaProductionResource\Pages;

use App\Filament\Resources\BakpiaProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBakpiaProductions extends ListRecords
{
    protected static string $resource = BakpiaProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
