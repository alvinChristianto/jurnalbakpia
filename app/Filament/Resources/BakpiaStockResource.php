<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaStockResource\Pages;
use App\Filament\Resources\BakpiaStockResource\RelationManagers;
use App\Models\BakpiaStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BakpiaStockResource extends Resource
{
    protected static ?string $model = BakpiaStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Stok Bakpia';
    protected static ?string $navigationGroup = 'Master Bakpia ';

    protected static ?string $modelLabel = 'Stok Bakpia';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'STOCK_IN' => 'info',
                        'STOCK_SOLD' => 'success',
                        'RETURNED' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('bakpia.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->label('outlet tujuan'),
                Tables\Columns\TextColumn::make('box_varian'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_record_date')
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
                    ->label('status stock')
                    ->options([
                        'STOCK_IN' => 'STOCK_IN',
                        'STOCK_SOLD' => 'STOCK_SOLD',
                        'RETURNED' => 'RETURNED',
                    ]),
                Tables\Filters\SelectFilter::make('box_varian')
                    ->label('jenis box')
                    ->options([
                        'box_8' => 'box_8',
                        'box_18' => 'box_18',
                    ]),
                Tables\Filters\SelectFilter::make('id_outlet')
                    ->label('Outlet')
                    ->relationship('outlet', 'name'),
                Tables\Filters\SelectFilter::make('id_bakpia')
                    ->label('Jenis Bakpia')
                    ->relationship('bakpia', 'name')
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBakpiaStocks::route('/'),
            'create' => Pages\CreateBakpiaStock::route('/create'),
            'edit' => Pages\EditBakpiaStock::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $adminOutlet = [1, 0];
        $user = auth()->user();

        $outlets = $user->outlets;
        $idUser = $user->id;
        // dd($idUser);

        if (!in_array($idUser, $adminOutlet)) {
            return parent::getEloquentQuery()->wherein('id_outlet', $outlets);
        }
        return parent::getEloquentQuery();
    }
}
