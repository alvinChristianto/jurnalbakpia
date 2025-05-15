<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaProductionResource\Pages;
use App\Filament\Resources\BakpiaProductionResource\RelationManagers;
use App\Models\Bakpia;
use App\Models\BakpiaProduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BakpiaProductionResource extends Resource
{
    protected static ?string $model = BakpiaProduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Produksi Bakpia ';
    protected static ?string $navigationGroup = 'Master Bakpia ';

    protected static ?string $modelLabel = 'Produksi Bakpia';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_bakpia')
                    ->options(function (Get $get) {
                        return Bakpia::pluck('name', 'id');
                    })
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description'),
                Forms\Components\DateTimePicker::make('production_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('bakpia.name'),
                Tables\Columns\TextColumn::make('production_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('production_status')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
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
            'index' => Pages\ListBakpiaProductions::route('/'),
            'create' => Pages\CreateBakpiaProduction::route('/create'),
            'edit' => Pages\EditBakpiaProduction::route('/{record}/edit'),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return '/'; // Or route('filament.pages.dashboard') if you want to go to the dashboard
    }
}
