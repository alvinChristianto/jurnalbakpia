<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingPricingTest extends TestCase
{
    private function fakeUpstream(): void
    {
        Http::fake([
            '*/api/mitra/v6.1/shipping_price' => Http::response([
                'status' => true,
                'results' => [
                    [
                        'service' => 'jne',
                        'service_name' => 'JNE Express',
                        'service_type' => 'REG',
                        'cost' => '15000',
                        'etd' => '2-3',
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_returns_rate_list(): void
    {
        $this->fakeUpstream();

        $response = $this->postJson('/api/shipping/pricing', [
            'destination_kecamatan_id' => 1234,
            'destination_kelurahan_id' => 56789,
            'total_qty' => 2,
            'item_value' => 50000,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'results' => [['service', 'service_name', 'service_type', 'cost', 'etd']],
            ]);
    }

    public function test_validation_rejects_missing_fields(): void
    {
        $this->postJson('/api/shipping/pricing', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'destination_kecamatan_id',
                'destination_kelurahan_id',
                'total_qty',
                'item_value',
            ]);
    }

    public function test_sends_config_origin_and_computed_dimensions(): void
    {
        $this->fakeUpstream();

        $this->postJson('/api/shipping/pricing', [
            'destination_kecamatan_id' => 1234,
            'destination_kelurahan_id' => 56789,
            'total_qty' => 3,
            'item_value' => 75000,
        ])->assertOk();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/mitra/v6.1/shipping_price')
                && $body['origin'] === config('kiriminaja.origin_kecamatan_id')
                && $body['subdistrict_origin'] === config('kiriminaja.origin_kelurahan_id')
                && $body['destination'] === 1234
                && $body['subdistrict_destination'] === 56789
                && $body['weight'] === 3 * config('kiriminaja.box_weight_grams')
                && $body['height'] === 3 * config('kiriminaja.box_height_cm')
                && $body['length'] === config('kiriminaja.box_length_cm')
                && $body['width'] === config('kiriminaja.box_width_cm')
                && $body['item_value'] === 75000
                && $body['insurance'] === 1
                && $body['courier'] === ['jne', 'tiki'];
        });
    }

    public function test_upstream_failure_returns_502(): void
    {
        Http::fake([
            '*/api/mitra/v6.1/shipping_price' => Http::response([
                'status' => false,
                'text' => 'Invalid destination',
            ], 200),
        ]);

        $this->postJson('/api/shipping/pricing', [
            'destination_kecamatan_id' => 1234,
            'destination_kelurahan_id' => 56789,
            'total_qty' => 1,
            'item_value' => 10000,
        ])->assertStatus(502)
            ->assertJson(['success' => false]);
    }
}
