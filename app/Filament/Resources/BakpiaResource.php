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
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('price_8')
                    ->numeric(),
                Forms\Components\TextInput::make('price_18')
                    ->numeric()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_8'),
                Tables\Columns\TextColumn::make('price_18')
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
