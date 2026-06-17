<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp messages through the Fonnte single-send API.
 *
 * Docs: https://docs.fonnte.com/send-whatsapp-message-with-php-api/#single
 * POST {FONNTE_BASE_URL}/send  with header `Authorization: <DEVICE_TOKEN>`
 * (the device token directly — NOT a `Bearer` prefix) and form body
 * `target` (phone) + `message`. Response JSON carries a `status` boolean.
 *
 * Never throws — every path returns a bool so webhook callers can wrap a
 * single try/catch and a Fonnte hiccup never breaks order/shipment flow.
 */
class FonnteService
{
    public function sendMessage(string $phone, string $message): bool
    {
        if (! config('fonnte.enabled') || ! config('fonnte.token')) {
            Log::info('Fonnte|sendMessage|skipped', [
                'reason' => ! config('fonnte.enabled') ? 'disabled' : 'no_token',
            ]);

            return false;
        }

        $target = $this->normalizePhone($phone);

        if (! $target) {
            Log::warning('Fonnte|sendMessage|empty_target', ['phone' => $phone]);

            return false;
        }

        Log::info('Fonnte|sendMessage|request', [
            'target' => $target,
            'message' => $message,
        ]);

        try {
            $response = Http::withHeaders(['Authorization' => config('fonnte.token')])
                ->asForm()
                ->timeout(15)
                ->post(config('fonnte.base_url').'/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

            $data = $response->json();

            $sent = $response->successful() && ($data['status'] ?? false);

            Log::info('Fonnte|sendMessage|response', [
                'target' => $target,
                'sent' => $sent,
                'status' => $response->status(),
                'response' => $data,
            ]);

            return $sent;
        } catch (\Throwable $e) {
            Log::error('Fonnte|sendMessage|exception', [
                'target' => $target,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Strip non-digits and convert a leading `0` to Indonesia's `62`
     * (stored `081234...` becomes `6281234...`, which Fonnte's target accepts).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }
}
