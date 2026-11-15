<?php

namespace Database\Seeders;

use App\Models\RefundRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu demo cho "Trung tâm xử lý hoàn phí": mỗi trạng thái có ít nhất một yêu cầu.
 * Chạy sau AccountSeeder + DemoAccountSeeder:  php artisan db:seed --class=RefundDemoSeeder
 * Chạy lại nhiều lần không bị trùng (đánh dấu bằng tutor_requests.notes = '[demo-hoan-phi]').
 */
class RefundDemoSeeder extends Seeder
{
    private const MARK = '[demo-hoan-phi]';

    public function run(): void
    {
        if (DB::table('tutor_requests')->where('notes', self::MARK)->exists()) {
            return;
        }

        $acc = DB::table('accounts')->pluck('account_id', 'username');
        foreach (['tutor01', 'tutor07', 'qlkh01', 'manager01'] as $u) {
            if (! isset($acc[$u])) {
                $this->command?->warn("Thiếu tài khoản {$u} — hãy chạy AccountSeeder và DemoAccountSeeder trước.");

                return;
            }
        }

        $parent = DB::table('people')->where('email', 'nhung.pham@gmail.com')->value('person_id')
            ?? DB::table('people')->insertGetId(['full_name' => 'Phạm Hồng Nhung', 'phone_number' => '0911000001', 'email' => 'nhung.pham@gmail.com', 'district' => 'Cầu Giấy']);

        $manager = $acc['manager01'];

        // [gia sư, môn, khối, phí nhận lớp, mã lý do, nguồn, nhận cách đây (ngày), trạng thái, nội dung, cấu hình thêm]
        $cases = [
            ['tutor01', 'Hóa học', 'Lớp 11', 600000, 'quality_feedback', 'staff', 13, 'pending',
                'Phụ huynh phản ánh gia sư thường xuyên đến muộn 15–20 phút và chưa chuẩn bị bài trước.', []],
            ['tutor07', 'Ngữ văn', 'Lớp 8', 400000, 'trial_quit', 'staff', 18, 'overdue',
                'Phụ huynh báo cho con nghỉ sau 2 buổi dạy thử vì lịch học thêm ở trường thay đổi.', []],
            ['tutor01', 'Toán', 'Lớp 6', 500000, 'center_fault', 'tutor', 9, 'reconsidering',
                'Thông tin lớp ghi học tại Cầu Giấy nhưng thực tế ở Hoài Đức, gia sư không thể di chuyển.',
                ['actual' => 0, 'rejection_reason' => 'Gia sư đã xem địa chỉ chi tiết trước khi nhận lớp.', 'reconsidered' => true,
                 'response_content' => 'Địa chỉ trên tin tuyển là Cầu Giấy, phụ huynh chỉ báo địa chỉ thật sau khi gia sư đã thanh toán. Đính kèm ảnh chụp tin nhắn.']],
            ['tutor07', 'Tiếng Anh', 'Lớp 3', 450000, 'teaching_method', 'staff', 6, 'decided',
                'Phụ huynh không hài lòng phương pháp dạy, muốn đổi gia sư.',
                ['actual' => 300000, 'note' => 'Gia sư đã dạy 3 buổi chính thức, trừ thêm phần hỗ trợ dạy thử.']],
            ['tutor01', 'Vật lý', 'Lớp 12', 700000, 'center_fault', 'tutor', 12, 'refund_failed',
                'Phụ huynh đổi ý không học trước khi gia sư kịp liên hệ.', ['actual' => 700000, 'tx' => ['failed']]],
            ['tutor07', 'Toán', 'Lớp 4', 350000, 'short_term_change', 'staff', 25, 'refunded',
                'Gia đình chuyển nhà vào TP.HCM sau 1 tháng học.', ['actual' => 262500, 'tx' => ['success'], 'class_status' => 'canceled', 'outcome' => 'close']],
            ['tutor01', 'Sinh học', 'Lớp 9', 300000, 'tutor_quit', 'tutor', 30, 'completed',
                'Gia sư không còn nhu cầu dạy do lịch học kỳ mới kín.', ['actual' => 0, 'class_status' => 'pending', 'outcome' => 'reopen']],
        ];

        foreach ($cases as [$tutor, $subject, $grade, $fee, $reason, $source, $daysAgo, $status, $content, $extra]) {
            $requestId = DB::table('tutor_requests')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà', 'sessions_per_week' => 2,
                'desired_tuition_fee' => 200000, 'status' => 'matched', 'notes' => self::MARK, 'person_id' => $parent,
                'created_at' => now()->subDays($daysAgo + 20), 'updated_at' => now()->subDays($daysAgo + 20),
            ]);

            $open = in_array($status, RefundRequest::OPEN_STATUSES, true);
            $classId = DB::table('tutor_classes')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà', 'sessions_per_week' => 2,
                'fee_per_session' => 200000, 'location' => 'Hà Nội', 'enrollment_fee' => $fee,
                'status' => $extra['class_status'] ?? ($open ? 'refund_processing' : 'teaching'),
                'accepted_at' => now()->subDays($daysAgo + 10),
                'created_at' => now()->subDays($daysAgo + 15), 'updated_at' => now(),
                'person_id' => $parent, 'tutor_request_id' => $requestId, 'account_id' => $acc[$tutor],
            ]);

            $paymentId = DB::table('payment_transactions')->insertGetId([
                'amount' => $fee, 'status' => 'completed', 'transaction_at' => now()->subDays($daysAgo + 10),
                'external_reference' => 'PAY' . str_pad((string) $classId, 6, '0', STR_PAD_LEFT),
                'class_id' => $classId, 'account_id' => $acc[$tutor],
            ]);

            $decided = array_key_exists('actual', $extra);
            $refundId = DB::table('refund_requests')->insertGetId([
                'reason' => $content, 'reason_code' => $reason, 'source_type' => $source,
                'expected_refund_amount' => $fee * RefundRequest::REASONS[$reason][1],
                'status' => $status, 'received_at' => now()->subDays($daysAgo),
                'actual_refund_amount' => $extra['actual'] ?? null,
                'rejection_reason' => $extra['rejection_reason'] ?? null,
                'decision_note' => $extra['note'] ?? null,
                'reconsidered' => $extra['reconsidered'] ?? false,
                'response_content' => $extra['response_content'] ?? null,
                'decided_at' => $decided ? now()->subDays(max($daysAgo - 3, 1)) : null,
                'decided_by' => $decided ? $manager : null,
                'class_outcome' => $extra['outcome'] ?? ($decided ? 'reopen' : null),
                'payment_transaction_id' => $paymentId,
                'requested_by' => $source === 'staff' ? $acc['qlkh01'] : $acc[$tutor],
            ]);

            foreach ($extra['tx'] ?? [] as $txStatus) {
                DB::table('refund_transactions')->insert([
                    'refund_amount' => $extra['actual'], 'status' => $txStatus,
                    'refunded_at' => $txStatus === 'success' ? now()->subDays(max($daysAgo - 5, 1)) : null,
                    'refund_request_id' => $refundId,
                ]);
            }

            DB::table('activity_logs')->insert([
                'action' => 'Gửi yêu cầu hoàn phí', 'occurred_at' => now()->subDays($daysAgo),
                'affected_object' => 'refund_requests#' . $refundId,
                'account_id' => $source === 'staff' ? $acc['qlkh01'] : $acc[$tutor],
            ]);
        }
    }
}
