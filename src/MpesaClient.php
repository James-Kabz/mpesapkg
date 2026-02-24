<?php

namespace JamesKabz\MpesaPkg;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JamesKabz\MpesaPkg\Services\MpesaConfig;
use JamesKabz\MpesaPkg\Services\MpesaHelper;

class MpesaClient
{
    protected MpesaConfig $config;
    protected MpesaHelper $helper;

    public function __construct(MpesaConfig $config, ?MpesaHelper $helper = null)
    {
        $this->config = $config;
        $this->helper = $helper ?: app(MpesaHelper::class);
    }

    /**
     * Request OAuth access token using configured consumer key/secret.
     *
     * @return array<string, mixed>
     */
    public function getAccessToken(): array
    {
        $consumerKey = $this->config->consumerKey();
        $consumerSecret = $this->config->consumerSecret();

        if (! $consumerKey || ! $consumerSecret) {
            return $this->errorResponse('Missing MPESA_CONSUMER_KEY or MPESA_CONSUMER_SECRET.');
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/oauth/v1/generate';

        $response = Http::timeout(15)
            ->withBasicAuth($consumerKey, $consumerSecret)
            ->get($url, [
                'grant_type' => 'client_credentials',
            ]);

        return $this->formatHttpResponse($response);
    }

    /**
     * Initiate STK push for a customer payment.
     * Required payload: phone, amount
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function stkPush(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $this->config->stkShortCode();
        $passkey = $this->config->stkPasskey();

        if (! $shortCode || ! $passkey) {
            return $this->errorResponse('Missing MPESA_STK_SHORT_CODE or MPESA_STK_PASSKEY.');
        }

        $timestamp = $payload['timestamp'] ?? now()->format('YmdHis');
        $password = base64_encode($shortCode . $passkey . $timestamp);
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));
        $callbackUrl = $payload['callback_url']
            ?? $this->config->stkCallbackUrl()
            ?? $this->config->callbackStk();

        $data = [
            'BusinessShortCode' => $shortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $payload['transaction_type'] ?? ($this->config->stkTransactionType() ?? 'CustomerPayBillOnline'),
            'Amount' => $payload['amount'] ?? 1,
            'PartyA' => $phone,
            'PartyB' => $payload['party_b'] ?? $shortCode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl ?? '',
            'AccountReference' => $payload['account_reference'] ?? ($this->config->stkAccountReference() ?? 'Mpesa Test'),
            'TransactionDesc' => $payload['transaction_desc'] ?? ($this->config->stkTransactionDesc() ?? 'STK Push Test'),
        ];

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/stkpush/v1/processrequest';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Send B2C payment.
     * Required payload: phone, amount (config must include initiator/credential/shortcode)
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function b2c(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $this->config->b2cShortCode();
        $initiator = $this->config->b2cInitiatorName();
        $initiatorPassword = $this->config->b2cInitiatorPassword();
        $securityCredential = $this->config->b2cSecurityCredential();

        if (! $shortCode || ! $initiator || (! $initiatorPassword && ! $securityCredential)) {
            return $this->errorResponse('Missing MPESA_B2C_SHORT_CODE, MPESA_B2C_INITIATOR, and either MPESA_B2C_INITIATOR_PASSWORD or MPESA_B2C_SECURITY_CREDENTIAL.');
        }

        if ($initiatorPassword) {
            try {
                $securityCredential = $this->helper->generateSecurityCredential($initiatorPassword);
            } catch (\Throwable $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));
        $resultUrl = $this->config->b2cResultUrl() ?? $this->config->callbackB2cResult();
        $timeoutUrl = $this->config->b2cTimeoutUrl() ?? $this->config->callbackB2cTimeout();

        $data = [
            'InitiatorName' => $initiator,
            'SecurityCredential' => $securityCredential,
            'CommandID' => $this->config->b2cCommandId() ?? 'BusinessPayment',
            'Amount' => $payload['amount'] ?? 1,
            'PartyA' => $shortCode,
            'PartyB' => $phone,
            'Remarks' => $payload['remarks'] ?? 'B2C Payment',
            'QueueTimeOutURL' => $timeoutUrl ?? '',
            'ResultURL' => $resultUrl ?? '',
            'Occasion' => $payload['occasion'] ?? 'Mpesa Test',
            'OriginatorConversationID' => $payload['originator_conversation_id'] ?? (string) Str::uuid(),
        ];

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $b2cPath = $this->config->env() === 'production'
            ? '/mpesa/b2c/v1/paymentrequest'
            : '/mpesa/b2c/v3/paymentrequest';
        $url = $baseUrl . $b2cPath;

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Send B2C payment with ID validation.
     * Required payload: phone, amount, remarks, id_number
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validatedB2c(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $this->config->b2cShortCode();
        $initiator = $this->config->b2cInitiatorName();
        $initiatorPassword = $this->config->b2cInitiatorPassword();
        $securityCredential = $this->config->b2cSecurityCredential();

        if (! $shortCode || ! $initiator || (! $initiatorPassword && ! $securityCredential)) {
            return $this->errorResponse('Missing MPESA_B2C_SHORT_CODE, MPESA_B2C_INITIATOR, and either MPESA_B2C_INITIATOR_PASSWORD or MPESA_B2C_SECURITY_CREDENTIAL.');
        }

        if (empty($payload['id_number'])) {
            return $this->errorResponse('Missing id_number for validated B2C.');
        }

        if ($initiatorPassword) {
            try {
                $securityCredential = $this->helper->generateSecurityCredential($initiatorPassword);
            } catch (\Throwable $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));
        $resultUrl = $this->config->b2cResultUrl() ?? $this->config->callbackB2cResult();
        $timeoutUrl = $this->config->b2cTimeoutUrl() ?? $this->config->callbackB2cTimeout();

        $data = [
            'InitiatorName' => $initiator,
            'SecurityCredential' => $securityCredential,
            'CommandID' => $payload['command_id'] ?? ($this->config->b2cCommandId() ?? 'BusinessPayment'),
            'Amount' => $payload['amount'] ?? 1,
            'PartyA' => $shortCode,
            'PartyB' => $phone,
            'Remarks' => $payload['remarks'] ?? 'B2C Payment',
            'Occasion' => $payload['occasion'] ?? '',
            'OriginatorConversationID' => $payload['originator_conversation_id'] ?? (string) Str::uuid(),
            'IDType' => $payload['id_type'] ?? '01',
            'IDNumber' => $payload['id_number'],
            'ResultURL' => $resultUrl ?? '',
            'QueueTimeOutURL' => $timeoutUrl ?? '',
        ];

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/b2cvalidate/v2/paymentrequest';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Register C2B validation and confirmation URLs.
     * Required payload: short_code, confirmation_url, validation_url
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function c2bRegisterUrls(array $payload = []): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $payload['short_code'] ?? $this->config->c2bShortCode();
        $confirmationUrl = $payload['confirmation_url'] ?? $this->config->c2bConfirmationUrl() ?? $this->config->callbackC2bConfirmation();
        $validationUrl = $payload['validation_url'] ?? $this->config->c2bValidationUrl() ?? $this->config->callbackC2bValidation();
        $responseType = $payload['response_type'] ?? ($this->config->c2bResponseType() ?? 'Completed');

        if (! $shortCode || ! $confirmationUrl || ! $validationUrl) {
            return $this->errorResponse('Missing C2B short code, confirmation_url, or validation_url.');
        }

        $data = [
            'ShortCode' => $shortCode,
            'ResponseType' => $responseType,
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl,
        ];

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/c2b/v2/registerurl';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Simulate C2B payment (sandbox).
     * Required payload: phone, amount, short_code, command_id
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function c2bSimulate(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $payload['short_code'] ?? $this->config->c2bShortCode();
        $commandId = $payload['command_id'] ?? 'CustomerPayBillOnline';
        $phone = $this->normalizePhone((string) ($payload['phone'] ?? ''));

        if (! $shortCode) {
            return $this->errorResponse('Missing C2B short code.');
        }

        $data = [
            'ShortCode' => $shortCode,
            'CommandID' => $commandId,
            'Amount' => (int) ($payload['amount'] ?? 1),
            'Msisdn' => $phone,
        ];

        if (! empty($payload['bill_ref_number'])) {
            $data['BillRefNumber'] = $payload['bill_ref_number'];
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/c2b/v1/simulate';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Query STK push status using CheckoutRequestID.
     * Required payload: checkout_request_id
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function stkQuery(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        $shortCode = $this->config->stkShortCode();
        $passkey = $this->config->stkPasskey();

        if (! $shortCode || ! $passkey) {
            return $this->errorResponse('Missing MPESA_STK_SHORT_CODE or MPESA_STK_PASSKEY.');
        }

        if (empty($payload['checkout_request_id'])) {
            return $this->errorResponse('Missing checkout_request_id.');
        }

        $timestamp = $payload['timestamp'] ?? now()->format('YmdHis');
        $password = base64_encode($shortCode . $passkey . $timestamp);

        $data = [
            'BusinessShortCode' => $shortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $payload['checkout_request_id'],
        ];

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/stkpushquery/v1/query';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Query a transaction status.
     * Required payload: short_code, transaction_id, identifier_type, remarks
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function transactionStatus(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        if (empty($payload['short_code']) || empty($payload['transaction_id']) || empty($payload['identifier_type']) || empty($payload['remarks'])) {
            return $this->errorResponse('Missing short_code, transaction_id, identifier_type, or remarks.');
        }

        $resultUrl = $payload['result_url'] ?? $this->config->callbackTransactionStatusResult();
        $timeoutUrl = $payload['timeout_url'] ?? $this->config->callbackTransactionStatusTimeout();

        $initiatorName = $payload['initiator_name'] ?? $this->config->b2cInitiatorName();
        $securityCredential = $payload['security_credential'] ?? $this->config->b2cSecurityCredential();
        $initiatorPassword = $this->config->b2cInitiatorPassword();

        if (! $securityCredential && $initiatorPassword) {
            try {
                $securityCredential = $this->helper->generateSecurityCredential($initiatorPassword);
            } catch (\Throwable $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $data = [
            'Initiator' => $initiatorName,
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $payload['transaction_id'],
            'PartyA' => $payload['short_code'],
            'IdentifierType' => $payload['identifier_type'],
            'Remarks' => $payload['remarks'],
            'Occasion' => $payload['occasion'] ?? '',
            'ResultURL' => $resultUrl ?? '',
            'QueueTimeOutURL' => $timeoutUrl ?? '',
        ];

        if (empty($data['Initiator']) || empty($data['SecurityCredential'])) {
            return $this->errorResponse('Missing initiator_name or security_credential for transaction status query.');
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/transactionstatus/v1/query';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Query account balance.
     * Required payload: short_code, identifier_type, remarks
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function accountBalance(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        if (empty($payload['short_code']) || empty($payload['identifier_type']) || empty($payload['remarks'])) {
            return $this->errorResponse('Missing short_code, identifier_type, or remarks.');
        }

        $resultUrl = $payload['result_url'] ?? $this->config->callbackAccountBalanceResult();
        $timeoutUrl = $payload['timeout_url'] ?? $this->config->callbackAccountBalanceTimeout();

        $initiatorName = $payload['initiator_name'] ?? $this->config->b2cInitiatorName();
        $securityCredential = $payload['security_credential'] ?? $this->config->b2cSecurityCredential();
        $initiatorPassword = $this->config->b2cInitiatorPassword();

        if (! $securityCredential && $initiatorPassword) {
            try {
                $securityCredential = $this->helper->generateSecurityCredential($initiatorPassword);
            } catch (\Throwable $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $data = [
            'Initiator' => $initiatorName,
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'AccountBalance',
            'PartyA' => $payload['short_code'],
            'IdentifierType' => $payload['identifier_type'],
            'Remarks' => $payload['remarks'],
            'ResultURL' => $resultUrl ?? '',
            'QueueTimeOutURL' => $timeoutUrl ?? '',
        ];

        if (empty($data['Initiator']) || empty($data['SecurityCredential'])) {
            return $this->errorResponse('Missing initiator_name or security_credential for account balance.');
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/accountbalance/v1/query';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Reverse a transaction.
     * Required payload: short_code, transaction_id, amount, remarks
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function reversal(array $payload): array
    {
        $tokenResult = $this->getAccessToken();
        $accessToken = $tokenResult['data']['access_token'] ?? null;

        if (! $tokenResult['ok'] || ! $accessToken) {
            return $this->errorResponse('Failed to get access token.', $tokenResult['status'] ?? 400);
        }

        if (empty($payload['short_code']) || empty($payload['transaction_id']) || empty($payload['amount']) || empty($payload['remarks'])) {
            return $this->errorResponse('Missing short_code, transaction_id, amount, or remarks.');
        }

        $resultUrl = $payload['result_url'] ?? $this->config->callbackReversalResult();
        $timeoutUrl = $payload['timeout_url'] ?? $this->config->callbackReversalTimeout();

        $initiatorName = $payload['initiator_name'] ?? $this->config->b2cInitiatorName();
        $securityCredential = $payload['security_credential'] ?? $this->config->b2cSecurityCredential();
        $initiatorPassword = $this->config->b2cInitiatorPassword();

        if (! $securityCredential && $initiatorPassword) {
            try {
                $securityCredential = $this->helper->generateSecurityCredential($initiatorPassword);
            } catch (\Throwable $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $data = [
            'Initiator' => $initiatorName,
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $payload['transaction_id'],
            'Amount' => $payload['amount'],
            'ReceiverParty' => $payload['short_code'],
            'RecieverIdentifierType' => $payload['identifier_type'] ?? '11',
            'Remarks' => $payload['remarks'],
            'Occasion' => $payload['occasion'] ?? '',
            'ResultURL' => $resultUrl ?? '',
            'QueueTimeOutURL' => $timeoutUrl ?? '',
        ];

        if (empty($data['Initiator']) || empty($data['SecurityCredential'])) {
            return $this->errorResponse('Missing initiator_name or security_credential for reversal.');
        }

        $baseUrl = rtrim($this->config->baseUrl(), '/');
        $url = $baseUrl . '/mpesa/reversal/v1/request';

        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($url, $data);

        return $this->formatHttpResponse($response);
    }

    /**
     * Normalize phone number to 2547XXXXXXXX format.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254' . substr($digits, 1);
        }
        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '254' . $digits;
        }
        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Build a standardized error response.
     */
    private function errorResponse(string $message, int $status = 400): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => $message,
            'body' => null,
        ];
    }

    /**
     * Build a standardized response from an HTTP client response.
     */
    private function formatHttpResponse($response): array
    {
        $json = $response->json();
        $data = is_array($json) ? $json : null;
        $error = null;

        if (! $response->successful()) {
            $error = $data['errorMessage'] ?? $data['error'] ?? $data['message'] ?? null;
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => $data,
            'error' => $error,
            'body' => $response->successful() ? null : $response->body(),
        ];
    }
}
