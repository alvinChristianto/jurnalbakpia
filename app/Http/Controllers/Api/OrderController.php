<?php

namespace App\Http\Controllers\Api;

use App\Events\TransaksiBerhasil;
use App\Http\Controllers\Controller;
use App\Mail\TransaksiMail;
use App\Models\OlCustomer;
use App\Models\OlEcommerceTransaction;
use App\Models\OlEcommerceTransactionDetail;
use App\Models\OlProduct;
use App\Services\FonnteService;
use App\Services\KiriminajaService;
use App\Services\WhatsAppTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use Picqer\Barcode\BarcodeGeneratorPNG;

class OrderController extends Controller
{
    private function calculateAdminFee(int $subtotal): int
    {
        return (int) min(
            round($subtotal * config('bakpia.admin_fee_percent') / 100),
            config('bakpia.admin_fee_max'),
        );
    }

    public function checkoutConfig()
    {
        return response()->json([
            'admin_fee_percent' => config('bakpia.admin_fee_percent'),
            'admin_fee_max' => config('bakpia.admin_fee_max'),
        ]);
    }

    public function getTokenMidtransv1(Request $request)
    {
        // 1. Setup Midtrans Config
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = (bool) env('MIDTRANS_IS_PROD', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $validated = $request->validate([
            // Validate the nested customer_data object
            'customer_data' => 'required|array',

            // name cannot be null/empty
            'customer_data.namaPenerima' => 'required|string|max:255',

            // email cannot be null, must be a valid email format
            'customer_data.email' => 'required|email|max:255',

            // phone cannot be null
            'customer_data.nomorTelepon' => 'required|string|min:8|max:15',

            // Also validate your items while we're at it
            'order_items' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
                $total = collect($value)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
                if ($total < config('order.min_checkout_qty')) {
                    $fail('Minimal pembelian adalah '.config('order.min_checkout_qty').' item.');
                }
            }],
            'order_items.*.id' => 'required',
            'order_items.*.name' => 'required|string',
            'order_items.*.price' => 'required|numeric|min:0',
            'order_items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:1',
            'shipping_address' => 'required|array',
            'shipping_cost' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',       // if this set to 0, then dont to send to item_details
        ]);

        $customerData = $validated['customer_data'];
        $totalPrice = $validated['total_price'];
        $orderDetail = $validated['order_items'];
        $shippingDetail = $validated['shipping_address'];
        $shippingCost = $validated['shipping_cost'];

        Log::info('API|getTokenMidtransv1|transactionToken-data-V1|parameter|'.$totalPrice.'|'.json_encode($customerData).'|'.json_encode($orderDetail).'|'.json_encode($shippingDetail));

        $customer = OlCustomer::firstOrCreate(
            ['email' => $customerData['email']],
            [
                'name' => $customerData['namaPenerima'],
                'phone_number' => $customerData['nomorTelepon'] ?? null,
                'password' => bcrypt(Str::random(12)),
            ]
        );

        // Keep stored phone in sync with what the customer entered at checkout
        // (firstOrCreate only sets it on first creation, so returning customers
        // would otherwise keep a stale/empty number).
        if (! empty($customerData['nomorTelepon'])
            && $customer->phone_number !== $customerData['nomorTelepon']) {
            $customer->update(['phone_number' => $customerData['nomorTelepon']]);
        }

        // Now you can safely get the ID
        $getIdCustomer = $customer->id;
        $isDelivery = ($shippingDetail['type'] ?? '') === 'delivery';

        // Resolve real prices from the DB — never trust $item['price'] from the client.
        $products = OlProduct::whereIn('id', collect($orderDetail)->pluck('id'))->get()->keyBy('id');
        foreach ($orderDetail as $item) {
            $product = $products->get($item['id']);
            if (! $product || strtoupper($product->status) !== 'ACTIVE') {
                return response()->json([
                    'message' => 'Salah satu produk tidak lagi tersedia. Silakan muat ulang halaman.',
                    'errors' => ['order_items' => ['Produk tidak ditemukan atau tidak aktif.']],
                ], 422);
            }
        }

