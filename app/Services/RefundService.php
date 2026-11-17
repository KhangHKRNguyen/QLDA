<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\RefundRequest;
use App\Models\RefundTransaction;
use App\Models\TutorClass;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Nghiệp vụ "Xử lý hoàn phí" (PTTKHT 2.2.9.2, SRS FR-HP-05 → FR-HP-13).
 *
 * Phía Quản lý Trung tâm: approve() / reject() cho cả quyết định lần đầu và quyết định cuối cùng,
 * retryRefund() khi hoàn tiền thất bại.
 * Phía người gửi (Gia sư / NV QLKH — giao diện làm ở phần "thêm vào các role khác"):
 * confirm() và requestReconsideration().
 * Tự động: markOverdue() (quá 15 ngày), syncPendingRefunds() (hỏi lại cổng thanh toán).
 */
class RefundService
{
    public function __construct(private RefundGateway $gateway) {}

    /**
     * Gửi yêu cầu hoàn phí (FR-HP-01 → FR-HP-04).
     * $source: 'tutor' (Gia sư — chỉ lý do được phép) | 'staff' (NV QLKH — cả 6 lý do).
     */
    public function submit(TutorClass $class, string $reasonCode, string $content, ?string $evidenceFile, string $source, int $actorId): RefundRequest
    {
        if ($blocker = TeachingService::refundBlocker($class)) {
            throw new DomainException($blocker);
        }

        $reason = RefundRequest::REASONS[$reasonCode] ?? throw new DomainException('Lý do hoàn phí không hợp lệ.');

        if ($source === 'tutor' && ! $reason[2]) {
            throw new DomainException('Gia sư không được chọn lý do này. Vui lòng liên hệ Trung tâm.');
        }

        $payment = TeachingService::paidTransaction($class);

        return DB::transaction(function () use ($class, $reasonCode, $reason, $content, $evidenceFile, $source, $actorId, $payment) {
            $request = RefundRequest::create([
                'reason'                 => $content,
                'reason_code'            => $reasonCode,
                'source_type'            => $source,
                'expected_refund_amount' => round((float) $payment->amount * $reason[1]),
                'status'                 => 'pending',
                'received_at'            => now(),
                'evidence_file'          => $evidenceFile,
                'payment_transaction_id' => $payment->payment_transaction_id,
                'requested_by'           => $actorId,
            ]);
            $request->setRelation('payment', $payment);

            $class->update(['status' => 'refund_processing']);

            $this->log($request, 'Gửi yêu cầu hoàn phí – dự kiến ' . self::money($request->expected_refund_amount), $actorId);
            $this->notifyMany($this->managers(), "Có yêu cầu hoàn phí mới #{$request->refund_request_id} (lớp {$class->subject} – {$class->grade_level}), hạn xử lý "
                . $request->deadline()->format('d/m/Y') . '.');

            if ($source === 'staff' && $class->account_id) {
                $this->notifyMany(Account::whereKey($class->account_id)->get(),
                    "Trung tâm đã tạo yêu cầu hoàn phí #{$request->refund_request_id} cho lớp {$class->subject} – {$class->grade_level} theo phản ánh của phụ huynh.");
            }

            return $request;
        });
    }

    /** Giám đốc yêu cầu Quản lý tiếp tục xử lý yêu cầu quá hạn (luồng phụ 1) */
    public function escalate(RefundRequest $request, ?string $note, int $directorId): void
    {
        if ($request->status !== 'overdue') {
            throw new DomainException('Chỉ nhắc xử lý các yêu cầu đang quá hạn.');
        }

        DB::transaction(function () use ($request, $note, $directorId) {
            $this->log($request, 'Giám đốc yêu cầu Quản lý tiếp tục xử lý' . ($note ? ": {$note}" : ''), $directorId);
            $this->notifyMany($this->managers(), "Giám đốc yêu cầu xử lý ngay yêu cầu hoàn phí #{$request->refund_request_id} (đã quá hạn)."
                . ($note ? " Ghi chú: {$note}" : ''));
        });
    }

