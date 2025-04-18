<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaTransactionResource\Pages;
use App\Filament\Resources\BakpiaTransactionResource\RelationManagers;
use App\Models\Bakpia;
use App\Models\BakpiaTransaction;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class BakpiaTransactionResource extends Resource
{
    protected static ?string $model = BakpiaTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        function calculatePricePer($idBakpiaPer, $boxVarianPer, $amountPer)
        {

            $price = 0;

            if ($boxVarianPer == 8) {
                $price = Bakpia::where('id', $idBakpiaPer)->value('price_8');
            } else if ($boxVarianPer == 18) {
                $price = Bakpia::where('id', $idBakpiaPer)->value('price_18');
            }

            $price = $price * $amountPer;
            // Log::info($price);

            return $price;
        }

        function calculatePrice($transactDetail)
        {
            $tempSumAll = 0;
            foreach ($transactDetail as $key => $bakpiaDetail) {
                $idBakpia = $bakpiaDetail['id_bakpia'];
                $box_varian = $bakpiaDetail['box_varian'];
                $amountBakpia = $bakpiaDetail['amount'];
                $pricePer = $bakpiaDetail['price_per'];

                $price = $pricePer;
                Log::info($price);

                $tempSumAll = $tempSumAll + $price;
            }

            return $tempSumAll;
        }

        return $form
            ->schema([
                Select::make('id_outlet')
                    ->relationship('outlet', 'name')
                    ->columnSpan('full')
                    ->required(),
                Fieldset::make('Data Barang')
                    ->schema([
                        Repeater::make('transaction_detail')
                            ->schema([
                                Forms\Components\Select::make('id_bakpia')
                                    ->options(function (Get $get) {
                                        return Bakpia::pluck('name', 'id');
                                    })

                                    ->required(),

                                Forms\Components\Select::make('box_varian')
                                    ->options([
                                        '8' => 'isi 8',
                                        '18' => 'isi 18',
                                    ]),
                                Forms\Components\TextInput::make('amount')
                                    ->integer(),
                                Forms\Components\TextInput::make('price_per')
                                    ->numeric()
                                    // ->disabled()
                                    ->dehydrated(true)
                                    ->reactive()
                                    ->suffixAction(
                                        Action::make('copyCostToPrice')
                                            ->icon('heroicon-m-calculator')
                                            ->action(function (Set $set, Get $get, $state) {
                                                $amountPer = $get('amount');
                                                $boxVarianPer = $get('box_varian');
                                                $idBakpiaPer = $get('id_bakpia');

                                                $priceTotlPer =  calculatePricePer($idBakpiaPer, $boxVarianPer, $amountPer);
                                                Log::info($priceTotlPer);
                                                $set('price_per', $priceTotlPer);
                                            })
                                    )



                            ])
                            ->columnSpan('full')
                            ->columns(4)
                    ]),
                Fieldset::make('Data Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true)
                            ->reactive()
                            ->suffixAction(
                                Action::make('copyCostToPrice')
                                    ->icon('heroicon-m-calculator')
                                    ->action(function (Set $set, Get $get, $state) {
                                        $transaction_detail = $get('transaction_detail');

                                        $priceTotl =  calculatePrice($transaction_detail);
                                        Log::info($priceTotl);
                                        $set('total_price', $priceTotl);
                                    })
                            ),
                        Forms\Components\Select::make('id_payment')
                            ->relationship('payment', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Select::make('id_customer')
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

                                Forms\Components\Select::make('gender')
                                    ->options([
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan'
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

                            ])
                    ])
                    ->required()
                // ->createOptionUsing(function (array $data) {
                //     $now = Carbon::now();

                //     $year = $now->format('y'); // Use 'y' for two-digit year representation
                //     $month = $now->format('m'); // Use 'm' for zero-padded month number
                //     $day = $now->format('d'); // Use 'm' for zero-padded month number

                //     // Generate three random digits
                //     $randomDigits = str_pad(random_int(100, 999), 3, '0', STR_PAD_LEFT);

                //     $transformId = "GUEST_" .  $year . $month . $day . $randomDigits;
                //     $data['id_customer'] = $transformId;

                //     return $data;
                // })
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_transaction')
                    ->searchable(),
                Tables\Columns\TextColumn::make('outlet.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name'),
                Tables\Columns\TextColumn::make('payment.name'),
                Tables\Columns\TextColumn::make('total_price')->numeric(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PAID' => 'PAID',
                        'REFUND' => 'REFUND',
                    ]),
                Tables\Filters\SelectFilter::make('id_payment')
                    ->label('Payment')
                    ->relationship('payment', 'name'),
                Tables\Filters\SelectFilter::make('id_outlet')
                    ->label('Outlet')
                    ->relationship('outlet', 'name')
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
