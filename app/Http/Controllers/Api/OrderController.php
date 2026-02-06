<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validate the data coming from Next.js localStorage
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address_id' => 'required|exists:addresses,id',
        ]);

        return DB::transaction(function () use ($request) {
            // 2. Create the Order
            $order = Order::create([
                'user_id' => auth()->id(), // Assuming user is logged in via Sanctum
                'status' => 'pending',
                'order_number' => 'BAK-' . strtoupper(uniqid()),
                'total_amount' => 0, // We will calculate this
                'shipping_address_snapshot' => $request->address_id, // Simplified for now
            ]);

            // 3. Logic to move items from request to order_items table
            // [Insert logic to calculate totals and save items]

            // 4. Return the order info to Next.js
            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order
            ], 201);
        });
    }
}
