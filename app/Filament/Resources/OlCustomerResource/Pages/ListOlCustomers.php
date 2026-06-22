<?php

namespace App\Filament\Resources\OlCustomerResource\Pages;

use App\Filament\Resources\OlCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOlCustomers extends ListRecords
{
    protected static string $resource = OlCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
