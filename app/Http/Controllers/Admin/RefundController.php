<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\RefundRequest;
use App\Services\RefundService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Trung tâm xử lý hoàn phí — vai trò Quản lý Trung tâm (PTTKHT 2.2.9.2, SRS FR-HP-05 → FR-HP-13).
 * Logic nghiệp vụ nằm ở App\Services\RefundService.
 */
class RefundController extends Controller
{
    public function __construct(private RefundService $service) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $code = preg_match('/^(#|hp)?0*(\d+)$/i', $q, $m) ? (int) $m[2] : null;

        $refunds = RefundRequest::query()
            ->select('refund_requests.*', 'people.full_name as tutor_name', 'accounts.username as tutor_username',
                'tutor_classes.class_id', 'tutor_classes.subject', 'tutor_classes.grade_level')
            ->join('payment_transactions', 'payment_transactions.payment_transaction_id', '=', 'refund_requests.payment_transaction_id')
            ->join('tutor_classes', 'tutor_classes.class_id', '=', 'payment_transactions.class_id')
            ->join('accounts', 'accounts.account_id', '=', 'payment_transactions.account_id')
            ->join('people', 'people.person_id', '=', 'accounts.person_id')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->when($code, fn ($w) => $w->orWhere('refund_requests.refund_request_id', $code))
                ->orWhere('people.full_name', 'like', "%{$q}%")
                ->orWhere('accounts.username', 'like', "%{$q}%")
                ->orWhere('tutor_classes.subject', 'like', "%{$q}%")))
            ->when($status === 'can-xu-ly', fn ($query) => $query->whereIn('refund_requests.status', RefundRequest::AWAITING_DECISION))
            ->when(isset(RefundRequest::STATUSES[$status]), fn ($query) => $query->where('refund_requests.status', $status))
            // Việc cần làm lên trước: quá hạn → xem xét lại → chờ xác minh (cũ nhất trước) → còn lại mới nhất trước
            ->orderByRaw("CASE refund_requests.status WHEN 'overdue' THEN 0 WHEN 'reconsidering' THEN 1 WHEN 'pending' THEN 2 WHEN 'refund_failed' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE WHEN refund_requests.status IN ('overdue','reconsidering','pending') THEN refund_requests.received_at END ASC")
            ->orderByDesc('refund_requests.received_at')
            ->paginate(15)
            ->withQueryString();

        $counts = RefundRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.refunds.index', [
            'refunds'  => $refunds,
            'statuses' => RefundRequest::STATUSES,
            'stats'    => [
                'awaiting'      => collect(RefundRequest::AWAITING_DECISION)->sum(fn ($s) => $counts[$s] ?? 0),
                'overdue'       => $counts['overdue'] ?? 0,
                'decided'       => $counts['decided'] ?? 0,
                'refund_failed' => $counts['refund_failed'] ?? 0,
                'refunded'      => RefundRequest::where('status', 'refunded')->sum('actual_refund_amount'),
            ],
        ]);
    }

    public function show(int $id): View
    {
        $refund = $this->find($id);
        $class = $refund->payment->tutorClass;

        return view('admin.refunds.show', [
            'refund'   => $refund,
            'class'    => $class,
            'tutor'    => $refund->payment->account,
            'customer' => $class ? Person::find($class->person_id) : null,
            'logs'     => $this->service->history($refund),
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $refund = $this->find($id);
        $data = $request->validate([
            'mode'          => ['required', Rule::in(['expected', 'adjust'])],
            'amount'        => ['exclude_unless:mode,adjust', 'required', 'numeric', 'min:0'],
            'note'          => ['exclude_unless:mode,adjust', 'required', 'string', 'min:5', 'max:1000'],
            'class_outcome' => ['required', Rule::in(array_keys(RefundRequest::CLASS_OUTCOMES))],
        ], $this->messages(), $this->attributes());

        $adjust = $data['mode'] === 'adjust';

        return $this->run(fn () => $this->service->approve(
            $refund,
            $adjust ? (float) $data['amount'] : null,
            $adjust ? $data['note'] : null,
            $data['class_outcome'],
            $this->actorId(),
        ), $refund->isFinalDecision() ? 'Đã lưu quyết định cuối cùng.' : 'Đã duyệt và gửi kết quả cho người gửi yêu cầu.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $refund = $this->find($id);
        $data = $request->validate([
            'reason'        => ['required', 'string', 'min:5', 'max:1000'],
            'class_outcome' => ['required', Rule::in(array_keys(RefundRequest::CLASS_OUTCOMES))],
        ], $this->messages(), $this->attributes());

        return $this->run(fn () => $this->service->reject($refund, $data['reason'], $data['class_outcome'], $this->actorId()),
            $refund->isFinalDecision() ? 'Đã từ chối hoàn phí (quyết định cuối cùng).' : 'Đã từ chối và gửi kết quả cho người gửi yêu cầu.');
    }

    public function retry(int $id): RedirectResponse
    {
        return $this->run(fn () => $this->service->retryRefund($this->find($id), $this->actorId()),
            'Đã gửi lại yêu cầu hoàn tiền đến cổng thanh toán.');
    }

    private function find(int $id): RefundRequest
    {
        return RefundRequest::with(['payment.tutorClass', 'payment.account.person', 'requester.person', 'decider.person', 'transactions'])
            ->findOrFail($id);
    }

    /** Chạy thao tác; lỗi nghiệp vụ thành toast lỗi. Thông báo thực tế lấy theo trạng thái sau khi xử lý. */
    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    private function messages(): array
    {
        return [
            'required' => ':Attribute không được để trống.',
            'numeric'  => ':Attribute phải là số.',
            'in'       => ':Attribute không hợp lệ.',
            'min'      => ':Attribute phải có ít nhất :min ký tự.',
            'max'      => ':Attribute tối đa :max ký tự.',
            'amount.min' => 'Mức hoàn không được âm.',
        ];
    }

    private function attributes(): array
    {
        return ['amount' => 'mức hoàn chính thức', 'note' => 'lý do điều chỉnh', 'reason' => 'lý do từ chối', 'class_outcome' => 'cách xử lý lớp'];
    }

    private function actorId(): int
    {
        return (int) Auth::id();
    }
}
