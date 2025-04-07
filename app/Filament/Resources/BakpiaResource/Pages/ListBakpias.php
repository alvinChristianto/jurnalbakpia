<?php

namespace App\Filament\Resources\BakpiaResource\Pages;

use App\Filament\Resources\BakpiaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBakpias extends ListRecords
{
    protected static string $resource = BakpiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
