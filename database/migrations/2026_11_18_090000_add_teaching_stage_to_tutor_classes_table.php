<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quy trình giảng dạy (PTTKHT 2.1.8, HĐ2–HĐ6): lớp đã có gia sư đi qua dạy thử → dạy chính thức.
 * teaching_stage: 'trial' (Đang dạy thử) | 'official' (Dạy chính thức); null khi lớp chưa có gia sư.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_classes', function (Blueprint $table) {
            $table->string('teaching_stage', 20)->nullable()->after('status');
            $table->timestamp('trial_decided_at')->nullable()->after('teaching_stage'); // lúc ghi nhận kết quả dạy thử
            $table->timestamp('completed_at')->nullable()->after('trial_decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_classes', function (Blueprint $table) {
            $table->dropColumn(['teaching_stage', 'trial_decided_at', 'completed_at']);
        });
    }
};
