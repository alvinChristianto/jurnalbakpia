<?php

namespace App\Filament\Resources;

use App\Enums\TransactionStatus;
use App\Filament\Resources\OlEcommerceTransactionResource\Pages;
use App\Filament\Resources\OlEcommerceTransactionResource\RelationManagers;
use App\Models\OlEcommerceTransaction;
use App\Services\KiriminajaService;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class OlEcommerceTransactionResource extends Resource
{
    protected static ?string $model = OlEcommerceTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Transaksi Online';
    protected static ?string $navigationGroup = 'Master website ';

    protected static ?string $modelLabel = 'Transaksi Online ';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ol_customer_id')
                    ->required(),
                Forms\Components\TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('shipping_cost')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('service_fee')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('grand_total')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255)
                    ->default('pending'),
                Forms\Components\DateTimePicker::make('requested_shipping_datetime')
                    ->label('Tanggal Pengiriman Diminta'),
                Forms\Components\TextInput::make('shipping_address_snapshot')
                    ->required(),
                Forms\Components\TextInput::make('courier_name')
                    ->label('Kurir')
                    ->maxLength(255),
                Forms\Components\TextInput::make('courier_service')
                    ->label('Layanan Kurir')
                    ->maxLength(255),
                Forms\Components\TextInput::make('tracking_number')
                    ->label('No. Resi')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_method')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_url')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\DateTimePicker::make('shipped_at')
                    ->label('Dikirim Pada'),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Selesai Pada'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No Invoice')
                    ->description(fn($record) => $record->olcustomer->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Pembayaran')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_cost')
                    ->label('Ongkos Kirim')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_fee')
                    ->label('Biaya Layanan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()

                    ->formatStateUsing(fn(TransactionStatus $state) => $state->label())
                    ->color(fn(TransactionStatus $state) => $state->color())
                    ->searchable(),

                Tables\Columns\TextColumn::make('requested_shipping_datetime')
                    ->label('Tgl. Pengiriman Diminta')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('courier_name')
                    ->label('Kurir')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('courier_service')
                    ->label('Layanan Kurir')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('No. Resi')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('Dikirim Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_method')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_reference')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar Pada')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Pembayaran',
                        'paid' => 'Sudah Dibayar',
                        'processing' => 'Sedang Disiapkan',
                        'shipping' => 'Dalam Pengiriman',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Filter::make('trx_indate_range')
                    ->label('Periode Beli')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn($query, $date) =>
                                $query->whereDate('created_at', '>=', Carbon::parse($date))
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn($query, $date) =>
                                $query->whereDate('created_at', '<=', Carbon::parse($date))
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('request_kiriminaja_pickup')
                    ->label('Buat Pickup')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buat Pickup KiriminAja')
                    ->modalDescription('Permintaan pickup akan dikirim ke KiriminAja. Pastikan pesanan sudah disiapkan.')
                    ->visible(fn(OlEcommerceTransaction $record): bool =>
                        in_array($record->status->value, ['paid', 'processing'])
                        && ($record->shipping_address_snapshot['type'] ?? '') === 'delivery'
                        && is_null($record->tracking_number)
                    )
                    ->action(function (OlEcommerceTransaction $record): void {
                        try {
                            $service  = app(KiriminajaService::class);
                            $response = $service->createExpressOrder($record);
                            $kjOrderId = $response['details'][0]['kj_order_id'] ?? null;

                            $record->update([
                                'tracking_number' => $kjOrderId,
                                'shipped_at'      => now(),
                                'status'          => 'shipping',
                            ]);

                            Notification::make()
                                ->title('Pickup berhasil dibuat')
                                ->body('KJ Order ID: ' . ($kjOrderId ?? '-'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal membuat pickup')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    ExportBulkAction::make()->exports([
                        ExcelExport::make()
                            ->fromTable()
                            ->withFilename(date('Y-m-d') . ' - OL E-commerce Transactions')
                            ->withColumns([
                                Column::make('invoice_number')->heading('Invoice ID'),
                                Column::make('olcustomer.name')->heading('customer Name'),
                                Column::make('shipping_cost')->heading('Shipping Cost'),
                                Column::make('service_fee')->heading('Service Fee'),
                                Column::make('grand_total')->heading('Grand Total'),
                                Column::make('status')->heading('Status'),
                                Column::make('created_at')->heading('Created At'),
                                Column::make('updated_at')->heading('Updated At'),
                            ]),
                    ])
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OlEcommerceTransactionDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOlEcommerceTransactions::route('/'),
            'create' => Pages\CreateOlEcommerceTransaction::route('/create'),
            'edit' => Pages\EditOlEcommerceTransaction::route('/{record}/edit'),
        ];
    }
}
