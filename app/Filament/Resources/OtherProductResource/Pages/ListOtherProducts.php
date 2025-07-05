<?php

namespace App\Filament\Resources\OtherProductResource\Pages;

use App\Filament\Resources\OtherProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherProducts extends ListRecords
{
    protected static string $resource = OtherProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
