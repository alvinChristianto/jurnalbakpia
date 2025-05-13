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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\TextInput::make('price_8')
                    ->label('harga box isi 8'),
                    // ->money('idr'),
                Forms\Components\TextInput::make('price_18')
                    ->label('harga box isi 18')
                    // ->money('idr')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Varian Bakpia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_8')
                    ->label('harga box isi 8')
                    ->money('idr'),
                Tables\Columns\TextColumn::make('price_18')
                    ->label('harga box isi 18')
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
