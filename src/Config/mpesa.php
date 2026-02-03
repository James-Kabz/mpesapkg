<?php

return [
    'env' => env('MPESA_ENV', 'sandbox'),
    'route_prefix' => env('MPESA_ROUTE_PREFIX', 'mpesa'),
    'route_middleware' => ['api'],
    'store_requests' => env('MPESA_STORE_REQUESTS', true),
    'store_callbacks' => env('MPESA_STORE_CALLBACKS', true),
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'base_url' => env('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    'cert_paths' => [
        'sandbox' => env('MPESA_CERT_SANDBOX_PATH', storage_path('app/private/certs/SandboxCertificate.cer')),
        'production' => env('MPESA_CERT_PRODUCTION_PATH', storage_path('app/private/certs/ProductionCertificate.cer')),
    ],
    'callbacks' => [
        'stk' => env('MPESA_STK_CALLBACK_URL'),
        'b2c_result' => env('MPESA_B2C_RESULT_URL'),
        'b2c_timeout' => env('MPESA_B2C_TIMEOUT_URL'),
        'c2b_validation' => env('MPESA_C2B_VALIDATION_URL'),
        'c2b_confirmation' => env('MPESA_C2B_CONFIRMATION_URL'),
        'transaction_status_result' => env('MPESA_TRANSACTION_STATUS_RESULT_URL'),
        'transaction_status_timeout' => env('MPESA_TRANSACTION_STATUS_TIMEOUT_URL'),
        'account_balance_result' => env('MPESA_ACCOUNT_BALANCE_RESULT_URL'),
        'account_balance_timeout' => env('MPESA_ACCOUNT_BALANCE_TIMEOUT_URL'),
        'reversal_result' => env('MPESA_REVERSAL_RESULT_URL'),
        'reversal_timeout' => env('MPESA_REVERSAL_TIMEOUT_URL'),
    ],
    'webhook_validation' => [
        'enabled' => env('MPESA_WEBHOOK_VALIDATION', false),
        'allowed_ips' => array_filter(explode(',', env('MPESA_WEBHOOK_ALLOWED_IPS', ''))),
        'header' => env('MPESA_WEBHOOK_HEADER', 'X-Mpesa-Token'),
        'token' => env('MPESA_WEBHOOK_TOKEN'),
    ],

    'credentials' => [
        'b2c' => [
            'initiator_name' => env('MPESA_B2C_INITIATOR'),
            'initiator_password' => env('MPESA_B2C_INITIATOR_PASSWORD'),
            'security_credential' => env('MPESA_B2C_SECURITY_CREDENTIAL'),
            'short_code' => env('MPESA_B2C_SHORT_CODE'),
            'command_id' => env('MPESA_B2C_COMMAND_ID', 'BusinessPayment'),
            'timeout_url' => env('MPESA_B2C_TIMEOUT_URL'),
            'result_url' => env('MPESA_B2C_RESULT_URL'),
            'passkey' => env('MPESA_B2C_PASSKEY'),
        ],
        'c2b' => [
            'short_code' => env('MPESA_C2B_SHORT_CODE'),
            'response_type' => env('MPESA_C2B_RESPONSE_TYPE', 'Completed'),
            'validation_url' => env('MPESA_C2B_VALIDATION_URL'),
            'confirmation_url' => env('MPESA_C2B_CONFIRMATION_URL'),
        ],
        'stk' => [
            'short_code' => env('MPESA_STK_SHORT_CODE'),
            'passkey' => env('MPESA_STK_PASSKEY'),
            'callback_url' => env('MPESA_STK_CALLBACK_URL'),
            'transaction_type' => env('MPESA_STK_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
            'account_reference' => env('MPESA_STK_ACCOUNT_REFERENCE', 'Mpesa Test'),
            'transaction_desc' => env('MPESA_STK_TRANSACTION_DESC', 'STK Push Test'),
        ],
    ],
];
