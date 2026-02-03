<?php

namespace JamesKabz\MpesaPkg\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Http\Concerns\ValidatesWebhook;
use JamesKabz\MpesaPkg\Models\MpesaCallback;
use JamesKabz\MpesaPkg\Models\MpesaRequest;
use JamesKabz\MpesaPkg\Services\MpesaConfig;

class MpesaB2cController
{
    use ValidatesWebhook;

    protected MpesaConfig $config;

    public function __construct(MpesaConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Send a B2C payment and persist request if enabled.
     */
    public function send(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'remarks' => ['nullable', 'string', 'max:200'],
            'occasion' => ['nullable', 'string', 'max:200'],
            'originator_conversation_id' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $client->b2c([
            'phone' => $data['phone'],
            'amount' => $data['amount'],
            'remarks' => $data['remarks'] ?? null,
            'occasion' => $data['occasion'] ?? null,
            'originator_conversation_id' => $data['originator_conversation_id'] ?? null,
        ]);

        if ($this->config->storeRequests()) {
            try {
                MpesaRequest::create([
                    'type' => 'b2c',
                    'status' => $result['ok'] ? 'pending' : 'failed',
                    'phone' => $data['phone'],
                    'amount' => $data['amount'],
                    'remarks' => $data['remarks'] ?? null,
                    'command_id' => $this->config->b2cCommandId(),
                    'party_a' => $this->config->b2cShortCode(),
                    'party_b' => $data['phone'],
                    'originator_conversation_id' => data_get($result, 'data.OriginatorConversationID'),
                    'conversation_id' => data_get($result, 'data.ConversationID'),
                    'response_code' => data_get($result, 'data.ResponseCode'),
                    'response_description' => data_get($result, 'data.ResponseDescription'),
                    'transaction_id' => data_get($result, 'data.TransactionID'),
                    'request_payload' => [
                        'phone' => $data['phone'],
                        'amount' => $data['amount'],
                        'remarks' => $data['remarks'] ?? null,
                        'occasion' => $data['occasion'] ?? null,
                        'originator_conversation_id' => $data['originator_conversation_id'] ?? null,
                    ],
                    'response_payload' => $result['data'] ?? $result,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist B2C request', ['error' => $e->getMessage()]);
            }
        }

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Send a B2C payment with ID validation.
     */
    public function validated(Request $request, MpesaClient $client): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'remarks' => ['required', 'string', 'max:200'],
            'id_number' => ['required', 'string', 'max:20'],
            'id_type' => ['nullable', 'string', 'max:5'],
            'occasion' => ['nullable', 'string', 'max:200'],
            'originator_conversation_id' => ['nullable', 'string', 'max:100'],
            'command_id' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $client->validatedB2c($data);

        return response()->json($result, $result['status'] ?? ($result['ok'] ? 200 : 400));
    }

    /**
     * Handle B2C result callback and persist if enabled.
     */
    public function result(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa B2C result received', $request->all());
        $payload = $request->all();

        if ($this->config->storeCallbacks()) {
            try {
                MpesaCallback::create([
                    'type' => 'b2c_result',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist B2C result callback', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Handle B2C timeout callback and persist if enabled.
     */
    public function timeout(Request $request): JsonResponse
    {
        if ($response = $this->validateWebhook($request)) {
            return $response;
        }

        Log::info('M-Pesa B2C timeout received', $request->all());
        $payload = $request->all();

        if ($this->config->storeCallbacks()) {
            try {
                MpesaCallback::create([
                    'type' => 'b2c_timeout',
                    'result_code' => data_get($payload, 'Result.ResultCode'),
                    'result_desc' => data_get($payload, 'Result.ResultDesc'),
                    'originator_conversation_id' => data_get($payload, 'Result.OriginatorConversationID'),
                    'conversation_id' => data_get($payload, 'Result.ConversationID'),
                    'transaction_id' => data_get($payload, 'Result.TransactionID'),
                    'party_a' => data_get($payload, 'Result.OriginatorConversationID'),
                    'party_b' => data_get($payload, 'Result.ConversationID'),
                    'payload' => $payload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to persist B2C timeout callback', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }
}
