<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtherProductResource\Pages;
use App\Filament\Resources\OtherProductResource\RelationManagers;
use App\Models\OtherProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OtherProductResource extends Resource
{
    protected static ?string $model = OtherProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Data Produk Lain ';
    protected static ?string $navigationGroup = 'Produk Lain ';

    protected static ?string $modelLabel = 'Data Produk Lain';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama produk')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price')
                    ->label('harga jual')
                    ->prefix('Rp'),
                Forms\Components\Textarea::make('description'),
                // ->money('idr'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama produk')
                    ->searchable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtherProducts::route('/'),
            'create' => Pages\CreateOtherProduct::route('/create'),
            'edit' => Pages\EditOtherProduct::route('/{record}/edit'),
        ];
    }
}
