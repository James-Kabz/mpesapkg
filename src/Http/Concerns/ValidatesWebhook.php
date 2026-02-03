<?php

namespace JamesKabz\MpesaPkg\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JamesKabz\MpesaPkg\Services\MpesaConfig;

trait ValidatesWebhook
{
    /**
     * Validate webhook token/IP allow-list when enabled.
     * Returns a JsonResponse on failure or null on success.
     */
    protected function validateWebhook(Request $request): ?JsonResponse
    {
        $config = app(MpesaConfig::class);

        if (! $config->webhookValidationEnabled()) {
            return null;
        }

        $header = $config->webhookValidationHeader();
        $token = $config->webhookValidationToken();
        $allowedIps = $config->webhookValidationAllowedIps();

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
