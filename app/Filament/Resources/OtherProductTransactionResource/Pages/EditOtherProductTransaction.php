<?php

namespace App\Filament\Resources\OtherProductTransactionResource\Pages;

use App\Filament\Resources\OtherProductTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherProductTransaction extends EditRecord
{
    protected static string $resource = OtherProductTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
