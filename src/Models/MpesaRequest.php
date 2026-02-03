<?php

namespace JamesKabz\MpesaPkg\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaRequest extends Model
{
    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'status',
        'phone',
        'party_a',
        'party_b',
        'amount',
        'currency',
        'remarks',
        'command_id',
        'bill_ref_number',
        'originator_conversation_id',
        'conversation_id',
        'merchant_request_id',
        'checkout_request_id',
        'response_code',
        'response_description',
        'result_code',
        'result_desc',
        'transaction_id',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'amount' => 'decimal:2',
        'result_code' => 'integer',
    ];
}
