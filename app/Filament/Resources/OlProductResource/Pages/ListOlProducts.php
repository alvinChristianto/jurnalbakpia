<?php

namespace App\Filament\Resources\OlProductResource\Pages;

use App\Filament\Resources\OlProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOlProducts extends ListRecords
{
    protected static string $resource = OlProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
