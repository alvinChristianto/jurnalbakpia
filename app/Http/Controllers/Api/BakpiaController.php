<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bakpia;
use App\Models\OlProduct;
use App\Models\Product;
use Illuminate\Http\Request;

class BakpiaController extends Controller
{
    /**
     * Display a listing of all products.
     */
    public function index()
    {
        // Fetch products that are active/available
        // We use paginate so your Next.js frontend doesn't lag if you have 100+ types of Bakpia
        $products = OlProduct::query()->latest()
            ->paginate(12);

        $randomDecimal = mt_rand(40, 50) / 10;
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
                'rating' => $product->rating,
                'status' => $product->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }
}
