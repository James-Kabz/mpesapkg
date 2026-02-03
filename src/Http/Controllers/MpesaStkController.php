<?php

namespace JamesKabz\MpesaPkg\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Http\Concerns\ValidatesWebhook;
use JamesKabz\MpesaPkg\Models\MpesaCallback;
use JamesKabz\MpesaPkg\Models\MpesaRequest;

class MpesaStkController
{
    use ValidatesWebhook;

    /**
     * Initiate STK push and persist request if enabled.
     */
    public function push(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'account_reference' => ['nullable', 'string', 'max:50'],
            'transaction_desc' => ['nullable', 'string', 'max:200'],
            'callback_url' => ['nullable', 'url'],
            'transaction_type' => ['nullable', 'string', 'max:50'],
            'party_b' => ['nullable', 'string', 'max:20'],
        ]);

        $result = $client->stkPush([
            'phone' => $data['phone'],
            'amount' => $data['amount'],
            'account_reference' => $data['account_reference'] ?? null,
            'transaction_desc' => $data['transaction_desc'] ?? null,
            'callback_url' => $data['callback_url'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? null,
            'party_b' => $data['party_b'] ?? null,
        ]);

        if (config('mpesa.store_requests', true)) {
            try {
                $stkConfig = config('mpesa.credentials.stk', []);
                MpesaRequest::create([
                    'type' => 'stk',
                    'status' => $result['ok'] ? 'pending' : 'failed',
                    'phone' => $data['phone'],
                    'amount' => $data['amount'],
                    'party_a' => $data['phone'],
                    'party_b' => $data['party_b'] ?? ($stkConfig['short_code'] ?? null),
                    'command_id' => $data['transaction_type'] ?? null,
                    'bill_ref_number' => $data['account_reference'] ?? null,
                    'merchant_request_id' => data_get($result, 'data.MerchantRequestID'),
                    'checkout_request_id' => data_get($result, 'data.CheckoutRequestID'),
                    'response_code' => data_get($result, 'data.ResponseCode'),
                    'response_description' => data_get($result, 'data.ResponseDescription'),
                    'request_payload' => [
                        'phone' => $data['phone'],
                        'amount' => $data['amount'],
                        'account_reference' => $data['account_reference'] ?? null,
                        'transaction_desc' => $data['transaction_desc'] ?? null,
                        'callback_url' => $data['callback_url'] ?? null,
                        'transaction_type' => $data['transaction_type'] ?? null,
                        'party_b' => $data['party_b'] ?? null,
                    ],
                    'response_payload' => $result['data'] ?? $result,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist STK request', ['error' => $e->getMessage()]);
            }
        }

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle STK callback and persist if enabled.
     */
    public function callback(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa STK callback received', $request->all());
        $payload = $request->all();
        $stkCallback = data_get($payload, 'Body.stkCallback');

        if (! is_array($stkCallback) || data_get($stkCallback, 'ResultCode') === null) {
            Log::warning('M-Pesa STK callback missing required fields', [
                'payload' => $payload,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }

        $metadata = data_get($stkCallback, 'CallbackMetadata.Item', []);
        $metadataItems = collect($metadata);
        $receipt = $metadataItems->firstWhere('Name', 'MpesaReceiptNumber');

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'stk',
                    'result_code' => data_get($stkCallback, 'ResultCode'),
                    'result_desc' => data_get($stkCallback, 'ResultDesc'),
                    'originator_conversation_id' => data_get($stkCallback, 'MerchantRequestID'),
                    'conversation_id' => data_get($stkCallback, 'CheckoutRequestID'),
                    'transaction_id' => is_array($receipt) ? ($receipt['Value'] ?? null) : null,
                    'merchant_request_id' => data_get($stkCallback, 'MerchantRequestID'),
                    'checkout_request_id' => data_get($stkCallback, 'CheckoutRequestID'),
                    'mpesa_receipt_number' => is_array($receipt) ? ($receipt['Value'] ?? null) : null,
                    'amount' => data_get($metadataItems->firstWhere('Name', 'Amount'), 'Value'),
                    'phone' => data_get($metadataItems->firstWhere('Name', 'PhoneNumber'), 'Value'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist STK callback', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Query STK push status using CheckoutRequestID.
     */
    public function query(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'checkout_request_id' => ['required', 'string'],
            'timestamp' => ['nullable', 'string'],
        ]);

        $result = $client->stkQuery($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }
}