        $subtotal = (int) collect($orderDetail)->sum(
            fn ($item) => $products->get($item['id'])->price * $item['quantity']
        );
        $serviceFee = $this->calculateAdminFee($subtotal);
        $grandTotal = $subtotal + $shippingCost + $serviceFee;

        if ((int) round($grandTotal) !== (int) round($totalPrice)) {
            return response()->json([
                'message' => 'Harga berubah, silakan muat ulang halaman.',
                'errors' => ['price_mismatch' => ['Total pesanan tidak sesuai dengan harga terkini.']],
            ], 422);
        }

        // Generate invoice number prefix based on environment
        $invoicePrefix = env('APP_ENV') === 'production' ? 'BAK-' : 'INV0-';

        $OlTransaction = OlEcommerceTransaction::create([
            'ol_customer_id' => $getIdCustomer,
            'invoice_number' => $invoicePrefix.strtoupper(Str::random(8)),
            'subtotal' => $grandTotal, // unchanged behaviour: this column mirrors the grand total, not the true subtotal (see plan-admin-fee-cap.md)
            'shipping_cost' => $shippingCost,
            'service_fee' => $serviceFee,
            'grand_total' => $grandTotal,
            'shipping_address_snapshot' => $shippingDetail,
            'status' => 'pending',
            'courier_name' => $isDelivery
                ? ($shippingDetail['courier']['service'] ?? null)
                : 'pickup',
            'courier_service' => $isDelivery
                ? ($shippingDetail['courier']['service_name'] ?? null)
                : null,
            'requested_shipping_datetime' => ! $isDelivery && isset($shippingDetail['pickupDate'])
                ? Carbon::parse($shippingDetail['pickupDate'].' '.($shippingDetail['pickupTime'] ?? '00:00'))
                : null,
        ]);

        // not necessary for now
        $OrderId = $OlTransaction->id;

        foreach ($orderDetail as $item) {
            OlEcommerceTransactionDetail::create([
                'transaction_id' => $OrderId,
                'product_id' => $item['id'],
                'product_name_snapshot' => $item['name'],
                'quantity' => $item['quantity'],
                'price_per_item' => $products->get($item['id'])->price,
                'note' => $item['note'] ?? null,
            ]);
        }

        // 3. Generate the Unique Order ID for Midtrans
        // We combine your Invoice Number + 4 Random Chars to avoid "Duplicate Order ID" errors in Midtrans
        $fourUniqDigit = strtoupper(Str::random(4));
        $midtransOrderId = $OlTransaction->invoice_number.'-'.$fourUniqDigit;

        // return $shippingDetail;
        // You can set this to anything you want, or remove it if not needed
        if ($shippingDetail['type'] === 'delivery') {
            $customField1 = 'delivery|'.($shippingDetail['courier']['service'] ?? '').'|'.($shippingDetail['courier']['service_name'] ?? '');
        } else {
            $customField1 = 'pickup|'.($shippingDetail['storeName'] ?? '').'|'.($shippingDetail['storeAddress'] ?? '');
        }

        $itemDetails = collect($orderDetail)->map(function ($item) use ($products) {
            return [
                'id' => $item['id'],
                'price' => (int) $products->get($item['id'])->price,
                'quantity' => (int) $item['quantity'],
                'name' => Str::limit($item['name'], 50),
            ];
        })->toArray();

        if ($shippingCost > 0) {
            $itemDetails[] = [
                'id' => 'SHP-01',
                'price' => $shippingCost,
                'quantity' => 1,
                'name' => $shippingDetail['courier']['service_name'] ?? 'shipping cost',
            ];
        }

        // 4. Push Tax into the array
        if ($serviceFee > 0) {
            $itemDetails[] = [
                'id' => 'TAX-01',
                'price' => $serviceFee,
                'quantity' => 1,
                'name' => 'admin fee',
            ];
        }

