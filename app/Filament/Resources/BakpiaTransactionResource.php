<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaTransactionResource\Pages;
use App\Filament\Resources\BakpiaTransactionResource\RelationManagers;
use App\Models\BakpiaTransaction;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log as FacadesLog;

class BakpiaTransactionResource extends Resource
{
    protected static ?string $model = BakpiaTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Data Barang')
                ->schema([
                    Repeater::make('transaction_detail')
                        ->schema([
                            Forms\Components\Select::make('payment_id')
                            ->relationship('payment', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                           
                        ])
                        ->columnSpan('full')
                        ->columns(2)
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListBakpiaTransactions::route('/'),
            'create' => Pages\CreateBakpiaTransaction::route('/create'),
            'edit' => Pages\EditBakpiaTransaction::route('/{record}/edit'),
        ];
    }
}