    /** Duyệt: giữ mức dự kiến ($amount = null) hoặc điều chỉnh mức hoàn chính thức (kèm lý do) */
    public function approve(RefundRequest $request, ?float $amount, ?string $note, string $classOutcome, int $actorId): void
    {
        $this->ensureAwaitingDecision($request);

        $amount ??= (float) $request->expected_refund_amount;
        $paid = (float) $request->payment->amount;

        if ($amount < 0 || $amount > $paid) {
            throw new DomainException('Mức hoàn phải từ 0 đến ' . self::money($paid) . ' (phí nhận lớp đã thanh toán).');
        }
        if ($amount != (float) $request->expected_refund_amount && blank($note)) {
            throw new DomainException('Cần nhập lý do khi điều chỉnh mức hoàn so với mức dự kiến.');
        }

        DB::transaction(function () use ($request, $amount, $note, $classOutcome, $actorId) {
            $final = $request->isFinalDecision();

            $this->saveDecision($request, [
                'actual_refund_amount' => $amount,
                'rejection_reason'     => null,
                'decision_note'        => $note,
                'class_outcome'        => $classOutcome,
            ], $actorId);

            $text = 'duyệt hoàn ' . self::money($amount) . ($note ? " (lý do: {$note})" : '');
            $this->log($request, ($final ? 'Quyết định cuối: ' : 'Quyết định: ') . $text, $actorId);

            if (! $final) {
                $this->toStatus($request, 'decided');
                $this->notifyRequester($request, "Yêu cầu hoàn phí #{$request->refund_request_id}: Trung tâm {$text}. Vui lòng xác nhận hoặc yêu cầu xem xét lại (chỉ 1 lần).");

                return;
            }

            $this->notifyRequester($request, "Yêu cầu hoàn phí #{$request->refund_request_id} — quyết định cuối cùng: Trung tâm {$text}.");
            $amount > 0 ? $this->startRefund($request, $actorId) : $this->complete($request, 'completed', $actorId);
        });
    }

    /** Từ chối hoàn phí (lần đầu: người gửi còn được xem xét lại; lần cuối: kết thúc) */
    public function reject(RefundRequest $request, string $reason, string $classOutcome, int $actorId): void
    {
        $this->ensureAwaitingDecision($request);

        DB::transaction(function () use ($request, $reason, $classOutcome, $actorId) {
            $final = $request->isFinalDecision();

            $this->saveDecision($request, [
                'actual_refund_amount' => 0,
                'rejection_reason'     => $reason,
                'decision_note'        => null,
                'class_outcome'        => $classOutcome,
            ], $actorId);

            $this->log($request, ($final ? 'Quyết định cuối: từ chối – ' : 'Quyết định: từ chối – ') . $reason, $actorId);

            if (! $final) {
                $this->toStatus($request, 'decided');
                $this->notifyRequester($request, "Yêu cầu hoàn phí #{$request->refund_request_id} bị từ chối: {$reason}. Bạn có thể xác nhận hoặc yêu cầu xem xét lại (chỉ 1 lần).");

                return;
            }

            $this->notifyRequester($request, "Yêu cầu hoàn phí #{$request->refund_request_id} — quyết định cuối cùng: từ chối hoàn phí. Lý do: {$reason}.");
            $this->complete($request, 'rejected_final', $actorId);
        });
    }

    /** Người gửi đồng ý với kết quả lần đầu (luồng chính bước 8–9) */
    public function confirm(RefundRequest $request, int $actorId): void
    {
        if ($request->status !== 'decided') {
            throw new DomainException('Yêu cầu không ở trạng thái chờ xác nhận kết quả.');
        }

        DB::transaction(function () use ($request, $actorId) {
            $this->log($request, 'Người gửi xác nhận kết quả hoàn phí', $actorId);

            (float) $request->actual_refund_amount > 0
                ? $this->startRefund($request, $actorId)
                : $this->complete($request, 'completed', $actorId); // từ chối hoặc 0 đ: hoàn tất, không tạo giao dịch (FR-HP-12)
        });
    }

