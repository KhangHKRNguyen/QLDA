<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->bigIncrements('payment_transaction_id');
            $table->decimal('amount', 12, 2);
            $table->timestamp('transaction_at')->useCurrent();
            $table->string('status', 30)->default('pending');
            $table->string('external_reference', 255)->nullable();

            $table->foreignId('class_id')
                ->constrained('tutor_classes', 'class_id')
                ->restrictOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounts', 'account_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};