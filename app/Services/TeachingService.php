<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\RefundRequest;
use App\Models\TutorClass;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Quy trình giảng dạy sau khi Gia sư nhận lớp (PTTKHT 2.1.8, HĐ2–HĐ7; SRS BR-05, BR-06).
 *
 * Trạng thái lớp dùng chung với các phần khác của nhóm:
 *   pending (Đang tuyển – Quang), processing (đang giữ chỗ – Quang/Khang), hidden, canceled,
 *   teaching (đã có gia sư, Đang được bảo hành / đang dạy), refund_processing, completed.
 * Phần "Gia sư nhận lớp" (Khang) cần đặt lớp về status = 'teaching', teaching_stage = 'trial', accepted_at = now()
 * sau khi thanh toán phí nhận lớp thành công.
 */
class TeachingService
{
    /** Thời hạn bảo hành kể từ ngày nhận lớp (BR-06) */
    public const WARRANTY_DAYS = 60;

    /** status => [nhãn, tone] */
    public const CLASS_STATUSES = [
        'pending'           => ['Đang tuyển gia sư', 'info'],
        'processing'        => ['Đang giữ chỗ', 'warning'],
        'hidden'            => ['Đã ẩn', 'neutral'],
        'canceled'          => ['Đã đóng', 'neutral'],
        'teaching'          => ['Đang dạy', 'success'],
        'refund_processing' => ['Đang xử lý hoàn phí', 'warning'],
        'completed'         => ['Hoàn thành', 'success'],
    ];

    public const STAGES = [
        'trial'    => 'Đang dạy thử',
        'official' => 'Dạy chính thức',
    ];

    /** Các trạng thái lớp "của gia sư" (đã nhận lớp) — hiện ở "Lớp của tôi" và "Lớp đang dạy" */
    public const ASSIGNED_STATUSES = ['teaching', 'refund_processing', 'completed'];

    public static function statusLabel(TutorClass $class): string
    {
        if ($class->status === 'teaching') {
            return self::inWarranty($class) ? 'Đang được bảo hành' : 'Đang dạy';
        }

        return self::CLASS_STATUSES[$class->status][0] ?? (string) $class->status;
    }

    public static function statusTone(TutorClass $class): string
    {
        return self::CLASS_STATUSES[$class->status][1] ?? 'neutral';
    }

    public static function stageLabel(TutorClass $class): ?string
    {
        return self::STAGES[$class->teaching_stage] ?? null;
    }

    public static function warrantyUntil(TutorClass $class): ?Carbon
    {
        return $class->accepted_at ? Carbon::parse($class->accepted_at)->addDays(self::WARRANTY_DAYS) : null;
    }

    public static function inWarranty(TutorClass $class): bool
    {
        return ($until = self::warrantyUntil($class)) !== null && now()->lt($until);
    }

    /** Số ngày bảo hành còn lại (0 khi đã hết) */
    public static function warrantyDaysLeft(TutorClass $class): int
    {
        $until = self::warrantyUntil($class);

        return $until && now()->lt($until) ? (int) ceil(now()->diffInDays($until)) : 0;
    }

    /** Giao dịch thanh toán phí nhận lớp thành công gần nhất của gia sư hiện tại */
    public static function paidTransaction(TutorClass $class): ?PaymentTransaction
    {
        return PaymentTransaction::where('class_id', $class->class_id)
            ->where('account_id', $class->account_id)
            ->where('status', 'completed')
            ->latest('payment_transaction_id')
            ->first();
    }

    public static function openRefund(TutorClass $class): ?RefundRequest
    {
        return RefundRequest::whereHas('payment', fn ($q) => $q->where('class_id', $class->class_id))
            ->whereIn('status', RefundRequest::OPEN_STATUSES)
            ->latest('refund_request_id')
            ->first();
    }

    /**
     * Điều kiện hiện chức năng "Yêu cầu hoàn phí" (FR-HP-01, HĐ7):
     * lớp "Đang được bảo hành", còn trong 60 ngày, chưa có yêu cầu đang chờ xử lý.
     * Trả về null nếu đủ điều kiện, ngược lại là lý do (MSG-16, MSG-17).
     */
    public static function refundBlocker(TutorClass $class): ?string
    {
        if (self::openRefund($class)) {
            return 'Lớp đã có yêu cầu hoàn phí đang được xử lý.';
        }
        if ($class->status !== 'teaching') {
            return 'Lớp đang ở trạng thái "' . self::statusLabel($class) . '", không thể yêu cầu hoàn phí.';
        }
        if (! self::inWarranty($class)) {
            return 'Lớp đã hết thời hạn yêu cầu hoàn phí (' . self::WARRANTY_DAYS . ' ngày kể từ ngày nhận lớp).';
        }
        if (! self::paidTransaction($class)) {
            return 'Lớp chưa có giao dịch thanh toán phí nhận lớp thành công.';
        }

        return null;
    }

    /** NV QLKH ghi nhận phản hồi của phụ huynh sau buổi dạy thử (HĐ4) */
    public function recordTrial(TutorClass $class, bool $agreed, int $actorId): void
    {
        if ($class->status !== 'teaching' || $class->teaching_stage !== 'trial') {
            throw new DomainException('Lớp không ở giai đoạn dạy thử.');
        }

        DB::transaction(function () use ($class, $agreed, $actorId) {
            $class->update([
                'teaching_stage'   => $agreed ? 'official' : 'trial',
                'trial_decided_at' => now(),
            ]);

            $text = $agreed
                ? "Phụ huynh đồng ý học chính thức lớp {$class->subject} – {$class->grade_level}. Chúc bạn giảng dạy hiệu quả!"
                : "Phụ huynh báo nghỉ sau buổi dạy thử lớp {$class->subject} – {$class->grade_level}. Trung tâm sẽ tạo yêu cầu hoàn phí.";
            $this->notify($class->account_id, $text);
            $this->log($class, $agreed ? 'Ghi nhận: phụ huynh đồng ý dạy chính thức' : 'Ghi nhận: phụ huynh báo nghỉ sau dạy thử', $actorId);
        });
    }

    /** Lớp kết thúc đúng thỏa thuận (HĐ6) */
    public function complete(TutorClass $class, int $actorId): void
    {
        if ($class->status !== 'teaching') {
            throw new DomainException('Chỉ đánh dấu hoàn thành khi lớp đang dạy.');
        }

        DB::transaction(function () use ($class, $actorId) {
            $class->update(['status' => 'completed', 'completed_at' => now()]);
            $this->notify($class->account_id, "Lớp {$class->subject} – {$class->grade_level} đã được ghi nhận Hoàn thành.");
            $this->log($class, 'Lớp hoàn thành', $actorId);
        });
    }

    public static function objectRef(TutorClass $class): string
    {
        return 'tutor_classes#' . $class->class_id;
    }

    private function notify(?int $accountId, string $content): void
    {
        if ($accountId) {
            Notification::create(['content' => $content, 'delivery_channel' => 'system', 'account_id' => $accountId]);
        }
    }

    private function log(TutorClass $class, string $action, int $actorId): void
    {
        ActivityLog::create([
            'action'          => mb_substr($action, 0, 100),
            'occurred_at'     => now(),
            'affected_object' => self::objectRef($class),
            'account_id'      => $actorId,
        ]);
    }
}
