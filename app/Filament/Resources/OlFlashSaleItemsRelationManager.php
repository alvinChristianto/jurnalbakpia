<?php

namespace App\Filament\Resources;

use App\Enums\DiscountType;
use App\Models\OlFlashSaleItem;
use App\Models\OlProduct;
use Filament\Forms\Components\Get;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OlFlashSaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function title(Model $ownerRecord, string $pageClass): string
    {
        return 'Item Flash Sale';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (Get $get, ?OlFlashSaleItem $record, $livewire): array {
                                $sale = $livewire->getOwnerRecord();

                                $excluded = $sale->items()
                                    ->when($record && $record->exists, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                    ->pluck('product_id');

                                return OlProduct::query()
                                    ->where('status', 'ACTIVE')
                                    ->whereKeyNot($excluded)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->rules([
                                function (Get $get, ?OlFlashSaleItem $record, $livewire) {
                                    return function (string $attribute, $value, \Closure $fail) use ($livewire, $record): void {
                                        $sale = $livewire->getOwnerRecord();

                                        $clash = OlFlashSaleItem::query()
                                            ->join('ol_flash_sales', 'ol_flash_sales.id', '=', 'ol_flash_sale_items.flash_sale_id')
                                            ->where('ol_flash_sale_items.product_id', $value)
                                            ->whereIn('ol_flash_sales.status', ['draft', 'published'])
                                            ->when(
                                                $record && $record->exists,
                                                fn (Builder $query) => $query->where('ol_flash_sale_items.id', '!=', $record->id),
                                            )
                                            ->when(
                                                $sale->exists,
                                                fn (Builder $query) => $query->where('ol_flash_sale_items.flash_sale_id', '!=', $sale->id),
                                            )
                                            ->where('ol_flash_sales.start_at', '<', $sale->start_at->copy()->addMinutes($sale->duration_minutes))
                                            ->where('ol_flash_sales.end_at', '>', $sale->start_at)
                                            ->exists();

                                        if ($clash) {
                                            $fail('Produk ini sudah terdaftar di flash sale lain dengan jadwal yang beririsan.');
                                        }
                                    };
                                },
                            ]),

                        Radio::make('discount_type')
                            ->label('Tipe Diskon')
                            ->options(collect([
                                DiscountType::PERCENT,
                                DiscountType::FIXED,
                            ])->mapWithKeys(fn (DiscountType $t): array => [$t->value => $t->label()])
                                ->all())
                            ->default(DiscountType::PERCENT->value)
                            ->columnSpan(1)
                            ->required(),

                        TextInput::make('discount_value')
                            ->label('Nilai Diskon')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->rules([
                                function (Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                        if (filled($get('product_id'))) {
                                            $product = OlProduct::find($get('product_id'));
                                        } else {
                                            $product = null;
                                        }

                                        $type = $get('discount_type');

                                        if ($type === DiscountType::PERCENT->value && $value > 100) {
                                            $fail('Persen diskon maksimum 100.');
                                        }

                                        if ($type === DiscountType::FIXED->value && $product && $value >= (int) round($product->price)) {
                                            $fail('Nominal diskon harus lebih kecil dari harga produk.');
                                        }
                                    };
                                },
                            ])
                            ->live()
                            ->helperText('Persen: 1–100. Nominal: dalam rupiah, lebih kecil dari harga produk.'),

                        Placeholder::make('final_price_preview')
                            ->label('Harga Akhir')
                            ->content(function (Get $get): string {
                                $product = OlProduct::find($get('product_id'));
                                $value = (int) ($get('discount_value') ?? 0);
                                $type = $get('discount_type');

                                if (! $product || $value <= 0 || filled($type) === false) {
                                    return '—';
                                }

                                $original = (int) round($product->price);
                                $discount = $type === DiscountType::PERCENT->value
                                    ? (int) floor($original * $value / 100)
                                    : min($value, $original);

                                return 'Rp '.number_format($original, 0, ',', '.')
                                    .' → Rp '.number_format($original - $discount, 0, ',', '.');
                            }),

                        TextInput::make('quota')
                            ->label('Kuota Flash Sale')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Kosongkan untuk kuota tanpa batas. Kuota terpisah dari stok normal.'),

                        Placeholder::make('sold_count_display')
                            ->label('Terjual')
                            ->content(fn (?OlFlashSaleItem $record): string => $record && $record->exists
                                ? number_format($record->sold_count, 0, ',', '.')
                                : '0'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('discount_type')
                    ->label('Tipe Diskon')
                    ->formatStateUsing(fn (string $state): string => DiscountType::from($state)->label()),

                TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(fn (OlFlashSaleItem $record): string => $record->discount_type === DiscountType::PERCENT
                        ? $record->discount_value.'%'
                        : 'Rp '.number_format($record->discount_value, 0, ',', '.')),

                TextColumn::make('quota')
                    ->label('Kuota')
                    ->placeholder('Unlimited'),

                TextColumn::make('sold_count')
                    ->label('Terjual'),

                TextColumn::make('progress')
                    ->label('Progres')
                    ->state(function (OlFlashSaleItem $record): ?string {
                        if ($record->quota === null) {
                            return null;
                        }

                        return $record->sold_count.'/'.$record->quota;
                    })
                    ->placeholder('—'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (OlFlashSaleItem $record): bool => $record->sold_count > 0),
            ]);
    }
}
