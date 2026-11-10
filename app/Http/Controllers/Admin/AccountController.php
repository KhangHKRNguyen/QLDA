<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use App\Services\AccountService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Quản lý tài khoản (Frm_QuanLyTaiKhoan): danh sách, xem xét, tạm khóa, đình chỉ, mở khóa, vô hiệu hóa.
 * Logic nghiệp vụ nằm ở App\Services\AccountService.
 */
class AccountController extends Controller
{
    public function __construct(private AccountService $service) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $accounts = Account::withProfile()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('people.full_name', 'like', "%{$q}%")
                ->orWhere('accounts.username', 'like', "%{$q}%")
                ->orWhere('people.email', 'like', "%{$q}%")
                ->orWhere('people.phone_number', 'like', "%{$q}%")))
            ->when($request->query('role'), fn ($query, $role) => $query->where('roles.role_name', $role))
            ->when($request->query('status'), fn ($query, $status) => $query->where('accounts.status', $status))
            ->orderByDesc('accounts.status_updated_at')
            ->orderByDesc('accounts.account_id')
            ->paginate(15)
            ->withQueryString();

        $counts = Account::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'statuses' => Account::STATUSES,
            'roles'    => Role::orderBy('role_id')->pluck('role_name'),
            'stats'    => [
                'all'                  => $counts->sum(),
                'active'               => $counts['active'] ?? 0,
                'locked'               => $counts['locked'] ?? 0,
                'suspended'            => $counts['suspended'] ?? 0,
                'pending_verification' => $counts['pending_verification'] ?? 0,
            ],
        ]);
    }

    public function show(int $id): View
    {
        $account = $this->find($id);

        return view('admin.accounts.show', [
            'account' => $account,
            'checks'  => $this->service->deactivationChecks($account),
            'logs'    => $this->service->history($account),
        ]);
    }

    public function review(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        return $this->run(fn () => $this->service->markReviewed($this->find($id), $data['reason'] ?? null, $this->actorId()),
            'Đã ghi nhận kết quả xem xét: không vi phạm.');
    }

    public function lock(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'days'   => ['required', 'integer', 'between:' . AccountService::MIN_LOCK_DAYS . ',' . AccountService::MAX_LOCK_DAYS],
        ], [], ['reason' => 'lý do', 'days' => 'thời hạn khóa']);

        $days = (int) $data['days'];

        return $this->run(fn () => $this->service->lock($this->find($id), $data['reason'], $days, $this->actorId()),
            "Đã tạm khóa tài khoản đến " . now()->addDays($days)->format('d/m/Y') . '.');
    }

    public function suspend(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'lý do']);

        return $this->run(fn () => $this->service->suspend($this->find($id), $data['reason'], $this->actorId()),
            'Đã đình chỉ vĩnh viễn tài khoản.');
    }

    public function unlock(int $id): RedirectResponse
    {
        return $this->run(fn () => $this->service->unlock($this->find($id), $this->actorId()),
            'Đã mở khóa tài khoản.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'lý do']);

        return $this->run(fn () => $this->service->deactivate($this->find($id), $data['reason'], $this->actorId()),
            'Tài khoản đã chuyển sang Ngừng hoạt động.');
    }

    private function find(int $id): Account
    {
        return Account::withProfile()->where('accounts.account_id', $id)->firstOrFail();
    }

    /** Chạy thao tác, đổi lỗi nghiệp vụ thành toast lỗi */
    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }

    /** TODO: trả về account_id của nhân viên đang đăng nhập khi đã có chức năng đăng nhập */
    private function actorId(): ?int
    {
        return null;
    }
}
