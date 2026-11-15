<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Yêu cầu hoàn phí (SRS 3.7, bảng 6.3, trạng thái 6.5).
 * Luồng: pending/overdue → decided → (xác nhận) refunding → refunded | completed
 *                                  → (xem xét lại) reconsidering → refunding | rejected_final | completed
 */
class RefundRequest extends Model
{
    protected $primaryKey = 'refund_request_id';

    public $timestamps = false;

    protected $guarded = ['refund_request_id'];

    /** Thời hạn Quản lý Trung tâm xử lý (BR 6.4) */
    public const PROCESSING_DAYS = 15;

    /** Giá trị cột status => [nhãn, tone badge] */
    public const STATUSES = [
        'pending'        => ['Chờ Trung tâm xác minh', 'info'],
        'overdue'        => ['Quá hạn', 'danger'],
        'decided'        => ['Đã có kết quả – chờ xác nhận', 'warning'],
        'reconsidering'  => ['Yêu cầu xem xét lại', 'warning'],
        'refunding'      => ['Đang hoàn tiền', 'info'],
        'refunded'       => ['Hoàn tiền thành công', 'success'],
        'refund_failed'  => ['Hoàn tiền thất bại', 'danger'],
        'rejected_final' => ['Từ chối hoàn phí – Lần cuối', 'neutral'],
        'completed'      => ['Hoàn tất', 'success'],
    ];

    /** Trạng thái Quản lý cần ra quyết định (lần đầu hoặc cuối cùng) */
    public const AWAITING_DECISION = ['pending', 'overdue', 'reconsidering'];

    /** Trạng thái chưa kết thúc — chặn gửi yêu cầu mới cho lớp (BR-07) và chặn vô hiệu hóa tài khoản */
    public const OPEN_STATUSES = ['pending', 'overdue', 'decided', 'reconsidering', 'refunding', 'refund_failed'];

    /** Bảng mức hoàn phí 6.3: mã => [lý do, tỷ lệ hoàn, Gia sư được chọn] */
    public const REASONS = [
        'center_fault'      => ['Trung tâm cung cấp sai thông tin lớp; Phụ huynh đổi ý trước khi Gia sư liên hệ; ba bên không thỏa thuận được yêu cầu phát sinh', 1.00, true],
        'teaching_method'   => ['Phụ huynh không hài lòng cách trao đổi hoặc phương pháp dạy', 0.75, false],
        'trial_quit'        => ['Phụ huynh báo nghỉ sau 1 đến 2 buổi dạy thử (có hỗ trợ lương dạy thử)', 0.75, false],
        'short_term_change' => ['Phụ huynh đổi nhu cầu ngắn hạn dưới 60 ngày sau dạy thử thành công', 0.75, false],
        'quality_feedback'  => ['Phụ huynh phản hồi không tốt về tác phong, kỹ năng, chuyên môn', 0.50, false],
        'tutor_quit'        => ['Gia sư vi phạm nghĩa vụ, hoặc Gia sư không còn nhu cầu dạy', 0.00, true],
    ];

    /** Nguồn phát sinh (source_type) */
    public const SOURCES = [
        'tutor' => 'Gia sư gửi',
        'staff' => 'Nhân viên QLKH tạo theo phản ánh của Phụ huynh',
    ];

    /** Cách xử lý lớp sau khi kết thúc hoàn phí (FR-HP-13) => [nhãn, trạng thái lớp] */
    public const CLASS_OUTCOMES = [
        'reopen'   => ['Khách hàng vẫn cần gia sư — mở lại tuyển gia sư', 'pending'],
        'close'    => ['Khách hàng không còn nhu cầu — đóng lớp', 'canceled'],
        'continue' => ['Giữ nguyên — gia sư tiếp tục dạy', 'teaching'],
    ];

    protected function casts(): array
    {
        return [
            'expected_refund_amount' => 'decimal:0',
            'actual_refund_amount'   => 'decimal:0',
            'received_at'            => 'datetime',
            'decided_at'             => 'datetime',
            'reconsidered'           => 'boolean',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id', 'payment_transaction_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'requested_by', 'account_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'decided_by', 'account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class, 'refund_request_id', 'refund_request_id');
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(RefundTransaction::class, 'refund_request_id', 'refund_request_id')->latestOfMany('refund_transaction_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'neutral';
    }

    public function reasonLabel(): ?string
    {
        return self::REASONS[$this->reason_code][0] ?? null;
    }

    public function reasonRate(): ?float
    {
        return self::REASONS[$this->reason_code][1] ?? null;
    }

    /** Hạn xử lý = ngày tiếp nhận + 15 ngày */
    public function deadline(): ?Carbon
    {
        return $this->received_at?->copy()->addDays(self::PROCESSING_DAYS);
    }

    /** Số ngày còn lại trước hạn (âm = đã quá); null khi không còn chờ quyết định lần đầu */
    public function daysLeft(): ?int
    {
        if (! in_array($this->status, ['pending', 'overdue'], true) || ! $this->deadline()) {
            return null;
        }

        return (int) floor(now()->diffInDays($this->deadline(), false));
    }

    public function awaitingDecision(): bool
    {
        return in_array($this->status, self::AWAITING_DECISION, true);
    }

    /** Đang ở bước quyết định cuối cùng (sau khi người gửi yêu cầu xem xét lại) */
    public function isFinalDecision(): bool
    {
        return $this->status === 'reconsidering';
    }

    public static function objectRef(self $request): string
    {
        return 'refund_requests#' . $request->refund_request_id;
    }
}
