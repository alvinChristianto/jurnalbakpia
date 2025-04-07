<?php

namespace App\Filament\Resources\BakpiaTransactionResource\Pages;

use App\Filament\Resources\BakpiaTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBakpiaTransaction extends EditRecord
{
    protected static string $resource = BakpiaTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
