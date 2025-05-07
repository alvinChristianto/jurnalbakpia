<?php

namespace App\Filament\Resources\BakpiaShipmentResource\Pages;

use App\Filament\Resources\BakpiaShipmentResource;
use App\Models\BakpiaStock;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBakpiaShipment extends CreateRecord
{
    protected static string $resource = BakpiaShipmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();
        
        BakpiaStock::create([
            'id_outlet' => $data['id_outlet'],
            'id_bakpia' => $data["id_bakpia"],
            'id_transaction' => '',
            'box_varian' =>  $data["box_varian"],
            'amount' => $data["amount"],
            'status' => 'STOCK_IN',
            'stock_record_date' => $now,
        ]);

        return $data;
    }
}
