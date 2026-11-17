<?php

namespace App\Http\Controllers\Concerns;

use App\Models\RefundRequest;
use App\Models\TutorClass;
use App\Services\RefundService;
use App\Services\TeachingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phần dùng chung cho người gửi yêu cầu hoàn phí (Gia sư, NV Quản lý khách hàng):
 * gửi yêu cầu, xác nhận kết quả, yêu cầu xem xét lại, tải bằng chứng.
 */
trait HandlesRefundRequests
{
    /** Bằng chứng lưu ở disk riêng tư, chỉ xem qua route có kiểm tra quyền */
    protected const EVIDENCE_DISK = 'local';

    protected const EVIDENCE_RULES = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];

    protected function submitRefund(Request $request, TutorClass $class, string $source, string $successRoute): RedirectResponse
    {
        $allowed = collect(RefundRequest::REASONS)
            ->filter(fn ($r) => $source === 'staff' || $r[2])
            ->keys()->all();

        $data = $request->validate([
            'reason_code' => ['required', Rule::in($allowed)],
            'reason'      => ['required', 'string', 'min:10', 'max:2000'],
            'evidence'    => self::EVIDENCE_RULES,
            'confirm'     => ['accepted'],
        ], $this->refundMessages(), [
            'reason_code' => 'lý do hoàn phí', 'reason' => 'nội dung phản ánh', 'evidence' => 'bằng chứng',
        ]);

        $path = $request->file('evidence')?->store('refund-evidence', self::EVIDENCE_DISK);

        try {
            $refund = app(RefundService::class)->submit($class, $data['reason_code'], $data['reason'], $path, $source, $this->actorId());
        } catch (DomainException $e) {
            $path && Storage::disk(self::EVIDENCE_DISK)->delete($path);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route($successRoute, $refund->refund_request_id)
            ->with('success', 'Gửi yêu cầu thành công. Trung tâm sẽ xử lý trong ' . RefundRequest::PROCESSING_DAYS . ' ngày.');
    }

    protected function confirmRefund(RefundRequest $refund): RedirectResponse
    {
        return $this->runRefund(fn () => app(RefundService::class)->confirm($refund, $this->actorId()),
            (float) $refund->actual_refund_amount > 0
                ? 'Đã xác nhận. Trung tâm đang hoàn tiền về phương thức thanh toán ban đầu.'
                : 'Đã xác nhận kết quả. Yêu cầu được ghi nhận hoàn tất.');
    }

    protected function reconsiderRefund(Request $request, RefundRequest $refund): RedirectResponse
    {
        $data = $request->validate([
            'response_content' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence'         => self::EVIDENCE_RULES,
        ], $this->refundMessages(), ['response_content' => 'nội dung phản hồi', 'evidence' => 'bằng chứng']);

        $path = $request->file('evidence')?->store('refund-evidence', self::EVIDENCE_DISK);

        return $this->runRefund(
            fn () => app(RefundService::class)->requestReconsideration($refund, $data['response_content'], $path, $this->actorId()),
            'Đã gửi yêu cầu xem xét lại. Quản lý Trung tâm sẽ ra quyết định cuối cùng.',
            $path,
        );
    }

    protected function downloadEvidence(RefundRequest $refund): StreamedResponse
    {
        abort_unless($refund->evidence_file && Storage::disk(self::EVIDENCE_DISK)->exists($refund->evidence_file), 404);

        return Storage::disk(self::EVIDENCE_DISK)->response($refund->evidence_file);
    }

    /** Người dùng hiện tại có phải người gửi và đang được quyền phản hồi kết quả không */
    protected function canRespond(RefundRequest $refund, bool $asStaff = false): bool
    {
        $mine = $asStaff ? $refund->source_type === 'staff' : $refund->requested_by === $this->actorId();

        return $mine && $refund->status === 'decided';
    }

    protected static function classOf(RefundRequest $refund): ?TutorClass
    {
        return $refund->payment?->tutorClass;
    }

    private function runRefund(callable $action, string $success, ?string $uploaded = null): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            $uploaded && Storage::disk(self::EVIDENCE_DISK)->delete($uploaded);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    private function refundMessages(): array
    {
        return [
            'required'        => ':Attribute không được để trống.',
            'in'              => ':Attribute không hợp lệ.',
            'min'             => ':Attribute phải có ít nhất :min ký tự.',
            'max'             => ':Attribute quá dài.',
            'evidence.max'    => 'Bằng chứng tối đa 5 MB.',
            'evidence.mimes'  => 'Bằng chứng phải là ảnh (JPG, PNG) hoặc PDF.',
            'confirm.accepted' => 'Vui lòng xác nhận thông tin trước khi gửi.',
        ];
    }

    protected function actorId(): int
    {
        return (int) Auth::id();
    }

    /** Thông tin hiển thị chung cho trang chi tiết lớp */
    protected static function classSummary(TutorClass $class): array
    {
        return [
            'statusLabel'  => TeachingService::statusLabel($class),
            'statusTone'   => TeachingService::statusTone($class),
            'stageLabel'   => TeachingService::stageLabel($class),
            'warrantyEnd'  => TeachingService::warrantyUntil($class),
            'warrantyLeft' => TeachingService::warrantyDaysLeft($class),
            'payment'      => TeachingService::paidTransaction($class),
            'blocker'      => TeachingService::refundBlocker($class),
        ];
    }
}
