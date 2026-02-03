<?php

namespace JamesKabz\MpesaPkg\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JamesKabz\MpesaPkg\Http\Concerns\ValidatesWebhook;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Models\MpesaCallback;

class MpesaUtilityController
{
    use ValidatesWebhook;

    /**
     * Initiate transaction status query.
     */
    public function transactionStatus(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'short_code' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'identifier_type' => ['required', 'string'],
            'remarks' => ['required', 'string', 'max:200'],
            'result_url' => ['nullable', 'url'],
            'timeout_url' => ['nullable', 'url'],
            'occasion' => ['nullable', 'string', 'max:200'],
            'initiator_name' => ['nullable', 'string', 'max:50'],
            'security_credential' => ['nullable', 'string'],
        ]);

        $result = $client->transactionStatus($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle transaction status result callback.
     */
    public function transactionStatusResult(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa transaction status result received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'transaction_status_result',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist transaction status result', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Handle transaction status timeout callback.
     */
    public function transactionStatusTimeout(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa transaction status timeout received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'transaction_status_timeout',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist transaction status timeout', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Initiate account balance query.
     */
    public function accountBalance(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'short_code' => ['required', 'string'],
            'identifier_type' => ['required', 'string'],
            'remarks' => ['required', 'string', 'max:200'],
            'result_url' => ['nullable', 'url'],
            'timeout_url' => ['nullable', 'url'],
            'initiator_name' => ['nullable', 'string', 'max:50'],
            'security_credential' => ['nullable', 'string'],
        ]);

        $result = $client->accountBalance($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle account balance result callback.
     */
    public function accountBalanceResult(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa account balance result received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'account_balance_result',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist account balance result', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Handle account balance timeout callback.
     */
    public function accountBalanceTimeout(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa account balance timeout received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'account_balance_timeout',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist account balance timeout', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Initiate a reversal.
     */
    public function reversal(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'short_code' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'remarks' => ['required', 'string', 'max:200'],
            'result_url' => ['nullable', 'url'],
            'timeout_url' => ['nullable', 'url'],
            'occasion' => ['nullable', 'string', 'max:200'],
            'identifier_type' => ['nullable', 'string', 'max:5'],
            'initiator_name' => ['nullable', 'string', 'max:50'],
            'security_credential' => ['nullable', 'string'],
        ]);

        $result = $client->reversal($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle reversal result callback.
     */
    public function reversalResult(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa reversal result received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'reversal_result',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist reversal result', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Handle reversal timeout callback.
     */
    public function reversalTimeout(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa reversal timeout received', $request->all());
        $payload = $request->all();

        if (config('mpesa.store_callbacks', true)) {
            try {
                MpesaCallback::create([
                    'type' => 'reversal_timeout',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist reversal timeout', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
