<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\OtherProduct;
use App\Models\Outlet;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $modelLabel = 'Transaksi';

    public static function calculatePricePer($idOutlet, $idBakpiaPer, $boxVarianPer, $amountPer)
    {
        Log::info($boxVarianPer);
        $price = 0;
        $stockFromGudang = BakpiaStock::where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'STOCK_IN')
            ->sum('amount');

        $stockSold = BakpiaStock::where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'STOCK_SOLD')
            ->sum('amount');

        $stockReturned = BakpiaStock::where('id_outlet', $idOutlet)
            ->where('id_bakpia', $idBakpiaPer)
            ->where('box_varian', $boxVarianPer)
            ->where('status', 'RETURNED')
            ->sum('amount');

        $totalStock = $stockFromGudang - $stockSold - $stockReturned;
        $checkStockBakpia = $totalStock - $amountPer;

        Log::info($checkStockBakpia.' | IN '.$stockFromGudang.' | SOLD '.$stockSold.' | RETN '.$stockReturned.' || '.$amountPer);
        if ($checkStockBakpia < 0) {
            Notification::make()
                ->title('Error')
                ->body('No Bakpia Stock left | '.$checkStockBakpia)
                ->danger()
                ->send();

            return [0, $totalStock, $checkStockBakpia];
        }

        if ($boxVarianPer === 'box_8') {
            $price = Bakpia::where('id', $idBakpiaPer)->value('price_8');
        } elseif ($boxVarianPer === 'box_18') {
            $price = Bakpia::where('id', $idBakpiaPer)->value('price_18');
        }

        Log::info($price);
        $price = $price * $amountPer;

        return [$price, $totalStock, $checkStockBakpia];
    }

    public static function recalculateLine(Set $set, Get $get): void
    {
        $productType = $get('product_type');
        $productId = $get('product_id');
        $amountPer = (int) $get('amount');

        if (! $productId || ! $amountPer) {
            $set('price_per', 0);
            $set('product_name', null);
            $set('price_unit', null);
            $set('stock_latest', null);
            $set('stock_after_sold', null);

            return;
        }

        if ($productType === 'OTHER') {
            $product = OtherProduct::find($productId);
            $set('price_per', static::calculatePricePerOther($productId, $amountPer));
            $set('product_name', $product->name ?? '');
            $set('price_unit', $product->price ?? 0);
            $set('stock_latest', null);
            $set('stock_after_sold', null);

            return;
        }

        $idOutlet = $get('../../id_outlet');
        $boxVarianPer = $get('box_varian');
        $res = static::calculatePricePer($idOutlet, $productId, $boxVarianPer, $amountPer);
        $bakpia = Bakpia::find($productId);

        $set('price_per', $res[0]);
        $set('stock_latest', $res[1]);
        $set('stock_after_sold', $res[2]);
        $set('product_name', $bakpia->name ?? '');
        $set('price_unit', $boxVarianPer === 'box_8' ? $bakpia->price_8 : $bakpia->price_18);
    }

    public static function calculatePricePerOther($idProduct, $amountPer)
    {
        $price = OtherProduct::where('id', $idProduct)->value('price');

        Log::info($price);
        $price = $price * $amountPer;

        return $price;
    }

    public static function calculatePrice($transactDetail)
    {
        $tempSumAll = 0;
        foreach ($transactDetail as $detail) {
            $tempSumAll += (int) ($detail['price_per'] ?? 0);
        }

        return $tempSumAll;
    }

    public static function isAdmin(): bool
    {
        return in_array(Auth::user()->email, ['admin@gmail.com'], true);
    }

    public static function userOutlets(): array
    {
        return Auth::user()->outlets ?? [];
    }

    public static function productTypeLabel(string $type): string
    {
        return $type === 'OTHER' ? 'Produk Lain' : 'Bakpia';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('id_outlet')
                    ->label('Nama Outlet')
                    ->options(function (): array {
                        if (static::isAdmin()) {
                            return Outlet::all()->pluck('name', 'id_outlet')->toArray();
                        }

                        return Outlet::whereIn('id_outlet', static::userOutlets())
                            ->pluck('name', 'id_outlet')
                            ->toArray();
                    })
                    ->default(fn (): ?string => static::singleOutlet())
                    ->disabled(fn (): bool => static::singleOutlet() !== null)
                    ->columnSpan('full')
                    ->required(),
                Fieldset::make('Data Produk')
                    ->schema([
                        Repeater::make('transaction_details')
                            ->label('detail produk yang dibeli')
                            ->schema([
                                Select::make('product_type')
                                    ->label('tipe produk')
                                    ->options([
                                        'BAKPIA' => 'Bakpia',
                                        'OTHER' => 'Produk Lain',
                                    ])
                                    ->default('BAKPIA')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateLine($set, $get))
                                    ->required(),
                                Select::make('product_id')
                                    ->label('nama produk')
                                    ->options(function (Get $get): array {
                                        if ($get('product_type') === 'OTHER') {
                                            return OtherProduct::pluck('name', 'id')->toArray();
                                        }

                                        return Bakpia::pluck('name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateLine($set, $get))
                                    ->required(),
                                Select::make('box_varian')
                                    ->label('jenis box')
                                    ->options([
                                        'box_8' => 'isi 8',
                                        'box_18' => 'isi 18',
                                    ])
                                    ->default('box_8')
                                    ->visible(fn (Get $get): bool => $get('product_type') === 'BAKPIA')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateLine($set, $get))
                                    ->required(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('jumlah')
                                    ->integer()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateLine($set, $get))
                                    ->required(),
                                Forms\Components\TextInput::make('price_per')
                                    ->label('harga subtotal')
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\Hidden::make('product_name'),
                                Forms\Components\Hidden::make('price_unit'),
                                Forms\Components\TextInput::make('stock_latest')
                                    ->label('stock terakhir')
                                    ->integer()
                                    ->disabled()
                                    ->visible(fn (Get $get): bool => $get('product_type') === 'BAKPIA'),
                                Forms\Components\TextInput::make('stock_after_sold')
                                    ->label('stock setelah dijual')
                                    ->integer()
                                    ->disabled()
                                    ->visible(fn (Get $get): bool => $get('product_type') === 'BAKPIA'),
                            ])
                            ->columnSpan('full')
                            ->columns(['md' => 3, 'xl' => 5])
                            ->createItemButtonLabel('+ Tambah Baris'),
                    ]),
                Fieldset::make('Data Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('total harga yang harus dibayar')
                            ->numeric()
                            ->disabled()
                            ->prefix('Rp')
                            ->dehydrated(true)
                            ->reactive()
                            ->required()
                            ->suffixAction(
                                Action::make('sumPrice')
                                    ->icon('heroicon-m-calculator')
                                    ->action(function (Set $set, Get $get) {
                                        $transaction_details = $get('transaction_details');

                                        $priceTotl = static::calculatePrice($transaction_details);
                                        Log::info($priceTotl);
                                        $set('total_price', $priceTotl);
                                    })
                            ),
                        Select::make('id_payment')
                            ->label('metode pembayaran')
                            ->relationship('payment', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Select::make('id_customer')
                    ->label('data pelanggan')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->createOptionForm([
                        Fieldset::make('Label')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email address')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone_number')
                                    ->label('Phone number')
                                    ->tel()
                                    ->required(),
                                Select::make('gender')
                                    ->options([
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('address')
                                    ->rows(2)
                                    ->cols(10)
                                    ->columnSpan('full'),
                                Forms\Components\TextInput::make('city')
                                    ->maxLength(255)
                                    ->columnSpan('full'),
                                Forms\Components\TextInput::make('province')
                                    ->maxLength(255)
                                    ->columnSpan('full'),
                            ]),
                    ])
                    ->required(),
            ]);
    }

    public static function singleOutlet(): ?string
    {
        if (static::isAdmin()) {
            return null;
        }

        $userOutlets = static::userOutlets();

        return count($userOutlets) === 1 ? $userOutlets[0] : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_transaction')
                    ->label('id transaksi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('tgl transaksi'),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('pembeli')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment.name')
                    ->label('metode bayar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_count')
                    ->label('items')
                    ->state(fn (Transaction $record): int => count($record->transaction_details ?? [])),
                Tables\Columns\TextColumn::make('product_types')
                    ->label('tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Bakpia' => 'info',
                        'Produk Lain' => 'warning',
                        default => 'success',
                    })
                    ->state(function (Transaction $record): string {
                        $details = $record->transaction_details ?? [];
                        $types = collect($details)->pluck('product_type')->unique()->map(fn (string $type) => static::productTypeLabel($type));

                        if ($types->count() > 1) {
                            return 'Mixed';
                        }

                        return $types->first() ?? '-';
                    }),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('total harga')
                    ->money('idr')
                    ->prefix('Rp ')
                    ->numeric()
                    ->summarize(Sum::make()),
            ])
            ->filters(
                [
                    Tables\Filters\Filter::make('created_at')
                        ->form([
                            DatePicker::make('created_from'),
                            DatePicker::make('created_until'),
                        ])
                        ->indicateUsing(function (array $data): ?string {
                            if (! $data['created_from'] && ! $data['created_until']) {
                                return null;
                            }
                            $indicatorFrom = 'Created from '.Carbon::parse($data['created_from'])->toFormattedDateString();
                            $indicatorUntil = ' to '.Carbon::parse($data['created_until'])->toFormattedDateString();

                            return $indicatorFrom.' '.$indicatorUntil;
                        })
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn (Builder $query, $date): Builder => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->where('created_at', '<=', Carbon::parse($date)->endOfDay()),
                                );
                        }),
                    Tables\Filters\SelectFilter::make('id_payment')
                        ->label('Payment')
                        ->relationship('payment', 'name')
                        ->searchable(),
                    Tables\Filters\SelectFilter::make('id_outlet')
                        ->label('Outlet')
                        ->relationship('outlet', 'name')
                        ->searchable(),
                ],
                layout: FiltersLayout::AboveContentCollapsible
            )
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->select([
                'id_transaction',
                'id_outlet',
                'id_customer',
                'id_payment',
                'total_price',
                'status',
                'transaction_details',
                'created_at',
            ]))
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Pdf-nota')
                    ->icon('heroicon-m-clipboard')
                    ->url(fn (Transaction $record) => route('transaction.report', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()->withColumns([
                            Column::make('id_transaction'),
                            Column::make('created_at'),
                            Column::make('status'),
                            Column::make('outlet.name'),
                            Column::make('customer.name'),
                            Column::make('payment.name'),
                            Column::make('total_price'),
                        ]),
                    ]),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $adminOutlet = [1, 0];
        $user = Auth::user();

        $outlets = $user->outlets;
        $idUser = $user->id;

        if (! in_array($idUser, $adminOutlet)) {
            return parent::getEloquentQuery()->whereIn('id_outlet', $outlets);
        }

        return parent::getEloquentQuery();
    }
}
