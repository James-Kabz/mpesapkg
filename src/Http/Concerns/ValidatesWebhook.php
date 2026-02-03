<?php

namespace JamesKabz\MpesaPkg\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ValidatesWebhook
{
    /**
     * Validate webhook token/IP allow-list when enabled.
     * Returns a JsonResponse on failure or null on success.
     */
    protected function validateWebhook(Request $request): ?JsonResponse
    {
        if (! config('mpesa.webhook_validation.enabled', false)) {
            return null;
        }

        $header = config('mpesa.webhook_validation.header', 'X-Mpesa-Token');
        $token = config('mpesa.webhook_validation.token');
        $allowedIps = config('mpesa.webhook_validation.allowed_ips', []);

        if ($token && $request->header($header) !== $token) {
            return response()->json([
                'ok' => false,
                'status' => 403,
                'data' => null,
                'error' => 'Invalid webhook token.',
            ], 403);
        }

        if (! empty($allowedIps)) {
            $ip = $request->ip();
            if (! in_array($ip, $allowedIps, true)) {
                return response()->json([
                    'ok' => false,
                    'status' => 403,
                    'data' => null,
                    'error' => 'Webhook IP not allowed.',
                ], 403);
            }
        }

        return null;
    }
}
