<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Notification;
use App\Models\Person;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gia sư đã đăng nhập: tổng quan, hồ sơ cá nhân (sửa thông tin, đổi mật khẩu) và thông báo.
 * Email là tên đăng nhập nên chỉ xem, không sửa.
 */
class TutorPersonalController extends Controller
{
    public function __construct(private AccountService $accounts) {}

    /** Tổng quan của gia sư */
    public function dashboard(): View
    {
        $account = Account::withProfile()->where('accounts.account_id', Auth::id())->firstOrFail();
        $checks = $this->accounts->deactivationChecks($account); // active_classes, unpaid_fees, pending_refunds

        return view('tutor.dashboard', [
            'account'        => $account,
            'totalClasses'   => DB::table('tutor_classes')->where('account_id', $account->account_id)->count(),
            'teaching'       => $checks['active_classes'],
            'pendingRefunds' => $checks['pending_refunds'],
            'notifications'  => Notification::where('account_id', $account->account_id)->orderByDesc('notification_id')->limit(3)->get(),
            'unread'         => Notification::where('account_id', $account->account_id)->where('is_read', false)->count(),
        ]);
    }

    public function edit(): View
    {
        $account = $this->account();

        return view('tutor.profile', ['account' => $account, 'person' => $account->person]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name'     => ['required', 'string', 'max:150'],
            'phone_number'  => ['required', 'regex:/^0\d{9}$/'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender'        => ['required', Rule::in(['Nam', 'Nữ', 'Khác'])],
            'address'       => ['required', 'string', 'max:500'],
            'district'      => ['nullable', 'string', 'max:100'],
        ], [
            'required'           => ':Attribute không được để trống.',
            'date'               => ':Attribute không hợp lệ.',
            'before'             => ':Attribute phải trước ngày hôm nay.',
            'in'                 => ':Attribute không hợp lệ.',
            'max'                => ':Attribute tối đa :max ký tự.',
            'phone_number.regex' => 'Số điện thoại phải gồm 10 chữ số, bắt đầu bằng 0.',
        ], [
            'full_name' => 'họ tên', 'phone_number' => 'số điện thoại', 'date_of_birth' => 'ngày sinh',
            'gender' => 'giới tính', 'address' => 'địa chỉ', 'district' => 'khu vực',
        ]);

        Person::whereKey($this->account()->person_id)->update($data);

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'required'         => ':Attribute không được để trống.',
            'current_password' => 'Mật khẩu hiện tại không đúng.',
            'min'              => ':Attribute phải có ít nhất :min ký tự.',
            'confirmed'        => 'Mật khẩu nhập lại không khớp.',
            'different'        => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ], ['current_password' => 'mật khẩu hiện tại', 'password' => 'mật khẩu mới']);

        $this->account()->update(['password' => $data['password']]); // model tự hash

        return back()->with('success', 'Đã đổi mật khẩu.');
    }

    public function notifications(): View
    {
        // Bảng notifications chưa có cột thời gian nên sắp theo mã giảm dần (mới nhất trước)
        $items = Notification::where('account_id', Auth::id())->orderByDesc('notification_id')->paginate(15);

        return view('tutor.notifications', [
            'items'  => $items,
            'unread' => Notification::where('account_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }

    public function readAll(): RedirectResponse
    {
        Notification::where('account_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    private function account(): Account
    {
        return Account::with('person')->findOrFail(Auth::id());
    }
}
