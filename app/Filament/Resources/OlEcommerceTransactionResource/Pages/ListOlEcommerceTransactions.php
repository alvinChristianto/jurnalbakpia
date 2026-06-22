<?php

namespace App\Filament\Resources\OlEcommerceTransactionResource\Pages;

use App\Filament\Resources\OlEcommerceTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOlEcommerceTransactions extends ListRecords
{
    protected static string $resource = OlEcommerceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
