<?php

namespace App\Filament\Resources\BakpiaResource\Pages;

use App\Filament\Resources\BakpiaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBakpia extends EditRecord
{
    protected static string $resource = BakpiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
