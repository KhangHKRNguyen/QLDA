<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->bigIncrements('refund_transaction_id');
            $table->decimal('refund_amount', 12, 2);
            $table->timestamp('refunded_at')->nullable();
            $table->string('status', 30)->default('pending');

            $table->foreignId('refund_request_id')
                ->constrained('refund_requests', 'refund_request_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
    }
};