    /** Người gửi yêu cầu xem xét lại — mỗi yêu cầu chỉ 1 lần (FR-HP-09) */
    public function requestReconsideration(RefundRequest $request, string $content, ?string $evidenceFile, int $actorId): void
    {
        if ($request->status !== 'decided') {
            throw new DomainException('Chỉ yêu cầu xem xét lại khi Trung tâm đã có kết quả và bạn chưa xác nhận.');
        }
        if ($request->reconsidered) {
            throw new DomainException('Yêu cầu này đã được xem xét lại một lần, không thể gửi thêm.');
        }

        DB::transaction(function () use ($request, $content, $evidenceFile, $actorId) {
            $request->forceFill([
                'reconsidered'     => true,
                'response_content' => $content,
                'evidence_file'    => $evidenceFile ?? $request->evidence_file,
            ])->save();

            $this->toStatus($request, 'reconsidering');
            $this->log($request, 'Yêu cầu xem xét lại', $actorId);
            $this->notifyMany($this->managers(), "Yêu cầu hoàn phí #{$request->refund_request_id} cần xem xét lại và ra quyết định cuối cùng.");
        });
    }

    /** Thực hiện lại hoàn tiền sau khi thất bại (luồng phụ 5) */
    public function retryRefund(RefundRequest $request, int $actorId): void
    {
        if ($request->status !== 'refund_failed') {
            throw new DomainException('Chỉ thực hiện lại khi giao dịch hoàn tiền trước đó thất bại.');
        }

        DB::transaction(fn () => $this->startRefund($request, $actorId));
    }

    /** Quá 15 ngày chưa quyết định → "Quá hạn", báo Giám đốc (FR-HP-06). Trả về số yêu cầu chuyển trạng thái. */
    public function markOverdue(): int
    {
        $expired = RefundRequest::with('payment')
            ->where('status', 'pending')
            ->where('received_at', '<=', now()->subDays(RefundRequest::PROCESSING_DAYS))
            ->get();

        foreach ($expired as $request) {
            DB::transaction(function () use ($request) {
                $this->toStatus($request, 'overdue');
                $this->log($request, 'Hệ thống: quá hạn xử lý ' . RefundRequest::PROCESSING_DAYS . ' ngày', null);

                $text = "Yêu cầu hoàn phí #{$request->refund_request_id} đã quá hạn xử lý "
                    . RefundRequest::PROCESSING_DAYS . ' ngày (tiếp nhận ' . $request->received_at->format('d/m/Y') . ').';
                $this->notifyMany($this->accountsWithRole('Giám đốc'), $text);
                $this->notifyMany($this->managers(), $text . ' Vui lòng xử lý ngay.');
            });
        }

        return $expired->count();
    }

    /** Hỏi lại cổng thanh toán cho các giao dịch "chưa có kết quả" — không tạo giao dịch mới */
    public function syncPendingRefunds(): int
    {
        $pending = RefundTransaction::with('request.payment')
            ->where('status', 'pending')
            ->whereHas('request', fn ($q) => $q->where('status', 'refunding'))
            ->get();

        foreach ($pending as $tx) {
            DB::transaction(fn () => $this->applyGatewayResult($tx->request, $tx, $this->gateway->refund($tx), null));
        }

        return $pending->count();
    }

    public function history(RefundRequest $request): Collection
    {
        return ActivityLog::with('account')
            ->where('affected_object', RefundRequest::objectRef($request))
            ->orderByDesc('occurred_at')
            ->orderByDesc('activity_log_id')
            ->get();
    }

    public static function money(float|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . ' đ';
    }

    // ------------------------------------------------------------------

    /** Gửi yêu cầu hoàn tiền đến cổng (bước 10–12). Không tạo giao dịch mới khi giao dịch trước chưa rõ kết quả. */
    private function startRefund(RefundRequest $request, ?int $actorId): void
    {
        if ($request->transactions()->where('status', 'pending')->exists()) {
            throw new DomainException('Giao dịch hoàn tiền trước đó chưa có kết quả, chưa thể tạo giao dịch mới.');
        }

        $tx = $request->transactions()->create([
            'refund_amount' => $request->actual_refund_amount,
            'status'        => 'pending',
        ]);

        $this->toStatus($request, 'refunding');
        $this->log($request, 'Gửi yêu cầu hoàn tiền ' . self::money($tx->refund_amount) . ' đến cổng thanh toán', $actorId);

        $this->applyGatewayResult($request, $tx, $this->gateway->refund($tx), $actorId);
    }

