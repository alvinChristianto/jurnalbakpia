<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaShipmentResource\Pages;
use App\Filament\Resources\BakpiaShipmentResource\RelationManagers;
use App\Models\BakpiaShipment;
use Filament\Forms;
use Filament\Forms\Form;
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
                Forms\Components\TextInput::make('id_bakpia')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_outlet')
                    ->required()
                    ->maxLength(36),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('box_type')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('shipment_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_bakpia')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_outlet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('box_type'),
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
}
