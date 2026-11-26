<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_classes', function (Blueprint $table) {
            $table->foreignId('locked_by_account_id')
                ->nullable()
                ->after('locked_at')
                ->constrained('accounts', 'account_id')
                ->nullOnDelete();

            $table->timestamp('lock_expires_at')->nullable()->after('locked_by_account_id');
            $table->index(['status', 'lock_expires_at'], 'tutor_classes_reservation_idx');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            // Snapshot thời điểm hết hạn của lần giữ chỗ này để nhận diện callback đến muộn.
            $table->timestamp('reservation_expires_at')->nullable()->after('transaction_at');
            $table->timestamp('paid_at')->nullable()->after('reservation_expires_at');
            $table->string('payment_provider', 50)->nullable()->after('external_reference');
            $table->json('gateway_payload')->nullable()->after('payment_provider');

            $table->index(['class_id', 'account_id', 'status'], 'payment_transactions_class_account_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_class_account_status_idx');
            $table->dropColumn([
                'reservation_expires_at',
                'paid_at',
                'payment_provider',
                'gateway_payload',
            ]);
        });

        Schema::table('tutor_classes', function (Blueprint $table) {
            $table->dropIndex('tutor_classes_reservation_idx');
            $table->dropForeign(['locked_by_account_id']);
            $table->dropColumn(['locked_by_account_id', 'lock_expires_at']);
        });
    }
};
