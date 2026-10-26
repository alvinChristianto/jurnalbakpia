<?php

namespace Tests\Feature\Api;

use App\Models\OlProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_returns_paginated_active_products(): void
    {
        OlProduct::create([
            'name' => 'Bakpia Kacang Hijau',
            'price' => 25000,
            'description' => 'Bakpia klasik isi kacang hijau',
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
            'flavor' => 'Kacang',
            'is_featured' => true,
        ]);
        OlProduct::create([
            'name' => 'Bakpia Coklat',
            'price' => 30000,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);
        OlProduct::create([
            'name' => 'Hidden Product',
            'price' => 1000,
            'category' => 'OTHER',
            'status' => 'INACTIVE',
        ]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_page',
                    'data' => [['id', 'name', 'price', 'category', 'status', 'flavor', 'is_featured', 'sort_order']],
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.total', 2);
    }

    public function test_products_endpoint_includes_featured_product(): void
    {
        OlProduct::create([
            'name' => 'Bakpia Kacang Hijau',
            'price' => 25000,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
            'flavor' => 'Kacang',
            'is_featured' => true,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $featured = collect($response->json('data.data'))->firstWhere('is_featured', true);
        $this->assertNotNull($featured);
        $this->assertSame('Kacang', $featured['flavor']);
    }

    public function test_products_alias_route_also_works(): void
    {
        $this->getJson('/api/bakpias')->assertOk();
    }

    public function test_products_endpoint_returns_empty_data_when_no_products(): void
    {
        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.data', []);
    }

    public function test_products_ids_filter_returns_matching_active_products_with_discount_fields(): void
    {
        $active = OlProduct::create([
            'name' => 'Bakpia Kacang Hijau',
            'price' => 25000,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);
        $inactive = OlProduct::create([
            'name' => 'Hidden Product',
            'price' => 1000,
            'category' => 'OTHER',
            'status' => 'INACTIVE',
        ]);

        $response = $this->getJson("/api/products?ids={$active->id},{$inactive->id}");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $active->id)
            ->assertJsonPath('data.data.0.price', 25000)
            ->assertJsonPath('data.data.0.original_price', null)
            ->assertJsonPath('data.data.0.discount_type', null)
            ->assertJsonPath('data.data.0.discount_source', null);
    }

    public function test_products_ids_filter_rejects_more_than_50_ids(): void
    {
        $ids = implode(',', range(1, 51));

        $this->getJson("/api/products?ids={$ids}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('ids');
    }

    public function test_products_ids_filter_without_value_falls_back_to_listing(): void
    {
        OlProduct::create([
            'name' => 'Bakpia Kacang Hijau',
            'price' => 25000,
            'category' => 'BAKPIA',
            'status' => 'ACTIVE',
        ]);

        $this->getJson('/api/products?ids=')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }
}
