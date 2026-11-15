<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung cột cho quy trình xử lý hoàn phí (SRS 3.7, PTTKHT 2.2.9.2):
 * mã lý do theo bảng 6.3, người gửi, người quyết định, lý do điều chỉnh, cách xử lý lớp sau hoàn phí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('reason_code', 30)->nullable()->after('reason');       // RefundRequest::REASONS
            $table->text('decision_note')->nullable()->after('rejection_reason'); // lý do điều chỉnh mức hoàn / quyết định cuối
            $table->timestamp('decided_at')->nullable()->after('decision_note');
            $table->string('class_outcome', 30)->nullable()->after('decided_at'); // RefundRequest::CLASS_OUTCOMES

            $table->foreignId('requested_by')->nullable()
                ->constrained('accounts', 'account_id')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()
                ->constrained('accounts', 'account_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['reason_code', 'decision_note', 'decided_at', 'class_outcome']);
        });
    }
};
