<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BakpiaTransactionResource\Pages;
use App\Filament\Resources\BakpiaTransactionResource\RelationManagers;
use App\Models\Bakpia;
use App\Models\BakpiaStock;
use App\Models\BakpiaTransaction;
use App\Models\Outlet;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

class BakpiaTransactionResource extends Resource
{
    protected static ?string $model = BakpiaTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Transaksi Bakpia';
    protected static ?string $navigationGroup = 'Transaksi Bakpia ';

    protected static ?string $modelLabel = 'Transaksi Bakpia';

    public static function form(Form $form): Form
    {
        function dataBakpia($idB, $var)
        {
            $idBakpiaPer = $idB;
            $boxVarianPer = $var;

            // You can now use $selectedPacketId to fetch related data or update other fields.
            if ($idBakpiaPer) {
                $sparepart = Bakpia::find($idBakpiaPer);

                if ($sparepart) {
                    // Example: Set another TextInput named 'sparepart_price' with the selected sparepart's price
                    if ($boxVarianPer === 'box_8') {
                        $prc =  $sparepart->price_8;
                    } elseif ($boxVarianPer === 'box_18') {
                        $prc =  $sparepart->price_18;
                    }
                    Log::info($sparepart->name);
                    Log::info($prc);
                    return [$sparepart->name, $prc];
                }
            } else {

                return ["", ""];
            }
        }
        function calculatePricePer($idOutlet, $idBakpiaPer, $boxVarianPer, $amountPer)
        {
            Log::info($boxVarianPer);
            $price = 0;
            $stockFromGudang = BakpiaStock::all()
                ->where('id_outlet', $idOutlet)
                ->where('id_bakpia', $idBakpiaPer)
                ->where('box_varian', $boxVarianPer)
                ->where('status', 'STOCK_IN')
                ->sum('amount');

            $stockSold = BakpiaStock::all()
                ->where('id_outlet', $idOutlet)
                ->where('id_bakpia', $idBakpiaPer)
                ->where('box_varian', $boxVarianPer)
                ->where('status', 'STOCK_SOLD')
                ->sum('amount');

            $stockReturned = BakpiaStock::all()
                ->where('id_outlet', $idOutlet)
                ->where('id_bakpia', $idBakpiaPer)
                ->where('box_varian', $boxVarianPer)
                ->where('status', 'RETURNED')
                ->sum('amount');

            $totalStock = $stockFromGudang - $stockSold - $stockReturned;
            $checkStockBakpia = $totalStock - $amountPer;

            Log::info($checkStockBakpia . ' | IN ' . $stockFromGudang . ' | SOLD ' . $stockSold . ' | RETN ' . $stockReturned . " || " . $amountPer);
            if ($checkStockBakpia < 0) {
                Notification::make()
                    ->title('Error') // Set the title of the notification
                    ->body('No Bakpia Stock left | ' . $checkStockBakpia) // Set the body of the notification
                    ->danger() // Set the type to danger (for error)
                    ->send(); // Send the notification

                // throw new \Exception('Record creation failed due to no bakpia stock left');

                return [0, $totalStock, $checkStockBakpia];
            }

            if ($boxVarianPer === 'box_8') {
                $price = Bakpia::where('id', $idBakpiaPer)->value('price_8');
            } else if ($boxVarianPer === 'box_18') {
                $price = Bakpia::where('id', $idBakpiaPer)->value('price_18');
            }

            Log::info($price);
            $price = $price * $amountPer;

            return [$price, $totalStock, $checkStockBakpia];
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
                    ->label('Nama Outlet')
                    ->options(function () {
                        /**if user login with email on adminEmail, then show all list outlet */
                        $adminEmail = ['admin@gmail.com'];

                        if (in_array(Auth::user()->email, $adminEmail, true)) {
                            $outletName = Outlet::all()->pluck('name', 'id_outlet');
                            // dd($outletName);
                            return $outletName;
                        } else {

                            $userOutlets = Auth::user()->outlets; // Get the authenticated user

                            $roleOutlets = []; // Use collect for easier manipulation

                            foreach ($userOutlets as $ids) {
                                $outletName = Outlet::all()->where('id_outlet', $ids)->pluck('name');
                                // array_push($roleOutlets, $outletName[0]);
                                $roleOutlets[$ids] = $outletName[0];
                            }
                            // dd($roleOutlets);
                            return $roleOutlets;
                        }
                    })

                    ->columnSpan('full')
                    ->required(),
                Fieldset::make('Data Bakpia')
                    ->schema([
                        Repeater::make('transaction_detail')
                            ->label('detail bakpia yang dibeli')
                            ->schema([
                                Forms\Components\Select::make('id_bakpia')
                                    ->label('jenis bakpia')
                                    ->options(function (Get $get) {
                                        return Bakpia::pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('box_varian')
                                    ->label('jenis box')
                                    ->options([
                                        'box_8' => 'isi 8',
                                        'box_18' => 'isi 18',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('amount')
                                    ->label('jumlah box')
                                    ->integer()
                                    ->required(),
                                Forms\Components\TextInput::make('price_per')
                                    ->label('harga per box')
                                    ->prefix('Rp')
                                    // ->numeric()
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
                                                $idOutlet = $get('../../id_outlet');

                                                $res =  calculatePricePer($idOutlet, $idBakpiaPer, $boxVarianPer, $amountPer);
                                                $res2 = dataBakpia($idBakpiaPer, $boxVarianPer,);

                                                $set('price_per', $res[0]);

                                                $set('stock_latest', $res[1]);

                                                $set('stock_after_sold', $res[2]);

                                                $set('name_bakpia', $res2[0]);

                                                $set('price_bakpia', $res2[1]);
                                            })
                                    ),

                                Forms\Components\Hidden::make('name_bakpia'),
                                Forms\Components\Hidden::make('price_bakpia'),
                                Forms\Components\TextInput::make('stock_latest')
                                    ->label('stock terakhir')
                                    ->integer()
                                    ->disabled(),

                                Forms\Components\TextInput::make('stock_after_sold')
                                    ->label('stock setelah dijual')
                                    ->integer()
                                    ->disabled(),



                            ])
                            ->columnSpan('full')
                            ->columns(4)
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
                Tables\Columns\TextColumn::make('total_price')
                    ->label('total harga')
                    ->money('idr')
                    ->prefix('Rp ')
                    ->numeric()
                    ->summarize(Sum::make()),
            ])
            ->filters(
                [
                    // Tables\Filters\SelectFilter::make('status')
                    //     ->options([
                    //         'PAID' => 'PAID',
                    //         'REFUND' => 'REFUND',
                    //     ]),

                    Tables\Filters\Filter::make('created_at')
                        ->form([
                            DatePicker::make('created_from'),
                            DatePicker::make('created_until'),
                        ])
                        ->indicateUsing(function (array $data): ?string {
                            if (!$data['created_from'] && !$data['created_until']) {
                                return null;
                            }
                            $indicatorFrom = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                            $indicatorUntil = ' to ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                            return $indicatorFrom . " " . $indicatorUntil;
                        })
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['created_from'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                                )
                                ->when(
                                    $data['created_until'],
                                    fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                                );
                        }),
                    Tables\Filters\SelectFilter::make('id_payment')
                        ->label('Payment')
                        ->relationship('payment', 'name'),
                    Tables\Filters\SelectFilter::make('id_outlet')
                        ->label('Outlet')
                        ->relationship('outlet', 'name')
                ],
                layout: FiltersLayout::AboveContentCollapsible
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Pdf')
                    ->icon('heroicon-m-clipboard')
                    ->url(fn (BakpiaTransaction $record) => route('bakpiaTransaction.report', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        dd('test');
        Notification::make()
            ->title('Error') // Set the title of the notification
            ->body('Something went wrong. Record not created.') // Set the body of the notification
            ->danger() // Set the type to danger (for error)
            ->send(); // Send the notification

        throw new \Exception('Record creation failed due to the specified condition.');
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
    protected function getRedirectUrl(): string
    {
        return '/'; // Or route('filament.pages.dashboard') if you want to go to the dashboard
    }
}
