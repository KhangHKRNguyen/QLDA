<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesRefundRequests;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Person;
use App\Models\RefundRequest;
use App\Models\TutorClass;
use App\Services\RefundService;
use App\Services\TeachingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * NV Quản lý khách hàng — theo dõi lớp đã có gia sư (PTTKHT 2.1.8):
 * ghi nhận kết quả dạy thử (HĐ4), đánh dấu hoàn thành (HĐ6), tạo phiếu hoàn phí theo phản ánh của phụ huynh (HĐ8 TH2),
 * xác nhận kết quả / yêu cầu xem xét lại cho các phiếu do Trung tâm tạo.
 */
class TeachingController extends Controller
{
    use HandlesRefundRequests;

    public function __construct(private TeachingService $teaching) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $filter = (string) $request->query('loc');

        $classes = TutorClass::query()
            ->select('tutor_classes.*', 'tp.full_name as tutor_name', 'cp.full_name as customer_name', 'cp.phone_number as customer_phone')
            ->leftJoin('accounts', 'accounts.account_id', '=', 'tutor_classes.account_id')
            ->leftJoin('people as tp', 'tp.person_id', '=', 'accounts.person_id')
            ->leftJoin('people as cp', 'cp.person_id', '=', 'tutor_classes.person_id')
            ->whereIn('tutor_classes.status', TeachingService::ASSIGNED_STATUSES)
            ->when($filter === 'day-thu', fn ($w) => $w->where('tutor_classes.status', 'teaching')->where('tutor_classes.teaching_stage', 'trial'))
            ->when($filter === 'chinh-thuc', fn ($w) => $w->where('tutor_classes.status', 'teaching')->where('tutor_classes.teaching_stage', 'official'))
            ->when($filter === 'hoan-phi', fn ($w) => $w->where('tutor_classes.status', 'refund_processing'))
            ->when($filter === 'hoan-thanh', fn ($w) => $w->where('tutor_classes.status', 'completed'))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s
                ->where('tutor_classes.subject', 'like', "%{$q}%")
                ->orWhere('tp.full_name', 'like', "%{$q}%")
                ->orWhere('cp.full_name', 'like', "%{$q}%")
                ->orWhere('cp.phone_number', 'like', "%{$q}%")
                ->when(ctype_digit(ltrim($q, '#')), fn ($s) => $s->orWhere('tutor_classes.class_id', (int) ltrim($q, '#')))))
            ->orderByRaw("CASE WHEN tutor_classes.status = 'teaching' AND tutor_classes.teaching_stage = 'trial' THEN 0
                               WHEN tutor_classes.status = 'refund_processing' THEN 1 WHEN tutor_classes.status = 'teaching' THEN 2 ELSE 3 END")
            ->orderByDesc('tutor_classes.accepted_at')
            ->paginate(15)
            ->withQueryString();

        $base = TutorClass::whereIn('status', TeachingService::ASSIGNED_STATUSES);

        return view('admin.teaching.index', [
            'classes' => $classes,
            'filter'  => $filter,
            'stats'   => [
                'trial'    => (clone $base)->where('status', 'teaching')->where('teaching_stage', 'trial')->count(),
                'official' => (clone $base)->where('status', 'teaching')->where('teaching_stage', 'official')->count(),
                'refund'   => (clone $base)->where('status', 'refund_processing')->count(),
                'awaiting' => RefundRequest::where('source_type', 'staff')->where('status', 'decided')->count(),
            ],
        ]);
    }

    public function show(int $id): View
    {
        $class = $this->findClass($id);

        return view('admin.teaching.show', self::classSummary($class) + [
            'class'    => $class,
            'tutor'    => Account::with('person')->find($class->account_id),
            'customer' => Person::find($class->person_id),
            'refunds'  => RefundRequest::whereHas('payment', fn ($q) => $q->where('class_id', $class->class_id))
                ->latest('refund_request_id')->get(),
            'logs'     => ActivityLog::with('account')->where('affected_object', TeachingService::objectRef($class))
                ->orderByDesc('occurred_at')->get(),
        ]);
    }

    /** HĐ4: phụ huynh đồng ý dạy chính thức (agreed=1) hoặc báo nghỉ (agreed=0 → sang tạo phiếu hoàn phí) */
    public function trial(Request $request, int $id): RedirectResponse
    {
        $class = $this->findClass($id);
        $agreed = $request->boolean('agreed');

        try {
            $this->teaching->recordTrial($class, $agreed, $this->actorId());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $agreed
            ? back()->with('success', 'Đã ghi nhận: phụ huynh đồng ý học chính thức.')
            : redirect()->route('admin.teaching.refund.create', [$id, 'ly-do' => 'trial_quit'])
                ->with('info', 'Đã ghi nhận phụ huynh báo nghỉ. Vui lòng tạo phiếu yêu cầu hoàn phí.');
    }

    public function complete(int $id): RedirectResponse
    {
        try {
            $this->teaching->complete($this->findClass($id), $this->actorId());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận lớp Hoàn thành.');
    }

    public function createRefund(Request $request, int $id): View|RedirectResponse
    {
        $class = $this->findClass($id);

        if ($blocker = TeachingService::refundBlocker($class)) {
            return redirect()->route('admin.teaching.show', $id)->with('error', $blocker);
        }

        return view('admin.teaching.refund-create', [
            'class'   => $class,
            'payment' => TeachingService::paidTransaction($class),
            'tutor'   => Account::with('person')->find($class->account_id),
            'preset'  => $request->query('ly-do'),
        ]);
    }

    public function storeRefund(Request $request, int $id): RedirectResponse
    {
        return $this->submitRefund($request, $this->findClass($id), 'staff', 'admin.teaching.refunds.show');
    }

    /** Phiếu hoàn phí do Trung tâm (NV QLKH) tạo */
    public function refunds(Request $request): View
    {
        $refunds = RefundRequest::with(['payment.tutorClass', 'payment.account.person', 'requester.person'])
            ->where('source_type', 'staff')
            ->when($request->query('loc') === 'cho-xac-nhan', fn ($q) => $q->where('status', 'decided'))
            ->orderByRaw("CASE WHEN status = 'decided' THEN 0 ELSE 1 END")
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teaching.refunds', ['refunds' => $refunds]);
    }

    public function showRefund(int $id): View
    {
        $refund = $this->findRefund($id);

        return view('admin.teaching.refund-show', [
            'refund'     => $refund,
            'class'      => self::classOf($refund),
            'canRespond' => $this->canRespond($refund, asStaff: true),
            'logs'       => app(RefundService::class)->history($refund),
        ]);
    }

    public function confirm(int $id): RedirectResponse
    {
        $refund = $this->findRefund($id);
        abort_unless($this->canRespond($refund, asStaff: true), 403);

        return $this->confirmRefund($refund);
    }

    public function reconsider(Request $request, int $id): RedirectResponse
    {
        $refund = $this->findRefund($id);
        abort_unless($this->canRespond($refund, asStaff: true), 403);

        return $this->reconsiderRefund($request, $refund);
    }

    public function evidence(int $id): StreamedResponse
    {
        return $this->downloadEvidence($this->findRefund($id));
    }

    private function findClass(int $id): TutorClass
    {
        return TutorClass::whereIn('status', TeachingService::ASSIGNED_STATUSES)->findOrFail($id);
    }

    private function findRefund(int $id): RefundRequest
    {
        return RefundRequest::with(['payment.tutorClass', 'payment.account.person', 'transactions', 'requester.person'])->findOrFail($id);
    }
}