        // 4. Prepare Midtrans Parameters
        $params = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => (int) $grandTotal, // Must be integer
            ],
            'customer_details' => [
                'first_name' => $customerData['namaPenerima'],
                'last_name' => '', // You can split the name if needed'',
                'email' => $customerData['email'],
                'phone' => $customerData['nomorTelepon'] ?? '',
                'billing_address' => [
                    'first_name' => $customerData['namaPenerima'],
                    'last_name' => '',
                    'email' => $customerData['email'],
                    'phone' => $customerData['nomorTelepon'] ?? '',
                    'address' => $shippingDetail['fullAddress'] ?? '',
                    'city' => $shippingDetail['city_name'] ?? '',
                    'postal_code' => $shippingDetail['postalCode'] ?? '',
                    'country_code' => $shippingDetail['countryCode'] ?? '',
                ],
                'shipping_address' => [
                    'first_name' => $customerData['namaPenerima'],
                    'last_name' => '',
                    'email' => $customerData['email'],
                    'phone' => $customerData['nomorTelepon'] ?? '',
                    'address' => $shippingDetail['fullAddress'] ?? '',
                    'city' => $shippingDetail['city_name'] ?? '',
                    'postal_code' => $shippingDetail['postalCode'] ?? '',
                    'country_code' => $shippingDetail['countryCode'] ?? '',
                ],
            ],
            'item_details' => $itemDetails,
            'custom_field1' => $customField1,
            'custom_field2' => '',

        ];

        // TAMBAH DISINI, DI CUSTOMER DETAIL DITAMBAH shipping_address json lalu di fronted kirim id provice dll, llu get price seperti biasa, dan cocockkan . jika tidak cocok maka tidak valid, lalu update ke database
        Log::info('API|getTokenMidtransv1|transactionToken-data-V1|parameter1|'.json_encode($params));

        // 5. Get Snap Token
        try {
            $snapToken = Snap::getSnapToken($params);

            // Save the Snap Token and the ID used to Midtrans into our DB
            $OlTransaction->update([
                'invoice_number_backend' => $midtransOrderId,
                'payment_token_midtrans' => $snapToken,
            ]);

            $reference = [
                'invoice_number_backend' => $midtransOrderId,
                'payment_token_midtrans' => $snapToken, // We store the token here for easy access
            ];

            return response()->json([
                'data' => [
                    'message' => 'Snap token generated',
                    'snap_token' => $snapToken,
                    'reference' => $reference,
                    'subtotal' => $subtotal,
                    'service_fee' => $serviceFee,
                    'shipping_cost' => $shippingCost,
                    'grand_total' => $grandTotal,
                ],

            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Midtrans Error: '.$e->getMessage(),
            ], 500);
        }
    }

    public function handleMidtransCallback(Request $request)
    {
        // 1. Setup Midtrans Config
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        $hashes = hash('sha512', $request->order_id.$request->status_code.$request->gross_amount.Config::$serverKey);

        $txStatus = $request->transaction_status;

        // get original order id
        $last_dash_pos = strrpos($request->order_id, '-');
        $originalOrderId = substr($request->order_id, 0, $last_dash_pos);
        Log::info('Midtrans Callback: Received callback for order_id '.$request->order_id.' with status '.$txStatus);
        if ($hashes == $request->signature_key) {
            if ($txStatus == 'capture' || $txStatus == 'settlement') {
                // Update transaction status in DB to "paid"
                $transaction = OlEcommerceTransaction::where('invoice_number', $originalOrderId)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }

                // Guard against double-encoded legacy records (stored as json_encode'd string)
                $shippingSnapshot = $transaction->shipping_address_snapshot;
                if (is_string($shippingSnapshot)) {
                    $shippingSnapshot = json_decode($shippingSnapshot, true);
                }
                Log::info('shhiping snapshot: '.json_encode($shippingSnapshot));

                if (($shippingSnapshot['type'] ?? '') === 'delivery') {
                    Log::info('Midtrans Callback: Transaction '.$transaction->invoice_number.' is a delivery order, requesting KiriminAja pickup');
                    try {
                        $kiriminajaResponse = (new KiriminajaService)->createExpressOrder($transaction);
                        $transaction->update([
                            'tracking_number' => $kiriminajaResponse['pickup_number'] ?? null,
                        ]);
                        Log::info('Midtrans Callback: KiriminAja pickup requested, pickup_number='.($kiriminajaResponse['pickup_number'] ?? 'n/a'));
                    } catch (\Exception $e) {
                        Log::error('Midtrans Callback: KiriminAja request_pickup failed for '.$transaction->invoice_number.': '.$e->getMessage());
                    }
                } else {
                    Log::info('Midtrans Callback: Transaction '.$transaction->invoice_number.' is a store-pickup order, no courier needed');
                }

                // $trxSendEmail = OlEcommerceTransaction::where('invoice_number', $originalOrderId)->first();
                // Log::info("Midtrans Callback: Transaction " . json_encode($trxSendEmail));
                // event(new TransaksiBerhasil($trxSendEmail));
                // Mencari transaksi berdasarkan invoice_number dengan eager loading customer
                $transaksi = OlEcommerceTransaction::with('olcustomer', 'details')
                    ->where('invoice_number', $originalOrderId) // Pastikan variabel $invoice_number sudah didefinisikan
                    ->first();

                // Validasi jika transaksi tidak ditemukan
                if (! $transaksi) {
                    Log::error("Transaksi dengan Invoice #{$originalOrderId} tidak ditemukan!");

                    return;
                }

                // Validasi jika relasi customer tidak ada
                if (! $transaksi->olcustomer) {
                    Log::error("Transaksi Invoice #{$originalOrderId} tidak memiliki data customer (ID: {$transaksi->ol_customer_id})!");

                    return;
                }
                Log::info('Midtrans Callback: Sending email for transaction '.$transaksi->invoice_number.' to '.$transaksi->olcustomer->email);

                Mail::to($transaksi->olcustomer->email)->sendNow(new TransaksiMail($transaksi));

                // WhatsApp notification (Fonnte) — never let a send failure break the webhook.
                try {
                    $phone = $transaksi->olcustomer->phone_number;
                    if ($phone) {
                        (new FonnteService)->sendMessage($phone, WhatsAppTemplate::paymentSuccess($transaksi));
                    }
                } catch (\Throwable $e) {
                    Log::error("Midtrans Callback: Fonnte WA failed for {$transaksi->invoice_number}: {$e->getMessage()}");
                }
            } elseif ($txStatus == 'deny' || $txStatus == 'cancel' || $txStatus == 'expire' || $txStatus == 'failure') {
                // Update transaction status in DB to "failed"
                $transaction = OlEcommerceTransaction::where('invoice_number', $originalOrderId)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => 'failed',
                    ]);
                }

                // You can handle pending status if needed
            } else {
                // Handle other statuses like "deny", "expire", "cancel" if needed
            }
        } else {
            Log::warning('Midtrans Callback: Invalid signature key for order_id '.$request->order_id);

            return response()->json(['message' => 'Invalid signature key'], 400);
        }
    }

    public function getTransactionDetailByInvoice($invoice_number)
    {
        $transaction = OlEcommerceTransaction::where('invoice_number', $invoice_number)->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // You can also load the transaction details if needed
        $transactionDetails = OlEcommerceTransactionDetail::where('transaction_id', $transaction->id)->get();

        return response()->json([
            'data' => [
                'success' => true,
                'message' => 'Transaction retrieved successfully',
                'data' => $transaction,
                'details' => $transactionDetails, // Uncomment if you want to include details
            ],
        ], 200);
    }

    public function getShippingTracking($invoice_number)
    {
        $transaction = OlEcommerceTransaction::where('invoice_number', $invoice_number)->first();

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if (! $transaction->tracking_number) {
            return response()->json(['success' => false, 'message' => 'No tracking number available yet', 'tracking' => null], 200);
        }

        try {
            $data = (new KiriminajaService)->getTracking($invoice_number);

            return response()->json([
                'success' => $data['status'] ?? false,
                'tracking' => $data,
            ], 200);
        } catch (\Exception $e) {
            Log::error('OrderController|getShippingTracking|error', [
                'invoice' => $invoice_number,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage(), 'tracking' => null], 200);
        }
    }

    public function getShippingLabel($invoice_number)
    {
        $transaction = OlEcommerceTransaction::with('olcustomer', 'details')
            ->where('invoice_number', $invoice_number)
            ->first();

        if (! $transaction) {
            abort(404, 'Transaction not found');
        }

        $printableStatuses = ['paid', 'shipping', 'completed'];
        if (! in_array($transaction->status->value, $printableStatuses, true)
            || $transaction->courier_name === 'pickup') {
            abort(404, 'Shipping label not available for this order');
        }

        // Barcode value: AWB from latest shipment event → tracking_number → invoice_number.
        $awb = $transaction->shipmentEvents()
            ->whereNotNull('awb')
            ->latest('event_at')
            ->value('awb');
        $barcodeValue = $awb ?: ($transaction->tracking_number ?: $transaction->invoice_number);

        // Recipient address — same snapshot-join logic as KiriminajaService::createExpressOrder.
        $snapshot = $transaction->shipping_address_snapshot;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }
        $snapshot = $snapshot ?: [];

        $recipientAddress = $snapshot['fullAddress'] ?? implode(', ', array_filter([
            $snapshot['street_detail'] ?? null,
            $snapshot['kelurahan_name'] ?? null,
            $snapshot['kecamatan_name'] ?? null,
            $snapshot['city_name'] ?? null,
            $snapshot['province_name'] ?? null,
            $snapshot['postalCode'] ?? null,
        ]));

        $totalQty = max(1, (int) $transaction->details->sum('quantity'));
        $weightGrams = $totalQty * (int) config('kiriminaja.box_weight_grams');

        $items = $transaction->details
            ->map(fn ($d) => $d->product_name_snapshot.' ×'.$d->quantity)
            ->implode(', ');

        $note = optional($transaction->details->first(fn ($d) => filled($d->note)))->note;

        // Code128 barcode as PNG data-URI; DomPDF cannot draw barcodes natively.
        $barcodeGenerator = new BarcodeGeneratorPNG;
        $barcodePng = $barcodeGenerator->getBarcode(
            $barcodeValue,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            60,
        );
        $barcodeDataUri = 'data:image/png;base64,'.base64_encode($barcodePng);

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('pdf.shipping_label', [
            'transaction' => $transaction,
            'barcodeValue' => $barcodeValue,
            'barcodeDataUri' => $barcodeDataUri,
            'service' => strtoupper($transaction->courier_service ?: $transaction->courier_name),
            'weightGrams' => $weightGrams,
            'totalQty' => $totalQty,
            'recipientName' => $transaction->olcustomer->name ?? '-',
            'recipientPhone' => $transaction->olcustomer->phone_number ?? '-',
            'recipientAddress' => $recipientAddress ?: '-',
            'senderName' => config('kiriminaja.sender_name'),
            'senderPhone' => config('kiriminaja.sender_phone'),
            'senderAddress' => config('kiriminaja.sender_address'),
            'items' => $items ?: '-',
            'note' => $note ?: '-',
        ])->setPaper([0, 0, 283.46, 425.20]); // 100×150mm label in PDF points

        return $pdf->stream('label-'.$transaction->invoice_number.'.pdf', ['Attachment' => false]);
    }

    public function getShippingPrice(Request $request)
    {
        $validated = $request->validate([
            'destination_kecamatan_id' => 'required|integer',
            'destination_kelurahan_id' => 'required|integer',
            'total_qty' => 'required|integer|min:1',
            'item_value' => 'required|integer|min:0',
        ]);

        try {
            $results = (new KiriminajaService)->getShippingPrice(
                $validated['destination_kecamatan_id'],
                $validated['destination_kelurahan_id'],
                $validated['total_qty'],
                $validated['item_value'],
            );

            return response()->json([
                'success' => true,
                'results' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('OrderController|getShippingPrice|error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'results' => [],
            ], 502);
        }
    }

    public function orderlists(Request $request)
    {
        $customer = $request->user();

        $transactions = OlEcommerceTransaction::where('ol_customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => [
                'success' => true,
                'message' => 'Order list retrieved successfully',
                'orders' => $transactions,
            ],
        ], 200);
    }
}
