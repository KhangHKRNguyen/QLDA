<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu demo cho "Giảng dạy và xử lý hoàn phí" (Gia sư, NV QLKH): lớp của tutor01 ở các giai đoạn khác nhau
 * và một yêu cầu hoàn phí đang chờ gia sư xác nhận kết quả.
 * Chạy sau AccountSeeder + DemoAccountSeeder:  php artisan db:seed --class=TeachingDemoSeeder
 * Chạy lại không bị trùng (đánh dấu tutor_requests.notes = '[demo-giang-day]').
 */
class TeachingDemoSeeder extends Seeder
{
    private const MARK = '[demo-giang-day]';

    public function run(): void
    {
        if (DB::table('tutor_requests')->where('notes', self::MARK)->exists()) {
            return;
        }

        $acc = DB::table('accounts')->pluck('account_id', 'username');
        if (! isset($acc['tutor01'], $acc['manager01'])) {
            $this->command?->warn('Thiếu tutor01 / manager01 — hãy chạy AccountSeeder và DemoAccountSeeder trước.');

            return;
        }
        $tutor = $acc['tutor01'];

        $parent = DB::table('people')->where('email', 'trang.le@gmail.com')->value('person_id')
            ?? DB::table('people')->insertGetId(['full_name' => 'Lê Thu Trang', 'phone_number' => '0911000002', 'email' => 'trang.le@gmail.com', 'district' => 'Đống Đa']);

        // [môn, khối, phí nhận lớp, nhận cách đây (ngày), status, teaching_stage, ghi chú]
        $classes = [
            ['Toán', 'Lớp 8', 500000, 3, 'teaching', 'trial', 'Mới nhận — chờ kết quả dạy thử'],
            ['Tiếng Anh', 'Lớp 5', 450000, 20, 'teaching', 'official', 'Đang dạy chính thức, còn bảo hành'],
            ['Ngữ văn', 'Lớp 9', 400000, 70, 'teaching', 'official', 'Đã hết bảo hành 60 ngày'],
            ['Vật lý', 'Lớp 11', 600000, 120, 'completed', 'official', 'Đã hoàn thành'],
            ['Hóa học', 'Lớp 10', 400000, 15, 'refund_processing', 'official', 'Có kết quả hoàn phí chờ gia sư xác nhận'],
        ];

        foreach ($classes as [$subject, $grade, $fee, $daysAgo, $status, $stage, $note]) {
            $requestId = DB::table('tutor_requests')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà', 'sessions_per_week' => 2,
                'desired_tuition_fee' => 200000, 'status' => 'matched', 'notes' => self::MARK, 'person_id' => $parent,
                'created_at' => now()->subDays($daysAgo + 7), 'updated_at' => now()->subDays($daysAgo + 7),
            ]);

            $classId = DB::table('tutor_classes')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà', 'sessions_per_week' => 2,
                'fee_per_session' => 200000, 'location' => 'Đống Đa, Hà Nội', 'schedule' => 'Tối thứ 3 và thứ 6, 19:00–20:30',
                'enrollment_fee' => $fee, 'status' => $status, 'teaching_stage' => $stage,
                'accepted_at' => now()->subDays($daysAgo),
                'trial_decided_at' => $stage === 'official' ? now()->subDays(max($daysAgo - 3, 1)) : null,
                'completed_at' => $status === 'completed' ? now()->subDays(30) : null,
                'created_at' => now()->subDays($daysAgo + 5), 'updated_at' => now(),
                'person_id' => $parent, 'tutor_request_id' => $requestId, 'account_id' => $tutor,
            ]);

            $paymentId = DB::table('payment_transactions')->insertGetId([
                'amount' => $fee, 'status' => 'completed', 'transaction_at' => now()->subDays($daysAgo),
                'external_reference' => 'PAY' . str_pad((string) $classId, 6, '0', STR_PAD_LEFT),
                'class_id' => $classId, 'account_id' => $tutor,
            ]);

            DB::table('activity_logs')->insert([
                'action' => "Nhận lớp {$subject} {$grade}", 'occurred_at' => now()->subDays($daysAgo),
                'affected_object' => 'tutor_classes#' . $classId, 'account_id' => $tutor,
            ]);

            if ($status === 'refund_processing') {
                $refundId = DB::table('refund_requests')->insertGetId([
                    'reason' => 'Phụ huynh đổi ý không học nữa trước khi tôi kịp liên hệ hẹn lịch dạy thử.',
                    'reason_code' => 'center_fault', 'source_type' => 'tutor',
                    'expected_refund_amount' => $fee, 'status' => 'decided', 'received_at' => now()->subDays(5),
                    'actual_refund_amount' => 300000, 'decision_note' => 'Gia sư đã đến dạy 1 buổi trước khi phụ huynh báo nghỉ, trừ phần hỗ trợ.',
                    'decided_at' => now()->subDays(1), 'decided_by' => $acc['manager01'], 'class_outcome' => 'reopen',
                    'payment_transaction_id' => $paymentId, 'requested_by' => $tutor,
                ]);
                foreach ([['Gửi yêu cầu hoàn phí – dự kiến 400.000 đ', 5, $tutor], ['Quyết định: duyệt hoàn 300.000 đ', 1, $acc['manager01']]] as [$action, $ago, $by]) {
                    DB::table('activity_logs')->insert([
                        'action' => $action, 'occurred_at' => now()->subDays($ago),
                        'affected_object' => 'refund_requests#' . $refundId, 'account_id' => $by,
                    ]);
                }
            }
        }

        // Lớp demo cũ (DemoAccountSeeder, RefundDemoSeeder) đã có gia sư: coi như đang dạy chính thức
        DB::table('tutor_classes')->whereIn('status', ['teaching', 'refund_processing'])->whereNull('teaching_stage')
            ->update(['teaching_stage' => 'official']);
    }
}
