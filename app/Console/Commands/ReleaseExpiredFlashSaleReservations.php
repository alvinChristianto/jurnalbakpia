<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Models\OlEcommerceTransaction;
use App\Services\OlFlashSaleQuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredFlashSaleReservations extends Command
{
    protected $signature = 'flash-sales:release-expired';

    protected $description = 'Release flash-sale quota held by stale pending online orders (backstop for Midtrans expire webhooks)';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) config('order.flashsale_reservation_ttl', 15));

        $released = DB::transaction(function () use ($cutoff): int {
            $ids = OlEcommerceTransaction::query()
                ->where('status', TransactionStatus::PENDING->value)
                ->where('created_at', '<', $cutoff)
                ->whereHas('details', fn ($query) => $query->whereNotNull('flash_sale_item_id'))
                ->lockForUpdate()
                ->pluck('id');

            foreach ($ids as $id) {
                $claimed = OlEcommerceTransaction::query()
                    ->where('id', $id)
                    ->where('status', TransactionStatus::PENDING->value)
                    ->update(['status' => TransactionStatus::FAILED->value]);

                if ($claimed) {
                    OlFlashSaleQuotaService::releaseForTransaction(OlEcommerceTransaction::find($id));
                }
            }

            return $ids->count();
        });

        $this->info("Released {$released} expired flash-sale reservation(s).");

        return self::SUCCESS;
    }
}
