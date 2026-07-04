<?php

namespace Tests\Feature\Api;

use App\Models\Outlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletsTest extends TestCase
{
    use RefreshDatabase;

    public function test_outlets_endpoint_returns_only_official_outlets(): void
    {
        Outlet::create([
            'id_outlet' => 'outlet-official-1',
            'type' => 'OFFICIAL',
            'name' => 'Official Outlet 1',
            'address' => 'Jl. Merdeka 1',
            'phone_number' => '081234567890',
        ]);

        Outlet::create([
            'id_outlet' => 'outlet-cabin-1',
            'type' => 'CABIN',
            'name' => 'Cabin Outlet',
            'address' => 'Jl. Merdeka 2',
            'phone_number' => '081234567891',
        ]);

        $response = $this->getJson('/api/outlets');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Official Outlet 1')
            ->assertJsonMissing([['name' => 'Cabin Outlet']]);
    }
}
