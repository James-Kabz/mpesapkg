<?php

namespace JamesKabz\MpesaPkg\Tests\Unit;

use Illuminate\Config\Repository as ConfigRepository;
use JamesKabz\MpesaPkg\Services\MpesaConfig;
use PHPUnit\Framework\TestCase;

class MpesaConfigTest extends TestCase
{
    public function test_typed_getters(): void
    {
        $configRepo = new ConfigRepository([
            'mpesa' => [
                'route_prefix' => 'api/payments',
                'route_middleware' => ['api', 'throttle:60,1'],
                'store_requests' => false,
                'store_callbacks' => true,
                'env' => 'sandbox',
                'base_url' => 'https://sandbox.safaricom.co.ke',
                'cert_paths' => [
                    'sandbox' => '/tmp/sandbox.cer',
                    'production' => '/tmp/prod.cer',
                ],
                'consumer_key' => 'key',
                'consumer_secret' => 'secret',
                'credentials' => [
                    'stk' => [
                        'short_code' => '123456',
                        'passkey' => 'pass',
                        'callback_url' => 'https://example.com/stk-inline',
                        'transaction_type' => 'CustomerPayBillOnline',
                        'account_reference' => 'REF',
                        'transaction_desc' => 'DESC',
                    ],
                    'b2c' => [
                        'initiator_name' => 'initiator',
                        'initiator_password' => 'pass123',
                        'security_credential' => 'sec',
                        'short_code' => '600000',
                        'command_id' => 'BusinessPayment',
                        'result_url' => 'https://example.com/b2c/result',
                        'timeout_url' => 'https://example.com/b2c/timeout',
                    ],
                    'c2b' => [
                        'short_code' => '600111',
                        'response_type' => 'Completed',
                        'validation_url' => 'https://example.com/c2b/validation',
                        'confirmation_url' => 'https://example.com/c2b/confirmation',
                    ],
                ],
                'callbacks' => [
                    'stk' => 'https://example.com/stk',
                    'b2c_result' => 'https://example.com/cb/b2c/result',
                    'b2c_timeout' => 'https://example.com/cb/b2c/timeout',
                    'c2b_validation' => 'https://example.com/cb/c2b/validation',
                    'c2b_confirmation' => 'https://example.com/cb/c2b/confirmation',
                    'transaction_status_result' => 'https://example.com/cb/ts/result',
                    'transaction_status_timeout' => 'https://example.com/cb/ts/timeout',
                    'account_balance_result' => 'https://example.com/cb/ab/result',
                    'account_balance_timeout' => 'https://example.com/cb/ab/timeout',
                    'reversal_result' => 'https://example.com/cb/rv/result',
                    'reversal_timeout' => 'https://example.com/cb/rv/timeout',
                ],
                'webhook_validation' => [
                    'enabled' => true,
                    'header' => 'X-Test-Token',
                    'token' => 'token',
                    'allowed_ips' => ['127.0.0.1'],
                ],
            ],
        ]);

        $config = new MpesaConfig($configRepo);

        $this->assertSame('api/payments', $config->routePrefix());
        $this->assertSame(['api', 'throttle:60,1'], $config->routeMiddleware());
        $this->assertFalse($config->storeRequests());
        $this->assertTrue($config->storeCallbacks());
        $this->assertSame('sandbox', $config->env());
        $this->assertSame('https://sandbox.safaricom.co.ke', $config->baseUrl());
        $this->assertSame('/tmp/sandbox.cer', $config->certificatePath());
        $this->assertSame('key', $config->consumerKey());
        $this->assertSame('secret', $config->consumerSecret());
        $this->assertSame([
            'short_code' => '123456',
            'passkey' => 'pass',
            'callback_url' => 'https://example.com/stk-inline',
            'transaction_type' => 'CustomerPayBillOnline',
            'account_reference' => 'REF',
            'transaction_desc' => 'DESC',
        ], $config->credentials('stk'));
        $this->assertSame([
            'short_code' => '123456',
            'passkey' => 'pass',
            'callback_url' => 'https://example.com/stk-inline',
            'transaction_type' => 'CustomerPayBillOnline',
            'account_reference' => 'REF',
            'transaction_desc' => 'DESC',
        ], $config->stkCredentials());
        $this->assertSame('123456', $config->stkShortCode());
        $this->assertSame('pass', $config->stkPasskey());
        $this->assertSame('https://example.com/stk-inline', $config->stkCallbackUrl());
        $this->assertSame('CustomerPayBillOnline', $config->stkTransactionType());
        $this->assertSame('REF', $config->stkAccountReference());
        $this->assertSame('DESC', $config->stkTransactionDesc());
        $this->assertSame('initiator', $config->b2cInitiatorName());
        $this->assertSame('pass123', $config->b2cInitiatorPassword());
        $this->assertSame('sec', $config->b2cSecurityCredential());
        $this->assertSame('600000', $config->b2cShortCode());
        $this->assertSame('BusinessPayment', $config->b2cCommandId());
        $this->assertSame('https://example.com/b2c/result', $config->b2cResultUrl());
        $this->assertSame('https://example.com/b2c/timeout', $config->b2cTimeoutUrl());
        $this->assertSame('600111', $config->c2bShortCode());
        $this->assertSame('Completed', $config->c2bResponseType());
        $this->assertSame('https://example.com/c2b/validation', $config->c2bValidationUrl());
        $this->assertSame('https://example.com/c2b/confirmation', $config->c2bConfirmationUrl());
        $this->assertSame('https://example.com/stk', $config->callback('stk'));
        $this->assertSame('https://example.com/stk', $config->callbackStk());
        $this->assertSame('https://example.com/cb/b2c/result', $config->callbackB2cResult());
        $this->assertSame('https://example.com/cb/b2c/timeout', $config->callbackB2cTimeout());
        $this->assertSame('https://example.com/cb/c2b/validation', $config->callbackC2bValidation());
        $this->assertSame('https://example.com/cb/c2b/confirmation', $config->callbackC2bConfirmation());
        $this->assertSame('https://example.com/cb/ts/result', $config->callbackTransactionStatusResult());
        $this->assertSame('https://example.com/cb/ts/timeout', $config->callbackTransactionStatusTimeout());
        $this->assertSame('https://example.com/cb/ab/result', $config->callbackAccountBalanceResult());
        $this->assertSame('https://example.com/cb/ab/timeout', $config->callbackAccountBalanceTimeout());
        $this->assertSame('https://example.com/cb/rv/result', $config->callbackReversalResult());
        $this->assertSame('https://example.com/cb/rv/timeout', $config->callbackReversalTimeout());
        $this->assertTrue($config->webhookValidationEnabled());
        $this->assertSame('X-Test-Token', $config->webhookValidationHeader());
        $this->assertSame('token', $config->webhookValidationToken());
        $this->assertSame(['127.0.0.1'], $config->webhookValidationAllowedIps());
    }
}
