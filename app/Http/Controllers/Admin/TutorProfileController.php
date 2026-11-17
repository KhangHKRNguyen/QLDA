<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Notification;
use App\Models\VerificationProfile;
use App\Services\AccountService;
use App\Services\TutorRegistrationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nhân viên Quản lý Gia sư (quy trình 2.1.5): xem hồ sơ chờ duyệt, duyệt / yêu cầu bổ sung / từ chối.
 */
class TutorProfileController extends Controller
{
    /** Tab lọc trạng thái: khóa => nhãn */
    public const TABS = [
        'pending_verification' => 'Chờ duyệt',
        'supplement_required'  => 'Chờ bổ sung',
        'active'               => 'Hoạt động',
        'locked'               => 'Tạm khóa',
        'suspended'            => 'Đình chỉ',
        'rejected'             => 'Từ chối',
        'cancelled'            => 'Đã hủy',
        'inactive'             => 'Ngừng hoạt động',
    ];

    public function __construct(private TutorRegistrationService $service, private AccountService $accounts) {}

    /** Tổng quan của Nhân viên Quản lý Gia sư */
    public function dashboard(): View
    {
        $counts = Account::join('roles', 'roles.role_id', '=', 'accounts.role_id')
            ->where('roles.role_name', TutorRegistrationService::TUTOR_ROLE)
            ->selectRaw('accounts.status as status, count(*) as total')
            ->groupBy('accounts.status')
            ->pluck('total', 'status');

        // Hồ sơ chờ duyệt lâu nhất (nộp trước xử lý trước)
        $pending = Account::withProfile()
            ->where('roles.role_name', TutorRegistrationService::TUTOR_ROLE)
            ->where('accounts.status', 'pending_verification')
            ->orderBy('accounts.account_id')
            ->limit(5)->get();

        // Gia sư sắp hết hạn bổ sung hồ sơ (hạn gần nhất trước)
        $expiring = VerificationProfile::query()
            ->join('accounts', 'accounts.person_id', '=', 'verification_profiles.person_id')
            ->join('people', 'people.person_id', '=', 'verification_profiles.person_id')
            ->where('verification_profiles.review_status', 'needs_supplement')
            ->where('accounts.status', 'supplement_required')
            ->groupBy('accounts.account_id', 'people.full_name')
            ->select('accounts.account_id', 'people.full_name', DB::raw('min(verification_profiles.supplement_deadline) as deadline'))
            ->orderBy('deadline')
            ->limit(5)->get();

        return view('admin.tutor_profiles.dashboard', [
            'counts'        => $counts,
            'pending'       => $pending,
            'expiring'      => $expiring,
            'name'          => Account::withProfile()->where('accounts.account_id', Auth::id())->value('people.full_name'),
            'notifications' => Notification::where('account_id', Auth::id())->orderByDesc('notification_id')->limit(3)->get(),
            'unread'        => Notification::where('account_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }

    /** Trang Thông báo riêng của QLGS (ví dụ: có hồ sơ gia sư mới chờ xác minh) */
    public function notifications(): View
    {
        return view('admin.tutor_profiles.notifications', [
            'items'  => Notification::where('account_id', Auth::id())->orderByDesc('notification_id')->paginate(15),
            'unread' => Notification::where('account_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }

    public function readAllNotifications(): RedirectResponse
    {
        Notification::where('account_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'pending_verification');
        $status = ($status === 'all' || isset(self::TABS[$status])) ? $status : 'pending_verification';

        $accounts = Account::withProfile()
            ->where('roles.role_name', TutorRegistrationService::TUTOR_ROLE)
            ->when($status !== 'all', fn ($query) => $query->where('accounts.status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('people.full_name', 'like', "%{$q}%")
                ->orWhere('people.email', 'like', "%{$q}%")
                ->orWhere('people.phone_number', 'like', "%{$q}%")))
            ->orderBy('accounts.account_id') // hồ sơ nộp trước xử lý trước
            ->paginate(15)
            ->withQueryString();

        $counts = Account::join('roles', 'roles.role_id', '=', 'accounts.role_id')
            ->where('roles.role_name', TutorRegistrationService::TUTOR_ROLE)
            ->selectRaw('accounts.status as status, count(*) as total')
            ->groupBy('accounts.status')
            ->pluck('total', 'status');

        return view('admin.tutor_profiles.index', [
            'accounts' => $accounts,
            'tabs'     => self::TABS,
            'status'   => $status,
            'counts'   => $counts,
        ]);
    }

    public function show(int $id): View
    {
        $account = $this->find($id);

        return view('admin.tutor_profiles.show', [
            'account' => $account,
            'docs'    => VerificationProfile::where('person_id', $account->person_id)->orderBy('verification_profile_id')->get(),
            'logs'    => $this->accounts->history($account),
        ]);
    }

    /** Xem tệp giấy tờ (lưu private, chỉ mở qua route này; route đã giới hạn cho Nhân viên Quản lý Gia sư). */
    public function document(int $documentId): StreamedResponse
    {
        $doc = VerificationProfile::findOrFail($documentId);
        $disk = Storage::disk(TutorRegistrationService::DISK);
        abort_unless($doc->attachment_file && $disk->exists($doc->attachment_file), 404);

        return $disk->response($doc->attachment_file);
    }

    public function approve(int $id): RedirectResponse
    {
        return $this->run(fn () => $this->service->approve($this->find($id), $this->actorId()),
            'Đã duyệt hồ sơ và kích hoạt tài khoản gia sư.');
    }

    public function requestSupplement(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'documents'   => ['required', 'array', 'min:1'],
            'documents.*' => ['integer'],
            'reason'      => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'documents.required' => 'Hãy chọn ít nhất một giấy tờ cần bổ sung.',
            'documents.min'      => 'Hãy chọn ít nhất một giấy tờ cần bổ sung.',
        ], ['reason' => 'lý do']);

        return $this->run(
            fn () => $this->service->requestSupplement($this->find($id), $data['documents'], $data['reason'], $this->actorId()),
            'Đã gửi yêu cầu bổ sung hồ sơ (hạn ' . now()->addDays(TutorRegistrationService::SUPPLEMENT_DAYS)->format('d/m/Y') . ').',
        );
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'lý do']);

        return $this->run(fn () => $this->service->reject($this->find($id), $data['reason'], $this->actorId()),
            'Đã từ chối hồ sơ.');
    }

    /** HĐ8 TH1: vi phạm nhẹ → tạm khóa có thời hạn 7–30 ngày (hết hạn tự mở lại: AccountService::unlockExpired) */
    public function lock(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'days'   => ['required', 'integer', 'between:' . AccountService::MIN_LOCK_DAYS . ',' . AccountService::MAX_LOCK_DAYS],
        ], [], ['reason' => 'lý do', 'days' => 'thời hạn khóa']);

        $days = (int) $data['days'];

        return $this->run(
            fn () => $this->accounts->lock($this->find($id), $data['reason'], $days, $this->actorId()),
            'Đã tạm khóa tài khoản gia sư đến ' . now()->addDays($days)->format('d/m/Y') . '.',
        );
    }

    /** HĐ8 TH2: vi phạm nghiêm trọng → đình chỉ vĩnh viễn */
    public function suspend(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'lý do']);

        return $this->run(fn () => $this->accounts->suspend($this->find($id), $data['reason'], $this->actorId()),
            'Đã đình chỉ vĩnh viễn tài khoản gia sư.');
    }

    public function unlock(int $id): RedirectResponse
    {
        return $this->run(fn () => $this->accounts->unlock($this->find($id), $this->actorId()),
            'Đã mở khóa tài khoản gia sư.');
    }

    private function find(int $id): Account
    {
        return Account::withProfile()
            ->where('accounts.account_id', $id)
            ->where('roles.role_name', TutorRegistrationService::TUTOR_ROLE)
            ->firstOrFail();
    }

    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    /** Nhân viên đang đăng nhập (ghi vào lịch sử xử lý) */
    private function actorId(): ?int
    {
        return Auth::id();
    }
}
