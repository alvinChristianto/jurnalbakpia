<?php

namespace App\Filament\Resources\OlFlashSaleResource\Pages;

use App\Enums\FlashSaleStatus;
use App\Filament\Resources\OlFlashSaleResource;
use App\Models\OlFlashSale;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditOlFlashSale extends EditRecord
{
    protected static string $resource = OlFlashSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-m-arrow-up-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (OlFlashSale $record): bool => $record->status === FlashSaleStatus::DRAFT)
                ->disabled(fn (OlFlashSale $record): bool => $record->items()->count() === 0)
                ->action(fn (OlFlashSale $record) => $record->update(['status' => FlashSaleStatus::PUBLISHED->value])),

            Action::make('cancelSale')
                ->label('Cancel')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Membatalkan event akan langsung menghapus harga flash sale dari semua produk.')
                ->visible(fn (OlFlashSale $record): bool => $record->status === FlashSaleStatus::PUBLISHED)
                ->action(fn (OlFlashSale $record) => $record->update(['status' => FlashSaleStatus::CANCELLED->value])),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        // A running event keeps its start time; admins may only extend or
        // shorten the duration, so end_at is recomputed from the original start.
        $startAt = $record->status === FlashSaleStatus::PUBLISHED
            ? $record->start_at
            : Carbon::parse($data['start_at']);

        $data['end_at'] = $startAt->copy()->addMinutes((int) $data['duration_minutes']);
        $data['start_at'] = $startAt->format('Y-m-d H:i:s');

        return $data;
    }
}
