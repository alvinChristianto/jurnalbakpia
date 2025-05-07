<?php

namespace App\Filament\Resources\OutletResource\RelationManagers;

use App\Models\Bakpia;
use App\Models\Outlet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BakpiaShipmentRelationManager extends RelationManager
{
    protected static string $relationship = 'bakpiaShipment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_bakpia')
                    ->options(function (Get $get) {
                        return Bakpia::pluck('name', 'id');
                    })
                    ->required(),
                
                Forms\Components\Select::make('box_varian')
                    ->options([
                        'box_8' => 'isi 8',
                        'box_18' => 'isi 18',
                    ]),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description'),
                Forms\Components\DateTimePicker::make('shipment_date')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bakpia_delivery')
            ->columns([
                Tables\Columns\TextColumn::make('bakpia.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->label('outlet tujuan'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('box_varian'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipment_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
