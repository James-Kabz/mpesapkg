<?php

namespace JamesKabz\MpesaPkg\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaCallback extends Model
{
    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'result_code',
        'result_desc',
        'originator_conversation_id',
        'conversation_id',
        'transaction_id',
        'merchant_request_id',
        'checkout_request_id',
        'mpesa_receipt_number',
        'bill_ref_number',
        'amount',
        'phone',
        'party_a',
        'party_b',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'result_code' => 'integer',
        'amount' => 'decimal:2',
    ];
}
