<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
 *
 * For now we only log what we receive so we can inspect the real payload
 * shape before wiring it into transaction state changes.
 */
class KiriminajaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $method = $request->input('method');
        $data = $request->input('data');

        Log::info('KiriminAja Webhook: received callback', [
            'method' => $method,
            'payload' => $request->all(),
        ]);

        switch ($method) {
            case 'processed_packages':
                Log::info('KiriminAja Webhook: processed_packages (AWB created)', ['data' => $data]);
                break;

            case 'shipped_packages':
                Log::info('KiriminAja Webhook: shipped_packages (picked up / in transit)', ['data' => $data]);
                break;

            case 'canceled_packages':
                Log::info('KiriminAja Webhook: canceled_packages (shipment canceled)', ['data' => $data]);
                break;

            case 'finished_packages':
                Log::info('KiriminAja Webhook: finished_packages (delivered)', ['data' => $data]);
                break;

            case 'returned_packages':
                Log::info('KiriminAja Webhook: returned_packages (returned to sender)', ['data' => $data]);
                break;

            default:
                Log::warning('KiriminAja Webhook: unknown method received', [
                    'method' => $method,
                    'payload' => $request->all(),
                ]);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ], 200);
    }
}
