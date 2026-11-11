<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Role;
use App\Services\AccountService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Quản lý tài khoản — vai trò Quản lý (Chương 2):
 * tìm kiếm theo mã hoặc tên, hiển thị danh sách, Thêm / Xóa (vô hiệu hóa) / Khóa / Mở khóa.
 * Logic nghiệp vụ nằm ở App\Services\AccountService.
 */
class AccountController extends Controller
{
    /** Vai trò không có tài khoản đăng nhập (phụ huynh liên hệ qua tổng đài) */
    private const ROLES_WITHOUT_ACCOUNT = ['Phụ huynh'];

    /** Cột được phép sắp xếp: khóa trên URL (?sort=...) => cột trong DB */
    public const SORTABLE = [
        'ma'         => 'accounts.account_id',
        'ten'        => 'people.full_name',
        'lien-he'    => 'people.email',
        'vai-tro'    => 'roles.role_name',
        'trang-thai' => 'accounts.status',
        'cap-nhat'   => 'accounts.status_updated_at',
    ];

    public function __construct(private AccountService $service) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $sort = (string) $request->query('sort');
        $sort = isset(self::SORTABLE[$sort]) ? $sort : 'ma';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        // "Mã" tài khoản: nhập 12, #12 hoặc TK12 đều tìm theo account_id = 12
        $code = preg_match('/^(#|tk)?0*(\d+)$/i', $q, $m) ? (int) $m[2] : null;

        $accounts = Account::withProfile()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->when($code, fn ($w) => $w->orWhere('accounts.account_id', $code))
                ->orWhere('people.full_name', 'like', "%{$q}%")
                ->orWhere('accounts.username', 'like', "%{$q}%")
                ->orWhere('people.email', 'like', "%{$q}%")
                ->orWhere('people.phone_number', 'like', "%{$q}%")))
            ->when($request->query('role'), fn ($query, $role) => $query->where('roles.role_name', $role))
            ->when($request->query('status'), fn ($query, $status) => $query->where('accounts.status', $status))
            ->when(
                in_array($sort, ['ten', 'vai-tro'], true) && Account::query()->getConnection()->getDriverName() === 'sqlite',
                fn ($query) => $query->orderByRaw(self::SORTABLE[$sort] . " COLLATE vi {$dir}"), // sắp theo tiếng Việt (xem AppServiceProvider)
                fn ($query) => $query->orderBy(self::SORTABLE[$sort], $dir),
            )
            ->when($sort !== 'ma', fn ($query) => $query->orderBy('accounts.account_id')) // trùng giá trị thì theo mã
            ->paginate(15)
            ->withQueryString();

        $counts = Account::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.accounts.index', [
            'accounts' => $accounts,
            'sort'     => $sort,
            'dir'      => $dir,
            'statuses' => Account::STATUSES,
            'roles'    => $this->assignableRoles()->pluck('role_name'),
            'stats'    => [
                'all'                  => $counts->sum(),
                'active'               => $counts['active'] ?? 0,
                'locked'               => $counts['locked'] ?? 0,
                'suspended'            => $counts['suspended'] ?? 0,
                'pending_verification' => $counts['pending_verification'] ?? 0,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.accounts.create', ['roles' => $this->assignableRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name'     => ['required', 'string', 'max:150'],
            'phone_number'  => ['required', 'regex:/^0\d{9}$/', Rule::unique('people', 'phone_number')],
            'email'         => ['required', 'email', 'max:255', Rule::unique('people', 'email')],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender'        => ['nullable', Rule::in(['Nam', 'Nữ', 'Khác'])],
            'address'       => ['nullable', 'string', 'max:500'],
            'district'      => ['nullable', 'string', 'max:100'],
            'username'      => ['required', 'string', 'min:4', 'max:100', 'alpha_dash:ascii', Rule::unique('accounts', 'username')],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'role_id'       => ['required', Rule::in($this->assignableRoles()->pluck('role_id')->all())],
        ], [
            'required'           => ':Attribute không được để trống.',
            'unique'             => ':Attribute đã được sử dụng.',
            'email'              => ':Attribute không hợp lệ.',
            'date'               => ':Attribute không hợp lệ.',
            'in'                 => ':Attribute không hợp lệ.',
            'before'             => ':Attribute phải trước ngày hôm nay.',
            'min'                => ':Attribute phải có ít nhất :min ký tự.',
            'max'                => ':Attribute tối đa :max ký tự.',
            'phone_number.regex' => 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.',
            'username.alpha_dash' => 'Tên đăng nhập chỉ gồm chữ không dấu, số, dấu gạch ngang và gạch dưới.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ], [
            'full_name' => 'họ tên', 'phone_number' => 'số điện thoại', 'email' => 'email',
            'date_of_birth' => 'ngày sinh', 'gender' => 'giới tính', 'address' => 'địa chỉ',
            'district' => 'khu vực', 'username' => 'tên đăng nhập', 'password' => 'mật khẩu', 'role_id' => 'vai trò',
        ]);

        $account = $this->service->create($data, $this->actorId());

        return redirect()->route('admin.accounts.show', $account->account_id)
            ->with('success', "Đã thêm tài khoản {$account->username}.");
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

    public function lock(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'days'   => ['required', 'integer', 'between:' . AccountService::MIN_LOCK_DAYS . ',' . AccountService::MAX_LOCK_DAYS],
        ], [], ['reason' => 'lý do', 'days' => 'thời hạn khóa']);

        $days = (int) $data['days'];

        return $this->run(fn () => $this->service->lock($this->find($id), $data['reason'], $days, $this->actorId()),
            'Đã tạm khóa tài khoản đến ' . now()->addDays($days)->format('d/m/Y') . '.');
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

    /** Các vai trò được cấp tài khoản (bỏ Phụ huynh) */
    private function assignableRoles(): Collection
    {
        return Role::whereNotIn('role_name', self::ROLES_WITHOUT_ACCOUNT)->orderBy('role_id')->get(['role_id', 'role_name']);
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

    /** TODO (sau khi Khang làm xong Auth): trả về account_id của người đang đăng nhập */
    private function actorId(): ?int
    {
        return null;
    }
}
