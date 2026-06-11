<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\OlEcommerceTransaction;
use App\Models\OlShipmentEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles delivery status webhooks sent by KiriminAja.
 *
 * KiriminAja posts a JSON body shaped as { "method": <event>, "data": [...] }.
 * Supported events (see https://developer.kiriminaja.com/docs/webhook/event):
 *   - processed_packages : AWB created / package processed
 *   - shipped_packages   : package picked up / in transit
 *   - canceled_packages  : shipment canceled
 *   - finished_packages  : package delivered
 *   - returned_packages  : package returned to sender
 */
class KiriminajaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $method = $request->input('method');
        $data = $request->input('data', []);

        Log::info('KiriminAja Webhook: received callback', [
            'method' => $method,
            'payload' => $request->all(),
        ]);

        foreach ($data as $item) {
            $this->processItem($method, $item, $request->all());
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ], 200);
    }

    private function processItem(string $method, array $item, array $rawPayload): void
    {
        $invoiceNumber = $item['order_id'] ?? null;

        if (! $invoiceNumber) {
            Log::warning('KiriminAja Webhook: missing order_id in item', ['item' => $item]);

            return;
        }

        $eventAt = isset($item['date']) ? Carbon::parse($item['date']) : null;
        $shippedAt = isset($item['shipped_at']) ? Carbon::parse($item['shipped_at']) : null;
        $finishedAt = isset($item['finished_at']) ? Carbon::parse($item['finished_at']) : null;
        $returnedAt = isset($item['returned_at']) ? Carbon::parse($item['returned_at']) : null;

        $eventType = match ($method) {
            'processed_packages' => 'processed',
            'shipped_packages' => 'shipped',
            'finished_packages' => 'finished',
            'returned_packages' => 'returned',
            'canceled_packages' => 'canceled',
            default => $method,
        };

        OlShipmentEvent::create([
            'invoice_number' => $invoiceNumber,
            'event_type' => $eventType,
            'awb' => $item['awb'] ?? null,
            'event_at' => $eventAt,
            'shipped_at' => $shippedAt,
            'finished_at' => $finishedAt,
            'returned_at' => $returnedAt,
            'reason' => $item['reason'] ?? null,
            'raw_payload' => $rawPayload,
        ]);

        $transaction = OlEcommerceTransaction::where('invoice_number', $invoiceNumber)->first();

        if (! $transaction) {
            Log::warning('KiriminAja Webhook: no transaction found for invoice', [
                'invoice_number' => $invoiceNumber,
                'method' => $method,
            ]);

            return;
        }

        DB::transaction(function () use ($method, $transaction, $shippedAt, $finishedAt, $returnedAt) {
            match ($method) {
                'shipped_packages' => $transaction->update([
                    'status' => TransactionStatus::SHIPPING,
                    'shipped_at' => $shippedAt ?? now(),
                ]),
                'finished_packages' => $transaction->update([
                    'status' => TransactionStatus::COMPLETED,
                    'completed_at' => $finishedAt ?? now(),
                ]),
                'returned_packages' => $transaction->update([
                    'status' => TransactionStatus::RETURNED,
                    'returned_at' => $returnedAt ?? now(),
                ]),
                'canceled_packages' => $transaction->update([
                    'status' => TransactionStatus::CANCELLED,
                ]),
                default => null,
            };
        });

        Log::info('KiriminAja Webhook: transaction updated', [
            'invoice_number' => $invoiceNumber,
            'method' => $method,
            'status' => $transaction->fresh()->status,
        ]);
    }
}
