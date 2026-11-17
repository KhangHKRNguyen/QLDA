<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\HandlesRefundRequests;
use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\RefundRequest;
use App\Models\TutorClass;
use App\Services\RefundService;
use App\Services\TeachingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cổng Gia sư — "Lớp của tôi" và "Yêu cầu hoàn phí" (PTTKHT 2.1.8; SRS FR-HP-01 → 04, 08, 09).
 * Gia sư chỉ thấy lớp và yêu cầu hoàn phí của chính mình.
 */
class TeachingController extends Controller
{
    use HandlesRefundRequests;

    public function classes(Request $request): View
    {
        $filter = (string) $request->query('loc');

        $classes = TutorClass::where('account_id', $this->actorId())
            ->whereIn('status', TeachingService::ASSIGNED_STATUSES)
            ->when($filter === 'dang-day', fn ($q) => $q->whereIn('status', ['teaching', 'refund_processing']))
            ->when($filter === 'hoan-thanh', fn ($q) => $q->where('status', 'completed'))
            ->orderByRaw("CASE status WHEN 'teaching' THEN 0 WHEN 'refund_processing' THEN 1 ELSE 2 END")
            ->orderByDesc('accepted_at')
            ->get();

        return view('tutor.classes.index', ['classes' => $classes, 'filter' => $filter]);
    }

    public function showClass(int $id): View
    {
        $class = $this->ownClass($id);
        $summary = self::classSummary($class);

        return view('tutor.classes.show', $summary + [
            'class'    => $class,
            // BR-03: chỉ mở thông tin liên hệ khi đã thanh toán phí nhận lớp thành công
            'customer' => $summary['payment'] ? Person::find($class->person_id) : null,
            'refunds'  => RefundRequest::whereHas('payment', fn ($q) => $q->where('class_id', $class->class_id))
                ->latest('refund_request_id')->get(),
        ]);
    }

    public function createRefund(int $id): View|RedirectResponse
    {
        $class = $this->ownClass($id);

        if ($blocker = TeachingService::refundBlocker($class)) {
            return redirect()->route('tutor.classes.show', $id)->with('error', $blocker);
        }

        return view('tutor.refunds.create', ['class' => $class, 'payment' => TeachingService::paidTransaction($class)]);
    }

    public function storeRefund(Request $request, int $id): RedirectResponse
    {
        return $this->submitRefund($request, $this->ownClass($id), 'tutor', 'tutor.refunds.show');
    }

    public function refunds(): View
    {
        $refunds = RefundRequest::with('payment.tutorClass')
            ->whereHas('payment', fn ($q) => $q->where('account_id', $this->actorId()))
            ->latest('received_at')
            ->get();

        return view('tutor.refunds.index', ['refunds' => $refunds]);
    }

    public function showRefund(int $id): View
    {
        $refund = $this->ownRefund($id);

        return view('tutor.refunds.show', [
            'refund'     => $refund,
            'class'      => self::classOf($refund),
            'canRespond' => $this->canRespond($refund),
            'logs'       => app(RefundService::class)->history($refund),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $refund = $this->ownRefund($id);
        abort_unless($this->canRespond($refund), 403);

        return $this->confirmRefund($refund);
    }

    public function reconsider(Request $request, int $id): RedirectResponse
    {
        $refund = $this->ownRefund($id);
        abort_unless($this->canRespond($refund), 403);

        return $this->reconsiderRefund($request, $refund);
    }

    public function evidence(int $id): StreamedResponse
    {
        return $this->downloadEvidence($this->ownRefund($id));
    }

    private function ownClass(int $id): TutorClass
    {
        return TutorClass::where('account_id', $this->actorId())
            ->whereIn('status', TeachingService::ASSIGNED_STATUSES)
            ->findOrFail($id);
    }

    private function ownRefund(int $id): RefundRequest
    {
        return RefundRequest::with(['payment.tutorClass', 'transactions', 'requester.person'])
            ->whereHas('payment', fn ($q) => $q->where('account_id', $this->actorId()))
            ->findOrFail($id);
    }
}
