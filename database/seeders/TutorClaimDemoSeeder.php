<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\PaymentTransaction;
use App\Models\Person;
use App\Models\TutorClass;
use App\Models\TutorRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dữ liệu demo cho quy trình 2.1.5 – Gia sư nhận lớp (HĐ1–HĐ10).
 *
 * Điều kiện chạy:
 * - Đã chạy RoleSeeder, AccountSeeder và DemoAccountSeeder.
 * - Đã chạy migration bổ sung các trường giữ chỗ/thanh toán của 2.1.5.
 *
 * Seeder chỉ tái sử dụng tài khoản và khách hàng hiện có; không tạo tài khoản mới.
 * Các bản ghi do Seeder quản lý được nhận diện bằng tag [DEMO-2.1.5:...].
 * Chạy lại sẽ cập nhật đúng các bản ghi demo này, không tạo thêm bản sao.
 */
class TutorClaimDemoSeeder extends Seeder
{
    private const TAG_PREFIX = '[DEMO-2.1.5:';

    public function run(): void
    {
        $accounts = Account::query()->with('role')->get();

        $activeTutors = $accounts->filter(
            fn (Account $account) => $account->role?->role_name === 'Gia sư'
                && $account->status === 'active'
        )->values();

        if ($activeTutors->isEmpty()) {
            throw new RuntimeException(
                'Không tìm thấy tài khoản Gia sư đang hoạt động. '
                . 'Hãy chạy RoleSeeder, AccountSeeder và DemoAccountSeeder trước.'
            );
        }

        // Dùng tutor01 làm tài khoản đăng nhập kiểm thử nếu tài khoản này đang hoạt động.
        $testTutor = $activeTutors->firstWhere('username', 'tutor01') ?? $activeTutors->first();

        // Dùng một Gia sư khác giữ lớp để tutor01 kiểm thử được trường hợp HĐ3: người khác đang giữ chỗ.
        $reservationOwner = $activeTutors->first(
            fn (Account $account) => (int) $account->account_id !== (int) $testTutor->account_id
        );

        if (! $reservationOwner) {
            // Vẫn cho phép chạy Seeder khi hệ thống chỉ có một Gia sư, nhưng cảnh báo kịch bản giữ chỗ
            // bởi người khác sẽ không kiểm thử được cho đến khi có thêm một tài khoản Gia sư active.
            $reservationOwner = $testTutor;
            $this->command?->warn(
                'Chỉ có một Gia sư active. Kịch bản RESERVED-CURRENT sẽ do chính tài khoản kiểm thử giữ; '
                . 'hãy kích hoạt thêm tutor07 để kiểm thử trường hợp Gia sư khác đang giữ lớp.'
            );
        }

        // Nhân viên tạo lớp: ưu tiên nhân viên Quản lý Khách hàng đã được AccountSeeder tạo.
        $creator = $accounts->firstWhere('username', 'qlkh01')
            ?? $accounts->first(fn (Account $account) => $account->role?->role_name !== 'Gia sư')
            ?? $testTutor;

        // Ưu tiên hồ sơ khách hàng đã được các Seeder hiện tại tạo, tránh lấy nhầm hồ sơ nhân viên.
        $customer = null;
        foreach ([
            'parent01@gmail.com',
            'nhung.pham@gmail.com',
            'trang.le@gmail.com',
        ] as $email) {
            $customer = Person::query()->where('email', $email)->first();
            if ($customer) {
                break;
            }
        }

        if (! $customer) {
            throw new RuntimeException(
                'Không tìm thấy khách hàng demo (parent01@gmail.com, nhung.pham@gmail.com hoặc trang.le@gmail.com). '
                . 'Hãy chạy AccountSeeder và DemoAccountSeeder trước.'
            );
        }

        $now = now();
        $paymentProvider = 'mock'; // Phải khớp provider mà ClassPaymentController dùng khi mô phỏng thanh toán.

        $scenarios = [
            [
                'key' => 'OPEN-MATH-CAUGIAY',
                'subject' => 'Toán',
                'grade_level' => 'Lớp 9',
                'learning_format' => 'Trực tiếp',
                'schedule' => 'Thứ 2, 4, 6 – 18:00 đến 19:30',
                'sessions_per_week' => 3,
                'fee_per_session' => 180000,
                'enrollment_fee' => 900000,
                'location' => 'Quận Cầu Giấy, Hà Nội',
                'status' => TutorClass::STATUS_OPEN,
                'request_notes' => 'Ưu tiên Gia sư có kinh nghiệm ôn thi vào lớp 10, phương pháp giảng dễ hiểu.',
                'state' => 'open',
            ],
            [
                'key' => 'OPEN-ENGLISH-ONLINE',
                'subject' => 'Tiếng Anh',
                'grade_level' => 'Lớp 6',
                'learning_format' => 'Online',
                'schedule' => 'Thứ 3, 5 – 19:00 đến 20:30',
                'sessions_per_week' => 2,
                'fee_per_session' => 160000,
                'enrollment_fee' => 750000,
                'location' => 'Trực tuyến',
                'status' => TutorClass::STATUS_OPEN,
                'request_notes' => 'Cần Gia sư củng cố ngữ pháp và giao tiếp cơ bản cho học sinh lớp 6.',
                'state' => 'open',
            ],
            [
                'key' => 'OPEN-PHYSICS-DONGDA',
                'subject' => 'Vật lý',
                'grade_level' => 'Lớp 10',
                'learning_format' => 'Trực tiếp',
                'schedule' => 'Thứ 7, Chủ nhật – 09:00 đến 10:30',
                'sessions_per_week' => 2,
                'fee_per_session' => 200000,
                'enrollment_fee' => 950000,
                'location' => 'Quận Đống Đa, Hà Nội',
                'status' => TutorClass::STATUS_OPEN,
                'request_notes' => 'Gia sư nắm chắc kiến thức Vật lý THPT, có thể dạy theo chương trình hiện hành.',
                'state' => 'open',
            ],
            [
                'key' => 'OPEN-LITERATURE-THANHXUAN',
                'subject' => 'Ngữ văn',
                'grade_level' => 'Lớp 8',
                'learning_format' => 'Kết hợp',
                'schedule' => 'Thứ 4, Chủ nhật – 17:00 đến 18:30',
                'sessions_per_week' => 2,
                'fee_per_session' => 150000,
                'enrollment_fee' => 700000,
                'location' => 'Quận Thanh Xuân, Hà Nội',
                'status' => TutorClass::STATUS_OPEN,
                'request_notes' => 'Cần hỗ trợ kỹ năng đọc hiểu, lập dàn ý và viết bài văn.',
                'state' => 'open',
            ],
            [
                'key' => 'RESERVED-CURRENT',
                'subject' => 'Hóa học',
                'grade_level' => 'Lớp 11',
                'learning_format' => 'Online',
                'schedule' => 'Thứ 2, 5 – 20:00 đến 21:30',
                'sessions_per_week' => 2,
                'fee_per_session' => 190000,
                'enrollment_fee' => 850000,
                'location' => 'Trực tuyến',
                'status' => TutorClass::STATUS_RESERVED,
                'request_notes' => 'Dữ liệu demo kiểm tra trường hợp lớp đang được Gia sư khác giữ chỗ.',
                'state' => 'reserved_current',
            ],
            [
                'key' => 'RESERVED-EXPIRED',
                'subject' => 'Sinh học',
                'grade_level' => 'Lớp 12',
                'learning_format' => 'Trực tiếp',
                'schedule' => 'Thứ 3, 6 – 18:30 đến 20:00',
                'sessions_per_week' => 2,
                'fee_per_session' => 180000,
                'enrollment_fee' => 800000,
                'location' => 'Quận Hai Bà Trưng, Hà Nội',
                'status' => TutorClass::STATUS_RESERVED,
                'request_notes' => 'Dữ liệu demo kiểm tra cơ chế tự mở khóa khi hết 5 phút.',
                'state' => 'reserved_expired',
            ],
            [
                'key' => 'PAID-TEACHING-TRIAL',
                'subject' => 'Toán',
                'grade_level' => 'Lớp 7',
                'learning_format' => 'Trực tiếp',
                'schedule' => 'Thứ 2, 4 – 17:30 đến 19:00',
                'sessions_per_week' => 2,
                'fee_per_session' => 170000,
                'enrollment_fee' => 800000,
                'location' => 'Quận Ba Đình, Hà Nội',
                'status' => TutorClass::STATUS_TEACHING,
                'request_notes' => 'Dữ liệu demo lớp đã nhận và thanh toán thành công; đang ở giai đoạn dạy thử.',
                'state' => 'paid',
            ],
        ];

        $seededClassIds = DB::transaction(function () use (
            $scenarios,
            $customer,
            $creator,
            $testTutor,
            $reservationOwner,
            $paymentProvider,
            $now
        ): array {
            $classIds = [];

            foreach ($scenarios as $scenario) {
                $tag = self::TAG_PREFIX . $scenario['key'] . ']';

                $request = TutorRequest::query()
                    ->where('notes', 'like', $tag . '%')
                    ->first() ?? new TutorRequest();

                $request->fill([
                    'person_id' => $customer->person_id,
                    'subject' => $scenario['subject'],
                    'grade_level' => $scenario['grade_level'],
                    'learning_format' => $scenario['learning_format'],
                    'schedule' => $scenario['schedule'],
                    'sessions_per_week' => $scenario['sessions_per_week'],
                    'desired_tuition_fee' => $scenario['fee_per_session'],
                    'status' => 'confirmed',
                    'sla_deadline' => null,
                    'cancellation_reason' => null,
                ]);
                $request->notes = $tag . ' ' . $scenario['request_notes'];
                $request->save();

                $isPaid = $scenario['state'] === 'paid';
                $isReserved = in_array($scenario['state'], ['reserved_current', 'reserved_expired'], true);
                $classAccountId = $isPaid ? $testTutor->account_id : $creator->account_id;

                $classAttributes = [
                    'person_id' => $customer->person_id,
                    // Theo cấu trúc hiện tại: trước khi nhận lớp, account_id là nhân viên tạo lớp;
                    // sau khi thanh toán thành công, ClassPaymentService chuyển account_id sang Gia sư.
                    'account_id' => $classAccountId,
                    'subject' => $scenario['subject'],
                    'grade_level' => $scenario['grade_level'],
                    'learning_format' => $scenario['learning_format'],
                    'schedule' => $scenario['schedule'],
                    'sessions_per_week' => $scenario['sessions_per_week'],
                    'fee_per_session' => $scenario['fee_per_session'],
                    'enrollment_fee' => $scenario['enrollment_fee'],
                    'location' => $scenario['location'],
                    'status' => $scenario['status'],
                    'accepted_at' => $isPaid ? $now->copy()->subDay() : null,
                    'teaching_stage' => $isPaid ? 'trial' : null,
                    'trial_decided_at' => null,
                    'completed_at' => null,
                    'locked_at' => null,
                    'locked_by_account_id' => null,
                    'lock_expires_at' => null,
                ];

                if ($scenario['state'] === 'reserved_current') {
                    $classAttributes['locked_at'] = $now->copy();
                    $classAttributes['locked_by_account_id'] = $reservationOwner->account_id;
                    $classAttributes['lock_expires_at'] = $now->copy()->addMinutes(5);
                } elseif ($scenario['state'] === 'reserved_expired') {
                    $classAttributes['locked_at'] = $now->copy()->subMinutes(6);
                    $classAttributes['locked_by_account_id'] = $reservationOwner->account_id;
                    $classAttributes['lock_expires_at'] = $now->copy()->subMinute();
                }

                /** @var TutorClass $class */
                $class = TutorClass::query()->updateOrCreate(
                    ['tutor_request_id' => $request->tutor_request_id],
                    $classAttributes
                );

                $classIds[$scenario['key']] = [
                    'class_id' => $class->class_id,
                    'payment_transaction_id' => null,
                ];

                if ($isReserved || $isPaid) {
                    $isExpired = $scenario['state'] === 'reserved_expired';
                    $paymentAccount = $isPaid ? $testTutor : $reservationOwner;
                    $reference = 'DEMO-2.1.5-' . $scenario['key'];

                    // Giá trị phải bằng tuyệt đối lock_expires_at trên lớp để sameReservation nhận diện đúng.
                    $reservationExpiresAt = $isExpired
                        ? $now->copy()->subMinute()
                        : ($isPaid ? $now->copy()->subHour() : $now->copy()->addMinutes(5));

                    $payment = PaymentTransaction::query()->updateOrCreate(
                        ['external_reference' => $reference],
                        [
                            'class_id' => $class->class_id,
                            'account_id' => $paymentAccount->account_id,
                            'amount' => $scenario['enrollment_fee'],
                            'transaction_at' => $isPaid ? $now->copy()->subDay() : $now->copy(),
                            'reservation_expires_at' => $reservationExpiresAt,
                            'paid_at' => $isPaid ? $now->copy()->subDay() : null,
                            'status' => $isPaid
                                ? PaymentTransaction::STATUS_SUCCESS
                                : PaymentTransaction::STATUS_PENDING,
                            // ClassPaymentController mô phỏng webhook với provider 'mock'.
                            'payment_provider' => $paymentProvider,
                            'gateway_payload' => [
                                'demo' => true,
                                'scenario' => $scenario['state'],
                                'seed' => self::class,
                            ],
                        ]
                    );

                    $classIds[$scenario['key']]['payment_transaction_id'] = $payment->payment_transaction_id;
                }
            }

            return $classIds;
        });

        $this->command?->info('Đã tạo/cập nhật 7 lớp demo cho quy trình Gia sư nhận lớp 2.1.5.');
        $this->command?->line('4 lớp đang tuyển, 1 lớp đang giữ chỗ, 1 lớp giữ chỗ hết hạn và 1 lớp đã thanh toán/đang dạy thử.');
        $this->command?->line('Tài khoản đăng nhập kiểm thử: ' . $testTutor->username);
        $this->command?->line('Tài khoản giữ lớp RESERVED-CURRENT: ' . $reservationOwner->username);

        $this->command?->table(
            ['Kịch bản', 'Mã lớp', 'Mã giao dịch'],
            collect($seededClassIds)->map(fn (array $item, string $key) => [
                $key,
                $item['class_id'],
                $item['payment_transaction_id'] ?? '—',
            ])->values()->all()
        );
    }
}
