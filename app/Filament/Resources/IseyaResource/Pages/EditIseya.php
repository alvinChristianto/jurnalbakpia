<?php

namespace App\Filament\Resources\IseyaResource\Pages;

use App\Filament\Resources\IseyaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIseya extends EditRecord
{
    protected static string $resource = IseyaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
