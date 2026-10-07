<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaResource\Pages;
use App\Filament\Resources\BakpiaResource\RelationManagers;
use App\Models\Bakpia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BakpiaResource extends Resource
{
    protected static ?string $model = Bakpia::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Master';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Varian Bakpia')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price')
                    ->label('harga jual')
                    ->prefix('Rp')
                    ->integer()
                    ->minValue(1)
                    ->required(),
                Forms\Components\Textarea::make('description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Varian Bakpia')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('harga jual')
                    ->money('idr'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\BakpiaProductionRelationManager::class,
            RelationManagers\BakpiaShipmentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBakpias::route('/'),
            'create' => Pages\CreateBakpia::route('/create'),
            'edit' => Pages\EditBakpia::route('/{record}/edit'),
        ];
    }
}
