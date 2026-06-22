<?php

namespace App\Filament\Resources\OlEcommerceTransactionResource\Pages;

use App\Filament\Resources\OlEcommerceTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOlEcommerceTransaction extends EditRecord
{
    protected static string $resource = OlEcommerceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
