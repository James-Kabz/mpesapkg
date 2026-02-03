<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Store inbound M-Pesa callbacks (C2B, STK, B2C, utilities).
         */
        Schema::create('mpesa_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->integer('result_code')->nullable();
            $table->string('result_desc', 200)->nullable();
            $table->string('originator_conversation_id', 100)->nullable()->index();
            $table->string('conversation_id', 100)->nullable()->index();
            $table->string('transaction_id', 100)->nullable()->index();
            $table->string('merchant_request_id', 100)->nullable()->index();
            $table->string('checkout_request_id', 100)->nullable()->index();
            $table->string('mpesa_receipt_number', 100)->nullable()->index();
            $table->string('bill_ref_number', 100)->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('party_a', 30)->nullable()->index();
            $table->string('party_b', 30)->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['type', 'result_code']);
        });
    }

    public function down(): void
    {
        /**
         * Drop inbound M-Pesa callbacks table.
         */
        Schema::dropIfExists('mpesa_callbacks');
    }
};
