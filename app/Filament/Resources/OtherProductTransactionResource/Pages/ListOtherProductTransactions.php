<?php

namespace App\Filament\Resources\OtherProductTransactionResource\Pages;

use App\Filament\Resources\OtherProductTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherProductTransactions extends ListRecords
{
    protected static string $resource = OtherProductTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
