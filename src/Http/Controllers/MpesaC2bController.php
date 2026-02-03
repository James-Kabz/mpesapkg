<?php

namespace JamesKabz\MpesaPkg\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JamesKabz\MpesaPkg\Http\Concerns\ValidatesWebhook;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Models\MpesaCallback;

class MpesaC2bController
{
    use ValidatesWebhook;

    /**
     * Register C2B validation and confirmation URLs.
     */
    public function register(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'short_code' => ['nullable', 'string', 'max:20'],
            'confirmation_url' => ['nullable', 'url'],
            'validation_url' => ['nullable', 'url'],
            'response_type' => ['nullable', 'string', 'max:20'],
        ]);

        $result = $client->c2bRegisterUrls($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Simulate a C2B transaction (sandbox).
     */
    public function simulate(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'short_code' => ['nullable', 'string', 'max:20'],
            'command_id' => ['nullable', 'string', 'max:50'],
            'bill_ref_number' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $client->c2bSimulate($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle C2B validation callback and persist if enabled.
     */
    public function validation(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa C2B validation received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'c2b_validation',
                    'result_code' => data_get($payload, 'ResultCode'),
                    'result_desc' => data_get($payload, 'ResultDesc'),
                    'transaction_id' => data_get($payload, 'TransID'),
                    'mpesa_receipt_number' => data_get($payload, 'TransID'),
                    'bill_ref_number' => data_get($payload, 'BillRefNumber'),
                    'amount' => data_get($payload, 'TransAmount'),
                    'phone' => data_get($payload, 'MSISDN'),
                    'party_a' => data_get($payload, 'MSISDN'),
                    'party_b' => data_get($payload, 'BusinessShortCode'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist C2B validation callback', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Handle C2B confirmation callback and persist if enabled.
     */
    public function confirmation(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa C2B confirmation received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'c2b_confirmation',
                    'result_code' => data_get($payload, 'ResultCode'),
                    'result_desc' => data_get($payload, 'ResultDesc'),
                    'transaction_id' => data_get($payload, 'TransID'),
                    'mpesa_receipt_number' => data_get($payload, 'TransID'),
                    'bill_ref_number' => data_get($payload, 'BillRefNumber'),
                    'amount' => data_get($payload, 'TransAmount'),
                    'phone' => data_get($payload, 'MSISDN'),
                    'party_a' => data_get($payload, 'MSISDN'),
                    'party_b' => data_get($payload, 'BusinessShortCode'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist C2B confirmation callback', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
