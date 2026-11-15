<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\VerificationProfile;
use App\Services\AccountService;
use App\Services\TutorRegistrationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        'active'               => 'Đã duyệt',
        'rejected'             => 'Từ chối',
        'cancelled'            => 'Đã hủy',
    ];

    public function __construct(private TutorRegistrationService $service, private AccountService $accounts) {}

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

    /** Xem tệp giấy tờ (lưu private, chỉ mở qua route này). TODO: gắn middleware kiểm tra quyền khi có Auth. */
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

    /** TODO (sau khi Khang làm xong Auth): trả về account_id của nhân viên đang đăng nhập */
    private function actorId(): ?int
    {
        return null;
    }
}
