<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->bigIncrements('refund_request_id');
            $table->text('reason');
            $table->string('source_type', 50);
            $table->decimal('expected_refund_amount', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->decimal('actual_refund_amount', 12, 2)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->boolean('reconsidered')->default(false);
            $table->string('evidence_file')->nullable();
            $table->text('response_content')->nullable();

            $table->foreignId('payment_transaction_id')
                ->constrained('payment_transactions', 'payment_transaction_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};