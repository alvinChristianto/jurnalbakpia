<?php

namespace App\Filament\Resources\IseyaResource\Pages;

use App\Filament\Resources\IseyaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIseya extends CreateRecord
{
    protected static string $resource = IseyaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
