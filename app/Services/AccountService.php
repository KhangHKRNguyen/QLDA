<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Notification;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Nghiệp vụ quản lý tài khoản, bám theo các biểu đồ tuần tự:
 * XemXetTaiKhoan, KhoaTaiKhoan (kèm Scheduler tự mở khóa), XoaTaiKhoan.
 *
 * Mỗi thao tác: cập nhật accounts → gửi notifications cho chủ tài khoản → ghi activity_logs.
 * Vi phạm điều kiện nghiệp vụ thì ném DomainException (controller hiển thị thành toast lỗi).
 */
class AccountService
{
    /** Trạng thái lớp (tutor_classes.status) được coi là "đang dạy". Thống nhất với nhóm làm module Lớp học. */
    public const TEACHING_CLASS_STATUSES = ['teaching'];

    public const MIN_LOCK_DAYS = 7;
    public const MAX_LOCK_DAYS = 30;

    /** Xem xét: không vi phạm → chỉ ghi nhận kết quả */
    public function markReviewed(Account $account, ?string $note, ?int $actorId): void
    {
        $this->log($account, 'Xem xét: không vi phạm' . ($note ? " – {$note}" : ''), $actorId);
    }

    /** Vi phạm nhẹ → tạm khóa 7–30 ngày */
    public function lock(Account $account, string $reason, int $days, ?int $actorId): void
    {
        if ($account->status !== 'active') {
            throw new DomainException('Chỉ tạm khóa được tài khoản đang hoạt động.');
        }

        $until = now()->addDays($days);

        DB::transaction(function () use ($account, $reason, $days, $until, $actorId) {
            $this->changeStatus($account, 'locked', $reason, $until);
            $this->notify($account, "Tài khoản của bạn bị tạm khóa {$days} ngày, đến {$until->format('d/m/Y H:i')}. Lý do: {$reason}");
            $this->log($account, "Tạm khóa {$days} ngày – {$reason}", $actorId);
        });
    }

    /** Vi phạm nghiêm trọng → đình chỉ vĩnh viễn */
    public function suspend(Account $account, string $reason, ?int $actorId): void
    {
        if (! in_array($account->status, ['active', 'locked'], true)) {
            throw new DomainException('Không thể đình chỉ tài khoản ở trạng thái hiện tại.');
        }

        DB::transaction(function () use ($account, $reason, $actorId) {
            $this->changeStatus($account, 'suspended', $reason, null);
            $this->notify($account, "Tài khoản của bạn đã bị đình chỉ vĩnh viễn và không thể khôi phục. Lý do: {$reason}");
            $this->log($account, "Đình chỉ vĩnh viễn – {$reason}", $actorId);
        });
    }

    public function unlock(Account $account, ?int $actorId, bool $automatic = false): void
    {
        if ($account->status !== 'locked') {
            throw new DomainException('Tài khoản không ở trạng thái tạm khóa.');
        }

        DB::transaction(function () use ($account, $actorId, $automatic) {
            $this->changeStatus($account, 'active', null, null);
            $this->notify($account, $automatic
                ? 'Thời hạn tạm khóa đã kết thúc, tài khoản của bạn đã hoạt động trở lại.'
                : 'Tài khoản của bạn đã được mở khóa và hoạt động trở lại.');
            $this->log($account, $automatic ? 'Tự động mở khóa (hết thời hạn)' : 'Mở khóa tài khoản', $actorId);
        });
    }

    /** Vô hiệu hóa: chuyển "Ngừng hoạt động" (không xóa cứng để giữ lịch sử lớp, giao dịch) */
    public function deactivate(Account $account, string $reason, ?int $actorId): void
    {
        if (! in_array($account->status, ['active', 'locked'], true)) {
            throw new DomainException('Không thể vô hiệu hóa tài khoản ở trạng thái hiện tại.');
        }
        if ($blockers = $this->blockerMessages($account)) {
            throw new DomainException('Chưa đủ điều kiện vô hiệu hóa: ' . implode('; ', $blockers) . '.');
        }

        DB::transaction(function () use ($account, $reason, $actorId) {
            $this->changeStatus($account, 'inactive', $reason, null);
            $this->notify($account, 'Tài khoản của bạn đã ngừng hoạt động.');
            $this->log($account, "Vô hiệu hóa tài khoản – {$reason}", $actorId);
        });
    }

