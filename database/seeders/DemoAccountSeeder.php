<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Dữ liệu DEMO cho trang Quản lý tài khoản (chỉ dùng khi phát triển).
 * Cần chạy RoleSeeder + AccountSeeder trước. Chạy lại nhiều lần không bị trùng.
 *
 * Tạo: gia sư ở đủ các trạng thái, phụ huynh, lớp đang dạy, giao dịch nợ phí,
 * yêu cầu hoàn phí chờ xử lý và lịch sử hoạt động.
 */
class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $tutorRole = DB::table('roles')->where('role_name', 'Gia sư')->value('role_id');
        if (! $tutorRole) {
            throw new \RuntimeException('Chưa có vai trò "Gia sư". Hãy chạy RoleSeeder trước.');
        }

        // username => [họ tên, SĐT, email, trạng thái, ngày sinh, giới tính, khu vực, lý do khóa, số ngày còn khóa]
        $tutors = [
            'tutor01' => ['Nguyễn Văn An', '0900000001', 'tutor01@gmail.com', 'active', '2002-03-14', 'Nam', 'Cầu Giấy', null, null],
            'tutor02' => ['Trần Thị Bích', '0900000011', 'bich.tran@gmail.com', 'locked', '2001-08-02', 'Nữ', 'Đống Đa', 'Hủy buổi dạy không báo trước 2 lần trong tháng.', 9],
            'tutor03' => ['Lê Hoàng Nam', '0900000012', 'nam.le@gmail.com', 'suspended', '1999-11-20', 'Nam', 'Hà Đông', 'Gian lận thông tin bằng cấp trong hồ sơ xác minh.', null],
            'tutor04' => ['Phạm Minh Châu', '0900000013', 'chau.pham@gmail.com', 'pending_verification', '2003-05-09', 'Nữ', 'Thanh Xuân', null, null],
            'tutor05' => ['Vũ Thu Hà', '0900000014', 'ha.vu@gmail.com', 'supplement_required', '2002-12-01', 'Nữ', 'Ba Đình', null, null],
            'tutor06' => ['Đặng Quốc Bảo', '0900000015', 'bao.dang@gmail.com', 'inactive', '1998-07-17', 'Nam', 'Long Biên', 'Gia sư yêu cầu ngừng hoạt động.', null],
            'tutor07' => ['Ngô Thanh Tùng', '0900000016', 'tung.ngo@gmail.com', 'active', '2000-01-25', 'Nam', 'Hoàng Mai', null, null],
            'tutor08' => ['Bùi Khánh Linh', '0900000017', 'linh.bui@gmail.com', 'incomplete', null, 'Nữ', 'Nam Từ Liêm', null, null],
        ];

        $ids = [];
        foreach ($tutors as $username => [$name, $phone, $email, $status, $dob, $gender, $district, $reason, $lockDays]) {
            DB::table('people')->updateOrInsert(['email' => $email], [
                'full_name' => $name, 'phone_number' => $phone, 'date_of_birth' => $dob,
                'gender' => $gender, 'district' => $district,
            ]);
            $personId = DB::table('people')->where('email', $email)->value('person_id');

            $exists = DB::table('accounts')->where('username', $username)->exists();
            DB::table('accounts')->updateOrInsert(['username' => $username], array_merge(
                $exists ? [] : ['password' => Hash::make('123')],
                [
                    'role_id' => $tutorRole, 'person_id' => $personId, 'status' => $status,
                    'lock_reason' => $reason,
                    'lock_expires_at' => $lockDays ? now()->addDays($lockDays) : null,
                    'status_updated_at' => now()->subDays(rand(1, 30)),
                ]
            ));
            $ids[$username] = DB::table('accounts')->where('username', $username)->value('account_id');
        }

        // Phần lớp học / giao dịch / lịch sử chỉ tạo 1 lần
        if (DB::table('tutor_requests')->where('notes', '[demo]')->exists()) {
            return;
        }

        $parents = [];
        foreach ([['Phạm Hồng Nhung', '0911000001', 'nhung.pham@gmail.com'], ['Lê Thu Trang', '0911000002', 'trang.le@gmail.com']] as [$name, $phone, $email]) {
            DB::table('people')->updateOrInsert(['email' => $email], ['full_name' => $name, 'phone_number' => $phone, 'district' => 'Cầu Giấy']);
            $parents[] = DB::table('people')->where('email', $email)->value('person_id');
        }

        // Lớp đang dạy: tutor01 có 2 lớp, tutor07 có 1 lớp
        $classes = [
            ['tutor01', $parents[0], 'Toán', 'Lớp 9', 3, 200000],
            ['tutor01', $parents[1], 'Vật lý', 'Lớp 10', 2, 220000],
            ['tutor07', $parents[1], 'Tiếng Anh', 'Lớp 7', 2, 180000],
        ];
        $classIds = [];
        foreach ($classes as [$tutor, $parent, $subject, $grade, $sessions, $fee]) {
            $requestId = DB::table('tutor_requests')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà',
                'sessions_per_week' => $sessions, 'desired_tuition_fee' => $fee, 'status' => 'matched',
                'notes' => '[demo]', 'person_id' => $parent, 'created_at' => now()->subDays(20),
            ]);
            $classIds[$tutor][] = DB::table('tutor_classes')->insertGetId([
                'subject' => $subject, 'grade_level' => $grade, 'learning_format' => 'Tại nhà',
                'sessions_per_week' => $sessions, 'fee_per_session' => $fee, 'location' => 'Cầu Giấy, Hà Nội',
                'status' => 'teaching', 'enrollment_fee' => 500000, 'accepted_at' => now()->subDays(15),
                'person_id' => $parent, 'tutor_request_id' => $requestId, 'account_id' => $ids[$tutor],
            ]);
        }

        // tutor01 đã thanh toán phí nhận lớp; tutor07 còn nợ phí + có yêu cầu hoàn phí chờ xử lý
        DB::table('payment_transactions')->insert([
            'amount' => 500000, 'status' => 'completed', 'transaction_at' => now()->subDays(14),
            'class_id' => $classIds['tutor01'][0], 'account_id' => $ids['tutor01'],
        ]);
        DB::table('payment_transactions')->insert([
            'amount' => 500000, 'status' => 'pending', 'transaction_at' => now()->subDays(10),
            'class_id' => $classIds['tutor07'][0], 'account_id' => $ids['tutor07'],
        ]);
        $paidId = DB::table('payment_transactions')->insertGetId([
            'amount' => 300000, 'status' => 'completed', 'transaction_at' => now()->subDays(12),
            'class_id' => $classIds['tutor07'][0], 'account_id' => $ids['tutor07'],
        ]);
        DB::table('refund_requests')->insert([
            'reason' => 'Phụ huynh đổi lịch, không tiếp tục học.', 'source_type' => 'tutor',
            'expected_refund_amount' => 300000, 'status' => 'pending', 'received_at' => now()->subDays(2),
            'payment_transaction_id' => $paidId,
        ]);

        // Lịch sử hoạt động
        $logs = [
            ['tutor01', 'Nhận lớp Toán 9', 15, 'tutor_classes#' . $classIds['tutor01'][0]],
            ['tutor01', 'Thanh toán phí nhận lớp 500.000 đ', 14, null],
            ['tutor02', 'Hủy buổi dạy không báo trước', 7, null],
            ['tutor02', 'Tạm khóa 14 ngày – Hủy buổi dạy không báo trước 2 lần', 5, 'accounts#' . $ids['tutor02']],
            ['tutor03', 'Đình chỉ vĩnh viễn – Gian lận thông tin bằng cấp', 20, 'accounts#' . $ids['tutor03']],
            ['tutor04', 'Nộp hồ sơ xác minh', 1, null],
            ['tutor05', 'Được yêu cầu bổ sung hồ sơ (hạn 15 ngày)', 4, null],
            ['tutor06', 'Vô hiệu hóa tài khoản theo yêu cầu', 25, 'accounts#' . $ids['tutor06']],
            ['tutor07', 'Gửi yêu cầu hoàn phí 300.000 đ', 2, null],
        ];
        foreach ($logs as [$tutor, $action, $daysAgo, $object]) {
            DB::table('activity_logs')->insert([
                'action' => $action, 'occurred_at' => now()->subDays($daysAgo),
                'affected_object' => $object, 'account_id' => $ids[$tutor],
            ]);
        }
    }
}
