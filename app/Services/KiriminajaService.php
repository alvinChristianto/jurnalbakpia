<?php

namespace App\Services;

use App\Models\OlEcommerceTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KiriminajaService
{
    public function createExpressOrder(OlEcommerceTransaction $transaction): array
    {
        $snapshot = $transaction->shipping_address_snapshot;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }

        if (($snapshot['type'] ?? '') !== 'delivery') {
            throw new RuntimeException('KiriminAja pickup is only for delivery orders.');
        }

        $transaction->loadMissing(['details', 'olcustomer']);

        $totalQty = $transaction->details->sum('quantity');
        $weight   = max(1, $totalQty) * config('kiriminaja.box_weight_grams');
        $height   = max(1, $totalQty) * config('kiriminaja.box_height_cm');
        $length   = config('kiriminaja.box_length_cm');
        $width    = config('kiriminaja.box_width_cm');

        // Snapshot is a flat structure: kecamatan_id, kelurahan_id, fullAddress, etc.
        $destinationAddress = $snapshot['fullAddress'] ?? implode(', ', array_filter([
            $snapshot['street_detail']  ?? null,
            $snapshot['kelurahan_name'] ?? null,
            $snapshot['kecamatan_name'] ?? null,
            $snapshot['city_name']      ?? null,
            $snapshot['province_name']  ?? null,
        ]));

        $payload = [
            'name'         => config('kiriminaja.sender_name'),
            'phone'        => config('kiriminaja.sender_phone'),
            'address'      => config('kiriminaja.sender_address'),
            'kecamatan_id' => config('kiriminaja.origin_kecamatan_id'),
            'kelurahan_id' => config('kiriminaja.origin_kelurahan_id'),
            'schedule'     => now()->addHour()->format('Y-m-d H:i:s'),
            'packages'     => [
                [
                    'order_id'                  => $transaction->invoice_number,
                    'destination_name'          => $transaction->olcustomer->name,
                    'destination_phone'         => $transaction->olcustomer->phone_number,
                    'destination_address'       => $destinationAddress,
                    'destination_kecamatan_id'  => (int) ($snapshot['kecamatan_id'] ?? 0),
                    'destination_kelurahan_id'  => (int) ($snapshot['kelurahan_id'] ?? 0),
                    'destination_zipcode'       => $snapshot['postalCode'] ?? '',
                    'weight'                    => $weight,
                    'length'                    => $length,
                    'width'                     => $width,
                    'height'                    => $height,
                    'qty'                       => 1,
                    'item_value'                => (int) $transaction->subtotal,
                    'item_name'                 => 'Bakpia Master',
                    'shipping_cost'             => (int) $transaction->shipping_cost,
                    'service'                   => $snapshot['courier']['service']      ?? '',
                    'service_type'              => $snapshot['courier']['service_type'] ?? '',
                    'insurance_amount'          => 0,
                    'cod'                       => 0,
                    'package_type_id'           => 7,//lain-lain
                ],
            ],
        ];

        Log::info('KiriminAja|createExpressOrder|request', [
            'invoice' => $transaction->invoice_number,
            'payload' => $payload,
        ]);

        $response = Http::withToken(config('kiriminaja.api_key'))
            ->timeout(20)
            ->post(config('kiriminaja.base_url') . '/api/mitra/request_pickup', $payload);

        $data = $response->json();

        Log::info('KiriminAja|createExpressOrder|response', [
            'invoice'  => $transaction->invoice_number,
            'status'   => $response->status(),
            'response' => $data,
        ]);

        if (!$response->successful() || !($data['status'] ?? false)) {
            throw new RuntimeException(
                'KiriminAja API error: ' . ($data['text'] ?? $response->body())
            );
        }

        return $data;
    }
}