    /** Scheduler: mở khóa các tài khoản đã hết hạn tạm khóa. Trả về số tài khoản được mở. */
    public function unlockExpired(): int
    {
        $expired = Account::where('status', 'locked')
            ->whereNotNull('lock_expires_at')
            ->where('lock_expires_at', '<=', now())
            ->get();

        $expired->each(fn (Account $a) => $this->unlock($a, null, automatic: true));

        return $expired->count();
    }

    /** Số liệu dùng để kiểm tra điều kiện vô hiệu hóa */
    public function deactivationChecks(Account $account): array
    {
        $id = $account->account_id;

        return [
            'active_classes' => DB::table('tutor_classes')
                ->where('account_id', $id)
                ->whereIn('status', self::TEACHING_CLASS_STATUSES)
                ->count(),
            'unpaid_fees' => (float) DB::table('payment_transactions')
                ->where('account_id', $id)
                ->where('status', 'pending')
                ->sum('amount'),
            'pending_refunds' => DB::table('refund_requests')
                ->join('payment_transactions', 'payment_transactions.payment_transaction_id', '=', 'refund_requests.payment_transaction_id')
                ->where('payment_transactions.account_id', $id)
                ->where('refund_requests.status', 'pending')
                ->count(),
        ];
    }

    /** Lý do chưa thể vô hiệu hóa (mảng rỗng = đủ điều kiện) */
    public function blockerMessages(Account $account): array
    {
        $c = $this->deactivationChecks($account);

        return array_values(array_filter([
            $c['active_classes'] > 0 ? "còn {$c['active_classes']} lớp đang dạy" : null,
            $c['unpaid_fees'] > 0 ? 'còn nợ phí ' . number_format($c['unpaid_fees'], 0, ',', '.') . ' đ' : null,
            $c['pending_refunds'] > 0 ? "còn {$c['pending_refunds']} yêu cầu hoàn phí chờ xử lý" : null,
        ]));
    }

    /** Lịch sử: thao tác do tài khoản thực hiện + thao tác tác động lên tài khoản */
    public function history(Account $account, int $limit = 20)
    {
        return ActivityLog::with('account')
            ->where(fn ($q) => $q
                ->where('account_id', $account->account_id)
                ->orWhere('affected_object', self::objectRef($account)))
            ->orderByDesc('occurred_at')
            ->orderByDesc('activity_log_id')
            ->limit($limit)
            ->get();
    }

    public static function objectRef(Account $account): string
    {
        return 'accounts#' . $account->account_id;
    }

    private function changeStatus(Account $account, string $status, ?string $reason, $expiresAt): void
    {
        // Dùng query trực tiếp vì $account có thể mang cột join (full_name, role_name...) không thuộc bảng accounts
        Account::whereKey($account->account_id)->update([
            'status'            => $status,
            'lock_reason'       => $reason,
            'lock_expires_at'   => $expiresAt,
            'status_updated_at' => now(),
        ]);
        $account->status = $status;
    }

    private function notify(Account $account, string $content): void
    {
        Notification::create([
            'content'          => $content,
            'delivery_channel' => 'system',
            'account_id'       => $account->account_id,
        ]);
    }

    /**
     * activity_logs.account_id là người thực hiện (bắt buộc).
     * TODO: khi có đăng nhập, controller truyền account_id của nhân viên; hiện chưa có auth
     * nên tạm ghi theo chính tài khoản bị tác động.
     */
    private function log(Account $account, string $action, ?int $actorId): void
    {
        ActivityLog::create([
            'action'          => mb_substr($action, 0, 100),
            'occurred_at'     => now(),
            'affected_object' => self::objectRef($account),
            'account_id'      => $actorId ?? $account->account_id,
        ]);
    }
}
