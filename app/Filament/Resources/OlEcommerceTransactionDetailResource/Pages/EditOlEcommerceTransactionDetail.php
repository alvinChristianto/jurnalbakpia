<?php

namespace App\Filament\Resources\OlEcommerceTransactionDetailResource\Pages;

use App\Filament\Resources\OlEcommerceTransactionDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOlEcommerceTransactionDetail extends EditRecord
{
    protected static string $resource = OlEcommerceTransactionDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
