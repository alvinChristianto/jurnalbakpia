<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OlFlashSale;
use App\Services\OlPricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FlashSaleController extends Controller
{
    public function active(): JsonResponse
    {
        $sale = Cache::remember(
            'flash-sales:active',
            (int) config('order.flashsale_cache_ttl', 30),
            fn () => $this->queryActiveSale(),
        );

        if (! $sale) {
            return response()->json([
                'success' => true,
                'message' => 'No active flash sale',
                'data' => null,
            ]);
        }

        $pricing = new OlPricingService;
        $effective = $pricing->effectivePrices($sale->items->pluck('product'));

        $items = $sale->items->map(function ($item) use ($effective) {
            $price = $effective->get($item->product_id);
            $quota = $item->quota;
            $soldCount = $item->sold_count;
            $soldOut = $quota !== null && $soldCount >= $quota;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'image' => $item->product->image,
                'price' => $price->finalPrice,
                'original_price' => $price->originalPrice,
                'discount_type' => $price->discountType->value,
                'discount_label' => $price->discountLabel(),
                'discount_source' => $price->source,
                'sold_count' => $soldCount,
                'quota' => $quota,
                'sold_out' => $soldOut,
                'progress_percent' => $quota !== null && $quota > 0
                    ? min(100, (int) floor($soldCount * 100 / $quota))
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Active flash sale retrieved successfully',
            'data' => [
                'id' => $sale->id,
                'name' => $sale->name,
                'banner_url' => $sale->banner_url,
                'banner_alt' => $sale->banner_alt,
                'banner_rect_url' => $sale->banner_rect_url,
                'banner_rect_alt' => $sale->banner_rect_alt,
                'start_at' => $sale->start_at->toISOString(),
                'end_at' => $sale->end_at->toISOString(),
                'server_time' => Carbon::now()->toISOString(),
                'remaining_seconds' => max(0, now()->diffInSeconds($sale->end_at, false)),
                'items' => $items->all(),
            ],
        ]);
    }

    public function upcoming(): JsonResponse
    {
        $sales = OlFlashSale::query()
            ->where('status', 'published')
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Upcoming flash sales retrieved successfully',
            'data' => $sales->map(fn (OlFlashSale $sale) => [
                'id' => $sale->id,
                'name' => $sale->name,
                'banner_url' => $sale->banner_url,
                'banner_alt' => $sale->banner_alt,
                'banner_rect_url' => $sale->banner_rect_url,
                'banner_rect_alt' => $sale->banner_rect_alt,
                'start_at' => $sale->start_at->toISOString(),
                'end_at' => $sale->end_at->toISOString(),
            ]),
        ]);
    }

    private function queryActiveSale(): ?OlFlashSale
    {
        return OlFlashSale::query()
            ->where('status', 'published')
            ->where('start_at', '<=', now())
            ->where('end_at', '>', now())
            ->with('items.product')
            ->first();
    }
}
