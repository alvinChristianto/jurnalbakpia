<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\Iseya;
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

    /**
     * @return array{0: int|float|null, 1: int|null, 2: int|null}
     */
    public static function calculatePricePer(?string $idOutlet, $idBakpiaPer, $amountPer)
    {
        $price = Bakpia::where('id', $idBakpiaPer)->value('price') * $amountPer;

        // No outlet picked yet (multi-outlet users start with a blank selector):
        // price can still be shown, but on-hand stock cannot be resolved.
        if (blank($idOutlet)) {
            return [$price, null, null];
        }

        $totalStock = BakpiaStock::onHand($idOutlet, (int) $idBakpiaPer);
        $checkStockBakpia = $totalStock - $amountPer;

        if ($checkStockBakpia < 0) {
            Notification::make()
                ->title('Error')
                ->body('No Bakpia Stock left | '.$checkStockBakpia)
                ->danger()
                ->send();

            return [0, $totalStock, $checkStockBakpia];
        }

        return [$price, $totalStock, $checkStockBakpia];
    }

    /**
     * State of a single line item, independent of which component triggered it.
     *
     * @return array<string, mixed>
     */
    public static function lineStateFor(?string $productType, mixed $productId, int $amountPer, ?string $idOutlet, ?string $discountType = null, mixed $discountValue = null): array
    {
        $state = static::baseLineStateFor($productType, $productId, $amountPer, $idOutlet);
        $state['discount_per'] = static::discountPer((int) $state['price_per'], $discountType, $discountValue);
        $state['price_after_discount'] = max(0, (int) $state['price_per'] - $state['discount_per']);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function baseLineStateFor(?string $productType, mixed $productId, int $amountPer, ?string $idOutlet): array
    {
        $state = [
            'price_per' => 0,
            'product_name' => null,
            'price_unit' => null,
            'stock_latest' => null,
            'stock_after_sold' => null,
        ];

        if (! $productId || $amountPer <= 0) {
            return $state;
        }

        if ($productType === 'OTHER') {
            $product = OtherProduct::find($productId);

            $state['price_per'] = static::calculatePricePerOther($productId, $amountPer);
            $state['product_name'] = $product->name ?? '';
            $state['price_unit'] = $product->price ?? 0;

            return $state;
        }

        if ($productType === 'ISEYA') {
            $product = Iseya::find($productId);

            $state['price_per'] = $product->price * $amountPer;
            $state['product_name'] = $product->name ?? '';
            $state['price_unit'] = $product->price ?? 0;

            return $state;
        }

        [$price, $stockLatest, $stockAfterSold] = static::calculatePricePer($idOutlet, $productId, $amountPer);
        $bakpia = Bakpia::find($productId);

        $state['price_per'] = $price;
        $state['stock_latest'] = $stockLatest;
        $state['stock_after_sold'] = $stockAfterSold;
        $state['product_name'] = $bakpia->name ?? '';
        $state['price_unit'] = $bakpia->price ?? 0;

        return $state;
    }

    /**
     * Nominal discount amount for a gross value, clamped to 0..gross.
     * PERCENT accepts decimals (0-100); the result is rounded half-up to
     * whole rupiah because total_price columns are integers.
     */
    public static function discountPer(int|float $gross, ?string $type, mixed $value): int
    {
        $value = (float) $value;

        if ($value <= 0 || $gross <= 0) {
            return 0;
        }

        $amount = $type === 'NOMINAL'
            ? min($value, (float) $gross)
            : $gross * min($value, 100) / 100;

        return (int) min(round($amount), round($gross));
    }

    /**
     * Whether the given outlet may use the given discount scope
     * ('line' or 'total'). An unpicked outlet cannot be verified yet,
     * so discount inputs stay hidden until one is selected.
     */
    public static function outletCan(?string $idOutlet, string $flag): bool
    {
        if (blank($idOutlet)) {
            return false;
        }

        $column = $flag === 'total' ? 'can_discount_total' : 'can_discount_line';

        return (bool) Outlet::query()->where('id_outlet', $idOutlet)->value($column);
    }

    /**
     * Recompute the form-level totals from the current line state.
     * $prefix is '' from root-context fields and '../../' from inside a
     * repeater line.
     */
    public static function recalculateTotals(Set $set, Get $get, string $prefix = ''): void
    {
        $idOutlet = $get($prefix.'id_outlet');
        $details = $get($prefix.'transaction_details') ?? [];
        $gross = static::calculatePrice($details);

        $lineDiscount = static::outletCan($idOutlet, 'line')
            ? collect($details)->sum(fn (array $detail): int => (int) ($detail['discount_per'] ?? 0))
            : 0;

        $headerDiscount = static::outletCan($idOutlet, 'total')
            ? static::discountPer($gross, $get($prefix.'discount_type'), $get($prefix.'discount_value'))
            : 0;

        $set($prefix.'discount', $headerDiscount);
        $set($prefix.'total_price', $gross);
        $set($prefix.'total_discount', $headerDiscount + $lineDiscount);
        $set($prefix.'total_price_after_discount', max(0, $gross - $headerDiscount - $lineDiscount));
    }

    /**
     * Authoritative server-side normalization of all discount fields. Runs on
     * create and on save: recomputes per-line discounts and both totals from
     * the stored type/value inputs, and strips discounts when the outlet
     * does not allow them.
     *
     * @return array<string, mixed>
     */
    public static function normalizeDiscounts(array $data): array
    {
        $outlet = isset($data['id_outlet']) ? Outlet::find($data['id_outlet']) : null;
        $canLine = (bool) ($outlet->can_discount_line ?? false);
        $canTotal = (bool) ($outlet->can_discount_total ?? false);

        $gross = 0;
        $lineDiscount = 0;

        foreach ($data['transaction_details'] ?? [] as $index => $item) {
            $itemGross = (int) ($item['price_per'] ?? 0);
            $gross += $itemGross;

            $discountPer = $canLine
                ? static::discountPer($itemGross, $item['discount_type'] ?? null, $item['discount_value'] ?? 0)
                : 0;

            $data['transaction_details'][$index]['discount_per'] = $discountPer;
            $data['transaction_details'][$index]['price_after_discount'] = max(0, $itemGross - $discountPer);
            $lineDiscount += $discountPer;
        }

        $headerDiscount = 0;

        if ($canTotal) {
            $type = $data['discount_type'] ?? null;
            $value = $data['discount_value'] ?? null;

            // Legacy rows carry a raw nominal discount with no type/value
            // inputs; keep it as-is (clamped) instead of recomputing to zero.
            $headerDiscount = (blank($type) && blank($value))
                ? static::discountPer($gross, 'NOMINAL', $data['discount'] ?? 0)
                : static::discountPer($gross, $type, $value);
        }

        $data['discount'] = $headerDiscount;
        $data['total_price'] = $gross;
        $data['total_discount'] = $headerDiscount + $lineDiscount;
        $data['total_price_after_discount'] = max(0, $gross - $headerDiscount - $lineDiscount);

        return $data;
    }

    public static function recalculateLine(Set $set, Get $get): void
    {
        $state = static::lineStateFor(
            $get('product_type'),
            $get('product_id'),
            (int) $get('amount'),
            $get('../../id_outlet'),
            $get('discount_type'),
            $get('discount_value'),
        );

        foreach ($state as $field => $value) {
            $set($field, $value);
        }

        static::recalculateTotals($set, $get, '../../');
    }

    /**
     * Re-run every line after the outlet changed, so stock fields that were
     * deferred (no outlet picked yet) get filled in and discount values are
     * cleared when the new outlet forbids them.
     */
    public static function recalculateAllLines(Set $set, Get $get): void
    {
        $idOutlet = $get('id_outlet');
        $canDiscountLine = static::outletCan($idOutlet, 'line');

        foreach (array_keys($get('transaction_details') ?? []) as $key) {
            $discountType = $get("transaction_details.{$key}.discount_type");
            $discountValue = $get("transaction_details.{$key}.discount_value");

            if (! $canDiscountLine) {
                $discountType = 'PERCENT';
                $discountValue = 0;
                $set("transaction_details.{$key}.discount_type", $discountType);
                $set("transaction_details.{$key}.discount_value", $discountValue);
            }

            $state = static::lineStateFor(
                $get("transaction_details.{$key}.product_type"),
                $get("transaction_details.{$key}.product_id"),
                (int) $get("transaction_details.{$key}.amount"),
                $idOutlet,
                $discountType,
                $discountValue,
            );

            foreach ($state as $field => $value) {
                $set("transaction_details.{$key}.{$field}", $value);
            }
        }

        if (! static::outletCan($idOutlet, 'total')) {
            $set('discount_type', 'PERCENT');
            $set('discount_value', 0);
        }

        static::recalculateTotals($set, $get);
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
        return match ($type) {
            'OTHER' => 'Produk Lain',
            'ISEYA' => 'Iseya',
            default => 'Bakpia',
        };
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
                    ->dehydrated(true)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateAllLines($set, $get))
                    ->helperText(function (): ?string {
                        if (static::singleOutlet() !== null) {
                            return null;
                        }

                        return 'Stock baru dihitung setelah outlet dipilih.';
                    })
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
                                        'ISEYA' => 'Iseya',
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

                                        if ($get('product_type') === 'ISEYA') {
                                            return Iseya::pluck('name', 'id')->toArray();
                                        }

                                        return Bakpia::pluck('name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
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
                                Forms\Components\Hidden::make('box_varian'),
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
                                Forms\Components\Grid::make()
                                    ->columns(['default' => 2])
                                    ->columnSpan('full')
                                    ->schema([
                                        Forms\Components\ToggleButtons::make('discount_type')
                                            ->label('tipe diskon')
                                            ->options([
                                                'PERCENT' => 'Persen',
                                                'NOMINAL' => 'Nominal',
                                            ])
                                            ->inline()
                                            ->default('PERCENT')
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                $set('discount_value', null);
                                                static::recalculateLine($set, $get);
                                            }),
                                        Forms\Components\TextInput::make('discount_value')
                                            ->label('nilai diskon')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(fn (Get $get): float => ($get('discount_type') ?? 'PERCENT') === 'PERCENT' ? 100 : (float) ($get('price_per') ?? 0))
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateLine($set, $get)),
                                    ])
                                    ->visible(fn (Get $get): bool => static::outletCan($get('../../id_outlet'), 'line')),
                                Forms\Components\Hidden::make('discount_per'),
                            ])
                            ->columnSpan('full')
                            ->columns(['md' => 3, 'xl' => 5])
                            ->createItemButtonLabel('+ Tambah Baris'),
                    ]),
                Fieldset::make('Data Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('total harga sebelum diskon')
                            ->numeric()
                            ->disabled()
                            ->prefix('Rp')
                            ->dehydrated(true)
                            ->suffixAction(
                                Action::make('sumPrice')
                                    ->icon('heroicon-m-calculator')
                                    ->action(function (Set $set, Get $get) {
                                        static::recalculateTotals($set, $get);
                                    })
                            ),
                        Forms\Components\Grid::make()
                            ->columns(['default' => 2])
                            ->columnSpan('full')
                            ->schema([
                                Forms\Components\ToggleButtons::make('discount_type')
                                    ->label('diskon transaksi')
                                    ->options([
                                        'PERCENT' => 'Persen',
                                        'NOMINAL' => 'Nominal',
                                    ])
                                    ->inline()
                                    ->default('PERCENT')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                        $set('discount_value', null);
                                        static::recalculateTotals($set, $get);
                                    })
                                    ->visible(fn (Get $get): bool => static::outletCan($get('id_outlet'), 'total')),
                                Forms\Components\TextInput::make('discount_value')
                                    ->label('nilai diskon transaksi')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(fn (Get $get): float => ($get('discount_type') ?? 'PERCENT') === 'PERCENT' ? 100 : (float) ($get('total_price') ?? 0))
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::recalculateTotals($set, $get))
                                    ->visible(fn (Get $get): bool => static::outletCan($get('id_outlet'), 'total')),
                            ]),
                        Forms\Components\Hidden::make('discount'),
                        Forms\Components\TextInput::make('total_discount')
                            ->label('total diskon (item + transaksi)')
                            ->numeric()
                            ->disabled()
                            ->prefix('Rp')
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('total_price_after_discount')
                            ->label('total harga setelah diskon')
                            ->numeric()
                            ->disabled()
                            ->prefix('Rp')
                            ->dehydrated(true),
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
                        'Iseya' => 'success',
                        default => 'primary',
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
                Tables\Columns\TextColumn::make('discount')
                    ->label('diskon')
                    ->money('idr')
                    ->prefix('Rp ')
                    ->numeric()
                    ->default(0),
                Tables\Columns\TextColumn::make('total_discount')
                    ->label('total diskon')
                    ->money('idr')
                    ->prefix('Rp ')
                    ->numeric()
                    ->default(0),
                Tables\Columns\TextColumn::make('total_price_after_discount')
                    ->label('total (setelah diskon)')
                    ->money('idr')
                    ->prefix('Rp ')
                    ->numeric()
                    ->default(0)
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
                    Tables\Filters\SelectFilter::make('product_type')
                        ->label('Tipe Produk')
                        ->options([
                            'BAKPIA' => 'Bakpia',
                            'ISEYA' => 'Iseya',
                            'OTHER' => 'Produk Lain',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query->when(
                                $data['value'] ?? null,
                                fn (Builder $query, string $type): Builder => $query->whereJsonContains('transaction_details', ['product_type' => $type]),
                            );
                        }),
                ],
                layout: FiltersLayout::AboveContentCollapsible
            )
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->select([
                'id_transaction',
                'id_outlet',
                'id_customer',
                'id_payment',
                'total_price',
                'discount',
                'discount_type',
                'discount_value',
                'total_discount',
                'total_price_after_discount',
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
                            Column::make('discount'),
                            Column::make('total_discount'),
                            Column::make('total_price_after_discount'),
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

        $outlets = $user->outlets ?? [];
        $idUser = $user->id;

        if (! in_array($idUser, $adminOutlet)) {
            return parent::getEloquentQuery()->whereIn('id_outlet', $outlets);
        }

        return parent::getEloquentQuery();
    }
}
