<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaShipmentResource\Pages;
use App\Filament\Resources\BakpiaShipmentResource\RelationManagers;
use App\Models\Bakpia;
use App\Models\BakpiaShipment;
use App\Models\Outlet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BakpiaShipmentResource extends Resource
{
    protected static ?string $model = BakpiaShipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_bakpia')
                    ->options(function (Get $get) {
                        return Bakpia::pluck('name', 'id');
                    })
                    ->required(),
                Forms\Components\Select::make('id_outlet')
                    ->label('outlet tujuan')
                    ->options(function (Get $get) {
                        return Outlet::pluck('name', 'id_outlet');
                    })

                    ->required(),
                Forms\Components\Select::make('box_varian')
                    ->options([
                        'box_8' => 'isi 8',
                        'box_18' => 'isi 18',
                    ]),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description'),
                Forms\Components\DateTimePicker::make('shipment_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bakpia.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->label('outlet tujuan'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('box_varian')
                    ->label('jenis box'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipment_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('status pengiriman')
                    ->options([
                        'SENT' => 'SENT',
                        'RETURNED' => 'RETURNED',
                    ]),
                Tables\Filters\SelectFilter::make('box_varian')
                    ->label('jenis box')
                    ->options([
                        'box_8' => 'box_8',
                        'box_18' => 'box_18',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBakpiaShipments::route('/'),
            'create' => Pages\CreateBakpiaShipment::route('/create'),
            'edit' => Pages\EditBakpiaShipment::route('/{record}/edit'),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return '/'; // Or route('filament.pages.dashboard') if you want to go to the dashboard
    }
}
