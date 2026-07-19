<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OlEcommerceTransactionDetailResource\Pages;
use App\Models\OlEcommerceTransactionDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class OlEcommerceTransactionDetailResource extends Resource
{
    protected static ?string $model = OlEcommerceTransactionDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Detail Transaksi Online';

    protected static ?string $navigationGroup = 'Master website ';

    protected static ?string $modelLabel = 'Detail Transaksi Online';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('transaction_id')
                    ->relationship('transaction', 'id')
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'id')
                    ->required(),
                Forms\Components\TextInput::make('product_name_snapshot')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price_per_item')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('note')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction.invoice_number')
                    ->label('No Invoice')
                    ->searchable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_name_snapshot')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_item')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('transaction_id')
                    ->relationship('transaction', 'invoice_number')
                    ->label('No Invoice'),
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Product'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),

                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(date('Y-m-d').' - OL E-com Transactions Detail')
                            ->withColumns([
                                Column::make('transaction.invoice_number')->heading('Invoice ID'),
                                Column::make('product.name')->heading('Product Name'),
                                Column::make('quantity')->heading('Quantity'),
                                Column::make('price_per_item')->heading('Price Per Item'),
                                Column::make('note')->heading('Note'),
                                Column::make('created_at')->heading('Created At'),
                                Column::make('updated_at')->heading('Updated At'),
                            ]),
                    ]),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOlEcommerceTransactionDetails::route('/'),
            'create' => Pages\CreateOlEcommerceTransactionDetail::route('/create'),
            'edit' => Pages\EditOlEcommerceTransactionDetail::route('/{record}/edit'),
        ];
    }
}
