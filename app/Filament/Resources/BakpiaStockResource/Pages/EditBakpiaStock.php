<?php

namespace App\Filament\Resources\BakpiaStockResource\Pages;

use App\Filament\Resources\BakpiaStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBakpiaStock extends EditRecord
{
    protected static string $resource = BakpiaStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
