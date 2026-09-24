<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use App\Filament\Resources\OutletResource\Widgets\OutletStockOverview;
use Filament\Resources\Pages\ViewRecord;

class ViewOutlet extends ViewRecord
{
    protected static string $resource = OutletResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            OutletStockOverview::class,
        ];
    }
}
