<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RefundRequest;
use App\Services\RefundService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Giám đốc Trung tâm — "Danh sách yêu cầu quá hạn" (PTTKHT 2.2.9.2 luồng phụ 1, SRS FR-HP-06):
 * xem yêu cầu hoàn phí quá 15 ngày chưa có quyết định và yêu cầu Quản lý tiếp tục xử lý.
 */
class OverdueRefundController extends Controller
{
    public function index(): View
    {
        $refunds = RefundRequest::with(['payment.tutorClass', 'payment.account.person'])
            ->where('status', 'overdue')
            ->orderBy('received_at')
            ->get();

        // Lần nhắc gần nhất của Giám đốc cho từng yêu cầu
        $reminders = ActivityLog::where('action', 'like', 'Giám đốc yêu cầu%')
            ->whereIn('affected_object', $refunds->map(fn ($r) => RefundRequest::objectRef($r)))
            ->get()
            ->groupBy('affected_object')
            ->map(fn ($logs) => [$logs->count(), $logs->max('occurred_at')]);

        // Yêu cầu từng quá hạn nhưng đã được xử lý tiếp (để Giám đốc theo dõi kết quả)
        $resolved = RefundRequest::with(['payment.tutorClass', 'payment.account.person'])
            ->whereIn('refund_request_id', ActivityLog::where('action', 'like', 'Hệ thống: quá hạn%')
                ->where('affected_object', 'like', 'refund_requests#%')
                ->pluck('affected_object')
                ->map(fn ($o) => (int) str_replace('refund_requests#', '', $o)))
            ->where('status', '!=', 'overdue')
            ->latest('decided_at')
            ->limit(10)
            ->get();

        return view('admin.refunds.overdue', compact('refunds', 'reminders', 'resolved'));
    }

    public function remind(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            app(RefundService::class)->escalate(RefundRequest::with('payment')->findOrFail($id), $data['reason'] ?? null, (int) Auth::id());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi yêu cầu xử lý đến Quản lý Trung tâm.');
    }
}
