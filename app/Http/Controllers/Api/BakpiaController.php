<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OlProduct;
use App\Models\Outlet;
use Illuminate\Http\JsonResponse;

class BakpiaController extends Controller
{
    /**
     * Display a listing of all products.
     */
    public function index()
    {
        // Fetch products that are active/available
        // We use paginate so your Next.js frontend doesn't lag if you have 100+ types of Bakpia
        $products = OlProduct::where('status', 'ACTIVE')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(12);

        // Map the data to ensure full image URLs are returned
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'description' => $product->description,
                'price' => $product->price,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'category' => $product->category,
                'flavor' => $product->flavor,
                'is_featured' => $product->is_featured,
                'sort_order' => $product->sort_order,
                'rating' => $product->rating,
                'status' => $product->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ], 200);
    }

    public function outlets(): JsonResponse
    {
        $outlets = Outlet::official()->select([
            'id_outlet', 'name', 'address',
            'phone_number', 'operational_day', 'operational_hour',
        ])->get();

        return response()->json($outlets);
    }
}