    private function applyGatewayResult(RefundRequest $request, RefundTransaction $tx, string $result, ?int $actorId): void
    {
        if ($result === RefundGateway::SUCCESS) {
            $tx->update(['status' => 'success', 'refunded_at' => now()]);
            $this->log($request, 'Hoàn tiền thành công ' . self::money($tx->refund_amount), $actorId);
            $this->notifyRequester($request, "Yêu cầu hoàn phí #{$request->refund_request_id}: đã hoàn " . self::money($tx->refund_amount) . ' về phương thức thanh toán ban đầu.');
            $this->complete($request, 'refunded', $actorId);
        } elseif ($result === RefundGateway::FAILED) {
            $tx->update(['status' => 'failed']);
            $this->toStatus($request, 'refund_failed');
            $this->log($request, 'Hoàn tiền thất bại', $actorId);
            $this->notifyMany($this->managers(), "Hoàn tiền cho yêu cầu #{$request->refund_request_id} thất bại. Vui lòng kiểm tra và thực hiện lại.");
        }
        // PENDING: giữ "Đang hoàn tiền", syncPendingRefunds() sẽ hỏi lại cổng.
    }

    /** Kết thúc yêu cầu và cập nhật trạng thái lớp theo lựa chọn của Quản lý (FR-HP-13) */
    private function complete(RefundRequest $request, string $status, ?int $actorId): void
    {
        $this->toStatus($request, $status);

        $outcome = RefundRequest::CLASS_OUTCOMES[$request->class_outcome] ?? null;
        $class = $request->payment->tutorClass;

        if ($outcome && $class) {
            $class->update(['status' => $outcome[1]]);
            $this->log($request, 'Cập nhật lớp #' . $class->class_id . ': ' . $outcome[0], $actorId);
        }
    }

    private function saveDecision(RefundRequest $request, array $data, int $actorId): void
    {
        $request->forceFill($data + ['decided_at' => now(), 'decided_by' => $actorId])->save();
    }

    private function ensureAwaitingDecision(RefundRequest $request): void
    {
        if (! $request->awaitingDecision()) {
            throw new DomainException('Yêu cầu đang ở trạng thái "' . $request->statusLabel() . '", không thể ra quyết định.');
        }
    }

    private function toStatus(RefundRequest $request, string $status): void
    {
        $request->forceFill(['status' => $status])->save();
    }

    /** Người gửi + Gia sư của giao dịch (nếu khác nhau) */
    private function notifyRequester(RefundRequest $request, string $content): void
    {
        $ids = collect([$request->requested_by, $request->payment->account_id])->filter()->unique();
        $this->notifyMany(Account::whereIn('account_id', $ids)->get(), $content);
    }

    private function notifyMany(iterable $accounts, string $content): void
    {
        foreach ($accounts as $account) {
            Notification::create([
                'content'          => $content,
                'delivery_channel' => 'system',
                'account_id'       => $account->account_id,
            ]);
        }
    }

    private function managers(): Collection
    {
        return $this->accountsWithRole('Quản lý');
    }

    private function accountsWithRole(string $role): Collection
    {
        return Account::query()
            ->whereHas('role', fn ($q) => $q->where('role_name', $role))
            ->where('status', 'active')
            ->get();
    }

    /** activity_logs.account_id bắt buộc: thao tác tự động ghi theo Gia sư của giao dịch */
    private function log(RefundRequest $request, string $action, ?int $actorId): void
    {
        ActivityLog::create([
            'action'          => mb_substr($action, 0, 100),
            'occurred_at'     => now(),
            'affected_object' => RefundRequest::objectRef($request),
            'account_id'      => $actorId ?? $request->payment->account_id,
        ]);
    }
}
