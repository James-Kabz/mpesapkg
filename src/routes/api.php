<?php

use Illuminate\Support\Facades\Route;
use JamesKabz\MpesaPkg\Http\Controllers\MpesaC2bController;
use JamesKabz\MpesaPkg\Http\Controllers\MpesaB2cController;
use JamesKabz\MpesaPkg\Http\Controllers\MpesaStkController;
use JamesKabz\MpesaPkg\Http\Controllers\MpesaUtilityController;

Route::prefix(config('mpesa.route_prefix', 'mpesa'))
    ->middleware(config('mpesa.route_middleware', ['api']))
    ->group(function () {
        Route::post('stk/push', [MpesaStkController::class, 'push']);
        Route::post('stk/callback', [MpesaStkController::class, 'callback']);
        Route::post('stk/query', [MpesaStkController::class, 'query']);
        Route::post('b2c/send', [MpesaB2cController::class, 'send']);
        Route::post('b2c/validated', [MpesaB2cController::class, 'validated']);
        Route::post('b2c/result', [MpesaB2cController::class, 'result']);
        Route::post('b2c/timeout', [MpesaB2cController::class, 'timeout']);
        Route::post('c2b/register', [MpesaC2bController::class, 'register']);
        Route::post('c2b/simulate', [MpesaC2bController::class, 'simulate']);
        Route::post('c2b/validation', [MpesaC2bController::class, 'validation']);
        Route::post('c2b/confirmation', [MpesaC2bController::class, 'confirmation']);
        Route::post('transaction/status', [MpesaUtilityController::class, 'transactionStatus']);
        Route::post('transaction/status/result', [MpesaUtilityController::class, 'transactionStatusResult']);
        Route::post('transaction/status/timeout', [MpesaUtilityController::class, 'transactionStatusTimeout']);
        Route::post('account/balance', [MpesaUtilityController::class, 'accountBalance']);
        Route::post('account/balance/result', [MpesaUtilityController::class, 'accountBalanceResult']);
        Route::post('account/balance/timeout', [MpesaUtilityController::class, 'accountBalanceTimeout']);
        Route::post('reversal', [MpesaUtilityController::class, 'reversal']);
        Route::post('reversal/result', [MpesaUtilityController::class, 'reversalResult']);
        Route::post('reversal/timeout', [MpesaUtilityController::class, 'reversalTimeout']);
    });
