<?php

namespace App\Filament\Pages;

use App\Models\Bakpia;
use App\Models\Outlet;
use App\Services\MassBakpiaShipmentService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MassBakpiaShipment extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Pengiriman Masal Bakpia';

    protected static ?string $navigationGroup = 'Master Bakpia ';

    protected static ?string $title = 'Pengiriman Masal Bakpia';

    protected static string $view = 'filament.pages.mass-bakpia-shipment';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('id_outlet')
                    ->label('outlet tujuan')
                    ->options(Outlet::pluck('name', 'id_outlet'))
                    ->required(),
                Repeater::make('items')
                    ->label('Daftar bakpia')
                    ->minItems(1)
                    ->reorderable()
                    ->schema([
                        Select::make('id_bakpia')
                            ->options(Bakpia::pluck('name', 'id'))
                            ->required(),
                        Select::make('box_varian')
                            ->options([
                                'box_8' => 'isi 8',
                                'box_18' => 'isi 18',
                            ])
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),
                Textarea::make('description'),
                DateTimePicker::make('shipment_date')
                    ->default(now())
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $outlet = Outlet::find($data['id_outlet']);

        $count = app(MassBakpiaShipmentService::class)->dispatch(
            $outlet,
            $data['items'],
            $data['description'] ?? null,
            Carbon::parse($data['shipment_date']),
        );

        Notification::make()
            ->title('Pengiriman berhasil')
            ->body("{$count} pengiriman bakpia berhasil dibuat.")
            ->success()
            ->send();

        $this->form->fill();
    }
}
