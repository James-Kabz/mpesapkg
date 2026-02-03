<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Store outbound M-Pesa requests (STK, B2C, etc).
         */
        Schema::create('mpesa_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('phone', 30)->nullable();
            $table->string('party_a', 30)->nullable()->index();
            $table->string('party_b', 30)->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            $table->string('remarks', 200)->nullable();
            $table->string('command_id', 50)->nullable();
            $table->string('bill_ref_number', 100)->nullable()->index();
            $table->string('originator_conversation_id', 100)->nullable()->index();
            $table->string('conversation_id', 100)->nullable()->index();
            $table->string('merchant_request_id', 100)->nullable()->index();
            $table->string('checkout_request_id', 100)->nullable()->index();
            $table->string('response_code', 20)->nullable();
            $table->string('response_description', 200)->nullable();
            $table->integer('result_code')->nullable();
            $table->string('result_desc', 200)->nullable();
            $table->string('transaction_id', 100)->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['type', 'phone']);
        });
    }

    public function down(): void
    {
        /**
         * Drop outbound M-Pesa requests table.
         */
        Schema::dropIfExists('mpesa_requests');
    }
};
