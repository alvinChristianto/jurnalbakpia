<?php

namespace App\Filament\Resources\BakpiaTransactionResource\Pages;

use App\Filament\Resources\BakpiaTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBakpiaTransactions extends ListRecords
{
    protected static string $resource = BakpiaTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
