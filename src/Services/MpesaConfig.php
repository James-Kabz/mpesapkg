<?php

namespace JamesKabz\MpesaPkg\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class MpesaConfig
{
    /**
     * @var array<string, mixed>
     */
    protected array $config;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config->get('mpesa', []);
    }

    /**
     * Get a config value by dot-notation key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Return the full mpesa config array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    public function routePrefix(): string
    {
        return (string) $this->get('route_prefix', 'mpesa');
    }

    /**
     * @return array<int, string>
     */
    public function routeMiddleware(): array
    {
        $middleware = $this->get('route_middleware', ['api']);
        return is_array($middleware) ? $middleware : ['api'];
    }

    public function storeRequests(): bool
    {
        return (bool) $this->get('store_requests', true);
    }

    public function storeCallbacks(): bool
    {
        return (bool) $this->get('store_callbacks', true);
    }

    public function env(): string
    {
        return (string) $this->get('env', 'sandbox');
    }

    public function baseUrl(): string
    {
        return (string) $this->get('base_url', 'https://sandbox.safaricom.co.ke');
    }

    public function consumerKey(): ?string
    {
        $key = $this->get('consumer_key');
        return $key !== null ? (string) $key : null;
    }

    public function consumerSecret(): ?string
    {
        $secret = $this->get('consumer_secret');
        return $secret !== null ? (string) $secret : null;
    }

    public function certificatePath(?string $env = null): string
    {
        $environment = $env ?: $this->env();
        if ($environment === 'sandbox') {
            return (string) $this->get('cert_paths.sandbox', $this->storagePath('app/private/certs/SandboxCertificate.cer'));
        }

        return (string) $this->get('cert_paths.production', $this->storagePath('app/private/certs/ProductionCertificate.cer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function credentials(string $key): array
    {
        $credentials = $this->get('credentials.' . $key, []);
        return is_array($credentials) ? $credentials : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function stkCredentials(): array
    {
        return $this->credentials('stk');
    }

    public function stkShortCode(): ?string
    {
        $value = $this->get('credentials.stk.short_code');
        return $value !== null ? (string) $value : null;
    }

    public function stkPasskey(): ?string
    {
        $value = $this->get('credentials.stk.passkey');
        return $value !== null ? (string) $value : null;
    }

    public function stkCallbackUrl(): ?string
    {
        $value = $this->get('credentials.stk.callback_url');
        return $value !== null ? (string) $value : null;
    }

    public function stkTransactionType(): ?string
    {
        $value = $this->get('credentials.stk.transaction_type');
        return $value !== null ? (string) $value : null;
    }

    public function stkAccountReference(): ?string
    {
        $value = $this->get('credentials.stk.account_reference');
        return $value !== null ? (string) $value : null;
    }

    public function stkTransactionDesc(): ?string
    {
        $value = $this->get('credentials.stk.transaction_desc');
        return $value !== null ? (string) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function b2cCredentials(): array
    {
        return $this->credentials('b2c');
    }

    public function b2cInitiatorName(): ?string
    {
        $value = $this->get('credentials.b2c.initiator_name');
        return $value !== null ? (string) $value : null;
    }

    public function b2cInitiatorPassword(): ?string
    {
        $value = $this->get('credentials.b2c.initiator_password');
        return $value !== null ? (string) $value : null;
    }

    public function b2cSecurityCredential(): ?string
    {
        $value = $this->get('credentials.b2c.security_credential');
        return $value !== null ? (string) $value : null;
    }

    public function b2cShortCode(): ?string
    {
        $value = $this->get('credentials.b2c.short_code');
        return $value !== null ? (string) $value : null;
    }

    public function b2cCommandId(): ?string
    {
        $value = $this->get('credentials.b2c.command_id');
        return $value !== null ? (string) $value : null;
    }

    public function b2cResultUrl(): ?string
    {
        $value = $this->get('credentials.b2c.result_url');
        return $value !== null ? (string) $value : null;
    }

    public function b2cTimeoutUrl(): ?string
    {
        $value = $this->get('credentials.b2c.timeout_url');
        return $value !== null ? (string) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function c2bCredentials(): array
    {
        return $this->credentials('c2b');
    }

    public function c2bShortCode(): ?string
    {
        $value = $this->get('credentials.c2b.short_code');
        return $value !== null ? (string) $value : null;
    }

    public function c2bResponseType(): ?string
    {
        $value = $this->get('credentials.c2b.response_type');
        return $value !== null ? (string) $value : null;
    }

    public function c2bValidationUrl(): ?string
    {
        $value = $this->get('credentials.c2b.validation_url');
        return $value !== null ? (string) $value : null;
    }

    public function c2bConfirmationUrl(): ?string
    {
        $value = $this->get('credentials.c2b.confirmation_url');
        return $value !== null ? (string) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function callbacks(): array
    {
        $callbacks = $this->get('callbacks', []);
        return is_array($callbacks) ? $callbacks : [];
    }

    public function callback(string $key): ?string
    {
        $callbacks = $this->callbacks();
        return array_key_exists($key, $callbacks) ? (string) $callbacks[$key] : null;
    }

    public function callbackStk(): ?string
    {
        return $this->callback('stk');
    }

    public function callbackB2cResult(): ?string
    {
        return $this->callback('b2c_result');
    }

    public function callbackB2cTimeout(): ?string
    {
        return $this->callback('b2c_timeout');
    }

    public function callbackC2bValidation(): ?string
    {
        return $this->callback('c2b_validation');
    }

    public function callbackC2bConfirmation(): ?string
    {
        return $this->callback('c2b_confirmation');
    }

    public function callbackTransactionStatusResult(): ?string
    {
        return $this->callback('transaction_status_result');
    }

    public function callbackTransactionStatusTimeout(): ?string
    {
        return $this->callback('transaction_status_timeout');
    }

    public function callbackAccountBalanceResult(): ?string
    {
        return $this->callback('account_balance_result');
    }

    public function callbackAccountBalanceTimeout(): ?string
    {
        return $this->callback('account_balance_timeout');
    }

    public function callbackReversalResult(): ?string
    {
        return $this->callback('reversal_result');
    }

    public function callbackReversalTimeout(): ?string
    {
        return $this->callback('reversal_timeout');
    }

    public function webhookValidationEnabled(): bool
    {
        return (bool) $this->get('webhook_validation.enabled', false);
    }

    public function webhookValidationHeader(): string
    {
        return (string) $this->get('webhook_validation.header', 'X-Mpesa-Token');
    }

    public function webhookValidationToken(): ?string
    {
        $token = $this->get('webhook_validation.token');
        return $token !== null ? (string) $token : null;
    }

    /**
     * @return array<int, string>
     */
    public function webhookValidationAllowedIps(): array
    {
        $ips = $this->get('webhook_validation.allowed_ips', []);
        return is_array($ips) ? $ips : [];
    }

    protected function storagePath(string $path): string
    {
        if (function_exists('storage_path')) {
            return storage_path($path);
        }

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
