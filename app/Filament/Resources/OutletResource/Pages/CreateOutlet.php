<?php

namespace App\Filament\Resources\OutletResource\Pages;

use App\Filament\Resources\OutletResource;
use App\Services\OutletInitialStockService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOutlet extends CreateRecord
{
    protected static string $resource = OutletResource::class;

    protected ?int $initialStockAmount = null;

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Simpan')
            ->icon('heroicon-m-plus')
            ->keyBindings(['mod+s'])
            ->modalHeading('Buat pengiriman stok awal?')
            ->modalDescription('Setiap varian bakpia akan mendapatkan pengiriman stok awal ke outlet baru ini beserta pencatatan stok masuk (STOCK_IN).')
            ->modalSubmitActionLabel('Konfirmasi')
            ->form([
                Radio::make('initialize_stock')
                    ->label('Inisialisasi stok awal')
                    ->options([
                        'yes' => 'Ya, buat pengiriman stok awal ke outlet baru',
                        'no' => 'Tidak, hanya buat outlet',
                    ])
                    ->default('no')
                    ->live()
                    ->required(),
                TextInput::make('amount')
                    ->label('Jumlah per varian box')
                    ->helperText('Dibuat untuk box_8 dan box_18 pada setiap varian bakpia.')
                    ->numeric()
                    ->default(1000)
                    ->minValue(1)
                    ->required()
                    ->visible(fn (Get $get): bool => $get('initialize_stock') === 'yes'),
            ])
            ->action(function (array $data): void {
                $this->initialStockAmount = ($data['initialize_stock'] ?? 'no') === 'yes'
                    ? (int) ($data['amount'] ?? 1000)
                    : null;

                $this->create();
            });
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = Carbon::now();

        $year = $now->format('y'); // Use 'y' for two-digit year representation
        $month = $now->format('m'); // Use 'm' for zero-padded month number
        $day = $now->format('d'); // Use 'm' for zero-padded month number

        // Generate three random digits
        $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

        $transformId = 'outlet_'.$year.$month.$day.$randomDigits;
        $data['id_outlet'] = $transformId;

        $data['operational_hour'] = [
            'start' => $data['operational_hour_start'] ?? null,
            'end' => $data['operational_hour_end'] ?? null,
        ];
        unset($data['operational_hour_start'], $data['operational_hour_end']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialStockAmount === null) {
            return;
        }

        $recordsCreated = app(OutletInitialStockService::class)
            ->populate($this->record, $this->initialStockAmount);

        if ($recordsCreated === 0) {
            Notification::make()
                ->title('Tidak ada stok yang dibuat')
                ->body('Belum ada varian bakpia untuk dikirim ke outlet baru.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Stok awal dibuat')
            ->body("{$recordsCreated} catatan pengiriman dan stok masuk berhasil dibuat untuk outlet baru.")
            ->success()
            ->send();
    }
}
