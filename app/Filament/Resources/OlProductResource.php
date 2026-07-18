<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OlProductResource\Pages;
use App\Models\OlProduct;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OlProductResource extends Resource
{
    protected static ?string $model = OlProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Produk Online';

    protected static ?string $navigationGroup = 'Master website ';

    protected static ?string $modelLabel = 'Produk Online';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Produk')
                    ->description('Detail utama produk yang akan ditampilkan.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Select::make('category')
                            ->options([
                                'BAKPIA' => 'Bakpia',
                                'ROTI' => 'Roti',
                                'OTHER' => 'Other',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('flavor')
                            ->options([
                                'Keju' => 'Keju',
                                'Abon' => 'Abon',
                                'Pisang' => 'Pisang',
                                'Coklat_Almond' => 'Coklat Almond',
                                'Kacang_Mete' => 'Kacang Mete',
                                'Original' => 'Original',
                            ])
                            ->searchable()
                            ->native(false)
                            ->nullable()
                            ->helperText('Rasa produk, dipakai untuk filter di halaman utama toko.'),

                        TextInput::make('price')
                            ->numeric()
                            ->prefix('IDR')
                            ->required(),

                        TextInput::make('rating')
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(0)
                            ->helperText('rating product, masukkan angka 4 sampai 5 dengan format 4.50 '),

                        Toggle::make('is_featured')
                            ->label('Tampilkan di Paling Laris')
                            ->helperText('Munculkan produk ini di baris "Paling Laris" pada halaman utama.'),
                    ])->columns(2),

                Section::make('Media & Deskripsi')
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->multiple()
                            ->required()
                            ->directory('olproducts')
                            ->columnSpanFull()
                            ->disk('public')
                            ->visibility('public')
                            ->openable(true)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Foto product, boleh lebih dari 1'),

                        Textarea::make('description')
                            ->columnSpanFull()
                            ->required()
                            ->helperText('Deskripsi produk, jelaskan dengan detail tentang produk yang dijual, 2 kalimat')
                            ->rows(5),

                        Select::make('status')
                            ->options([
                                'ACTIVE' => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->default('ACTIVE')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->circular(),

                TextColumn::make('name')
                    ->searchable() // Menggunakan index 'name'
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BAKPIA' => 'primary',
                        'ROTI' => 'success',
                        'OTHER' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('flavor')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('rating')
                    ->numeric(decimalPlaces: 1)
                    ->icon('heroicon-m-star')
                    ->iconColor('warning'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),

                // Menggunakan SelectColumn agar status bisa diubah langsung dari tabel
                SelectColumn::make('status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'INACTIVE' => 'Inactive',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'BAKPIA' => 'Bakpia',
                        'ROTI' => 'Roti',
                        'OTHER' => 'Other',
                    ]),
                SelectFilter::make('flavor')
                    ->options(fn (): array => OlProduct::query()
                        ->whereNotNull('flavor')
                        ->distinct()
                        ->orderBy('flavor')
                        ->pluck('flavor', 'flavor')
                        ->all()),
                TernaryFilter::make('is_featured')
                    ->label('Featured'),
                SelectFilter::make('status'),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOlProducts::route('/'),
            'create' => Pages\CreateOlProduct::route('/create'),
            'edit' => Pages\EditOlProduct::route('/{record}/edit'),
        ];
    }
}
