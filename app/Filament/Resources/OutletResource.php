<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OutletResource\Pages;
use App\Filament\Resources\OutletResource\RelationManagers;
use App\Models\Outlet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OutletResource extends Resource
{
    protected static ?string $model = Outlet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Outlet cabang';

    protected static ?string $navigationGroup = 'Master';

    protected static ?string $modelLabel = 'Outlet cabang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('type')
                    ->options([
                        'OFFICIAL' => 'OFFICIAL',
                        'CABIN' => 'CABIN',
                        'DENTES' => 'DENTES',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\CheckboxList::make('operational_day')
                    ->label('Hari Operasional')
                    ->options([
                        'Senin'   => 'Senin',
                        'Selasa'  => 'Selasa',
                        'Rabu'    => 'Rabu',
                        'Kamis'   => 'Kamis',
                        'Jumat'   => 'Jumat',
                        'Sabtu'   => 'Sabtu',
                        'Minggu'  => 'Minggu',
                    ])
                    ->columns(7),
                Forms\Components\TextInput::make('operational_hour_start')
                    ->label('Jam Buka')
                    ->type('time')
                    ->afterStateHydrated(function ($component, $record) {
                        $hour = $record?->operational_hour;
                        if (is_array($hour)) {
                            $component->state($hour['start'] ?? null);
                        }
                    }),
                Forms\Components\TextInput::make('operational_hour_end')
                    ->label('Jam Tutup')
                    ->type('time')
                    ->afterStateHydrated(function ($component, $record) {
                        $hour = $record?->operational_hour;
                        if (is_array($hour)) {
                            $component->state($hour['end'] ?? null);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('operational_day')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('operational_hour')
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? (($state['start'] ?? '') . ' – ' . ($state['end'] ?? ''))
                        : $state),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BakpiaShipmentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOutlets::route('/'),
            'create' => Pages\CreateOutlet::route('/create'),
            'edit' => Pages\EditOutlet::route('/{record}/edit'),
        ];
    }
}
