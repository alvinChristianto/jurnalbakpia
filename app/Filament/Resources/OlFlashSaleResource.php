<?php

namespace App\Filament\Resources;

use App\Enums\FlashSaleStatus;
use App\Filament\Resources\OlFlashSaleResource\Pages;
use App\Models\OlFlashSale;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class OlFlashSaleResource extends Resource
{
    protected static ?string $model = OlFlashSale::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Master website';

    protected static ?string $navigationLabel = 'Flash Sale Online';

    protected static ?string $modelLabel = 'Flash Sale Online';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Flash Sale')
                    ->description('Satu event flash sale. Produk di kelola di bagian item di bawah.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Event')
                            ->required()
                            ->maxLength(150)
                            ->columnSpanFull(),

                        DateTimePicker::make('start_at')
                            ->label('Mulai (WIB)')
                            ->required()
                            ->seconds(false)
                            ->native(false),

                        TextInput::make('duration_minutes')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->integer()
                            ->default(60)
                            ->minValue(1)
                            ->maxValue(4320)
                            ->required()
                            ->helperText('Panjang event dalam menit (maksimum 3 hari = 4320 menit).'),

                        Placeholder::make('end_preview')
                            ->label('Berakhir pada')
                            ->content(function (Get $get, ?OlFlashSale $record): string {
                                return self::previewEnd($get, $record);
                            }),

                        Select::make('status')
                            ->options(collect(FlashSaleStatus::cases())
                                ->mapWithKeys(fn (FlashSaleStatus $s): array => [$s->value => $s->label()])
                                ->all())
                            ->default(FlashSaleStatus::DRAFT->value)
                            ->native(false)
                            ->required()
                            ->helperText('Gunakan aksi Publish / Cancel pada daftar atau halaman edit untuk mengubah status.'),

                        FileUpload::make('banner_path')
                            ->label('Banner Persegi (1:1)')
                            ->image()
                            ->directory('olflashsales')
                            ->disk('public')
                            ->visibility('public')
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->nullable()
                            ->helperText('Persegi (1:1) — tampil di modal pembuka storefront.'),

                        TextInput::make('banner_alt')
                            ->label('Banner Alt Text')
                            ->maxLength(255)
                            ->helperText('Wajib bila banner diunggah.')
                            ->required(fn (callable $get): bool => filled($get('banner_path'))),

                        FileUpload::make('banner_rect_path')
                            ->label('Banner Lebar (3:1)')
                            ->image()
                            ->directory('olflashsales')
                            ->disk('public')
                            ->visibility('public')
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->nullable()
                            ->helperText('Lebar (±3:1) — tampil sebagai banner besar di halaman utama. Jika kosong, banner persegi dipotong otomatis.'),

                        TextInput::make('banner_rect_alt')
                            ->label('Banner Lebar Alt Text')
                            ->maxLength(255)
                            ->helperText('Wajib bila banner lebar diunggah.')
                            ->required(fn (callable $get): bool => filled($get('banner_rect_path'))),
                    ]),
            ]);
    }

    private static function previewEnd(Get $get, ?OlFlashSale $record): string
    {
        $startAt = $get('start_at');
        $duration = (int) $get('duration_minutes');

        $effectiveStart = null;
        if ($record && $record->exists && $record->status === FlashSaleStatus::PUBLISHED) {
            $effectiveStart = $record->start_at;
        } elseif (filled($startAt)) {
            $effectiveStart = Carbon::parse($startAt);
        }

        if (! $effectiveStart || $duration <= 0) {
            return '—';
        }

        return $effectiveStart->copy()->addMinutes($duration)->format('d M Y, H:i').' WIB';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('banner_path')
                    ->label('Banner')
                    ->circular()
                    ->placeholder('—'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof FlashSaleStatus ? $state : FlashSaleStatus::from($state))->label())
                    ->color(fn ($state): string => ($state instanceof FlashSaleStatus ? $state : FlashSaleStatus::from($state))->color()),

                TextColumn::make('start_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('end_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                self::publishAction(),
                self::cancelAction(),
                self::duplicateAction(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(FlashSaleStatus::cases())
                        ->mapWithKeys(fn (FlashSaleStatus $s): array => [$s->value => $s->label()])
                        ->all()),
            ]);
    }

    public static function publishAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('publish')
            ->label('Publish')
            ->icon('heroicon-m-arrow-up-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (OlFlashSale $record): bool => $record->status === FlashSaleStatus::DRAFT)
            ->disabled(fn (OlFlashSale $record): bool => $record->items()->count() === 0)
            ->action(function (OlFlashSale $record): void {
                $record->update(['status' => FlashSaleStatus::PUBLISHED->value]);
            });
    }

    public static function cancelAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('cancelSale')
            ->label('Cancel')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Membatalkan event akan langsung menghapus harga flash sale dari semua produk.')
            ->visible(fn (OlFlashSale $record): bool => $record->status === FlashSaleStatus::PUBLISHED)
            ->action(function (OlFlashSale $record): void {
                $record->update(['status' => FlashSaleStatus::CANCELLED->value]);
            });
    }

    public static function duplicateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('duplicate')
            ->label('Duplicate')
            ->icon('heroicon-m-document-duplicate')
            ->color('gray')
            ->action(function (OlFlashSale $record): void {
                DB::transaction(function () use ($record): void {
                    $copy = $record->replicate();
                    $copy->name = $record->name.' (Copy)';
                    $copy->status = FlashSaleStatus::DRAFT;
                    $copy->push();

                    foreach ($record->items as $item) {
                        $itemCopy = $item->replicate();
                        $itemCopy->sold_count = 0;
                        $copy->items()->save($itemCopy);
                    }
                });
            });
    }

    public static function getRelations(): array
    {
        return [
            OlFlashSaleItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOlFlashSales::route('/'),
            'create' => Pages\CreateOlFlashSale::route('/create'),
            'edit' => Pages\EditOlFlashSale::route('/{record}/edit'),
        ];
    }
}
