<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaStockResource\Pages;
use App\Models\BakpiaStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->schema([
                Forms\Components\Section::make('Informasi Outlet & Produk')
                    ->schema([
                        Forms\Components\Select::make('id_outlet')
                            ->label('Outlet')
                            ->relationship('outlet', 'name')
                            ->required(),
                        Forms\Components\Select::make('id_bakpia')
                            ->label('Jenis Bakpia')
                            ->relationship('bakpia', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('id_transaction')
                            ->label('ID Transaksi')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Stok')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'STOCK_IN' => 'STOCK_IN',
                                'STOCK_SOLD' => 'STOCK_SOLD',
                                'RETURNED' => 'RETURNED',
                            ])
                            ->required(),
                        Forms\Components\Select::make('box_varian')
                            ->label('Varian Box')
                            ->options([
                                'box_8' => 'Box 8',
                                'box_18' => 'Box 18',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('stock_record_date')
                            ->label('Tanggal Stok')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Outlet & Produk')
                    ->schema([
                        Infolists\Components\TextEntry::make('outlet.name')
                            ->label('Outlet'),
                        Infolists\Components\TextEntry::make('bakpia.name')
                            ->label('Jenis Bakpia'),
                        Infolists\Components\TextEntry::make('id_transaction')
                            ->label('ID Transaksi')
                            ->placeholder('—'),
                    ])->columns(2),

                Infolists\Components\Section::make('Detail Stok')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'STOCK_IN' => 'info',
                                'STOCK_SOLD' => 'success',
                                'RETURNED' => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('box_varian')
                            ->label('Varian Box'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Jumlah')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('stock_record_date')
                            ->label('Tanggal Stok')
                            ->dateTime(),
                    ])->columns(2),
            ]);
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
                Tables\Columns\TextColumn::make('id_transaction')
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
                    ->relationship('bakpia', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
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
        $idUser = $user->id;

        if (! in_array($idUser, $adminOutlet)) {
            $outlets = $user->outlets ?? [];

            return parent::getEloquentQuery()->whereIn('id_outlet', $outlets);
        }

        return parent::getEloquentQuery();
    }
}
