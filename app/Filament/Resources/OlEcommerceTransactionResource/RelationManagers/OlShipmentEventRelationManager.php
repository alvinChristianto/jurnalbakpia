<?php

namespace App\Filament\Resources\OlEcommerceTransactionResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OlShipmentEventRelationManager extends RelationManager
{
    protected static string $relationship = 'shipmentEvents';

    protected static ?string $title = 'Riwayat Pengiriman';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipe Event')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PICKED_UP', 'IN_TRANSIT' => 'info',
                        'DELIVERED', 'FINISHED'   => 'success',
                        'RETURNED', 'FAILED'       => 'danger',
                        default                    => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('awb')
                    ->label('No. Resi (AWB)')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('event_at')
                    ->label('Waktu Event')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('Dikirim Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Selesai Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Dikembalikan Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diterima Sistem')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('event_at', 'desc');
    }
}
