<?php

namespace App\Filament\Resources\OlProductResource\Pages;

use App\Filament\Resources\OlProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOlProduct extends EditRecord
{
    protected static string $resource = OlProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
