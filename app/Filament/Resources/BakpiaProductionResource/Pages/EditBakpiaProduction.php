<?php

namespace App\Filament\Resources\BakpiaProductionResource\Pages;

use App\Filament\Resources\BakpiaProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBakpiaProduction extends EditRecord
{
    protected static string $resource = BakpiaProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
