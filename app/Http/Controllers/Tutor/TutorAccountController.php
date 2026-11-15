<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\TutorRegistrationRequest;
use App\Models\Account;
use App\Models\VerificationProfile;
use App\Services\AccountService;
use App\Services\TutorRegistrationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

/**
 * Phía gia sư (quy trình 2.1.5): đăng ký + nộp hồ sơ, xem trạng thái, bổ sung hồ sơ, xóa tài khoản.
 */
class TutorAccountController extends Controller
{
    public function __construct(
        private TutorRegistrationService $registration,
        private AccountService $accounts,
    ) {}

    public function create(): View
    {
        return view('tutor.register', ['types' => VerificationProfile::TYPES]);
    }

    public function store(TutorRegistrationRequest $request): RedirectResponse
    {
        try {
            $account = $this->registration->register(
                Arr::except($request->validated(), ['documents', 'password_confirmation']),
                $request->file('documents', []),
            );
        } catch (DomainException $e) {
            return back()->withInput($request->except('password', 'password_confirmation'))->with('error', $e->getMessage());
        }

        // TODO (sau khi Khang làm xong Auth): thay bằng đăng nhập thật.
        session(['tutor_account_id' => $account->account_id]);

        return redirect()->route('tutor.status')
            ->with('success', 'Đã gửi hồ sơ đăng ký. Trung tâm sẽ xác minh và thông báo kết quả cho bạn.');
    }

    public function status(): View|RedirectResponse
    {
        $account = $this->currentAccount();
        if (! $account) {
            return redirect()->route('tutor.register')->with('info', 'Vui lòng đăng ký tài khoản gia sư trước.');
        }

        $docs = VerificationProfile::where('person_id', $account->person_id)
            ->orderBy('verification_profile_id')->get();

        return view('tutor.status', [
            'account'  => $account,
            'docs'     => $docs,
            'deadline' => $docs->where('review_status', 'needs_supplement')->sortBy('supplement_deadline')->first()?->supplement_deadline,
            'blockers' => in_array($account->status, ['active', 'locked'], true) ? $this->accounts->blockerMessages($account) : [],
        ]);
    }

    public function supplement(Request $request): RedirectResponse
    {
        $account = $this->currentAccount();
        abort_unless($account, 403);

        $rules = [];
        foreach (VerificationProfile::where('person_id', $account->person_id)
                     ->where('review_status', 'needs_supplement')->pluck('verification_profile_id') as $id) {
            $rules["documents.$id"] = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }
        $request->validate($rules, [
            'required' => 'Vui lòng chọn tệp bổ sung cho từng giấy tờ được yêu cầu.',
            'mimes'    => 'Chỉ nhận tệp JPG, PNG hoặc PDF.',
            'max'      => 'Mỗi tệp tối đa 5 MB.',
            'uploaded' => 'Tải tệp lên thất bại (tệp quá lớn). Mỗi tệp tối đa 5 MB.',
        ]);

        try {
            $this->registration->submitSupplement($account, $request->file('documents', []));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã gửi hồ sơ bổ sung. Trung tâm sẽ xác minh lại.');
    }

    /** Gia sư chủ động xóa tài khoản (AccountService kiểm tra: không lớp đang dạy, không nợ phí, không hoàn phí chờ) */
    public function destroy(Request $request): RedirectResponse
    {
        $account = $this->currentAccount();
        abort_unless($account, 403);

        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'lý do']);

        try {
            $this->accounts->deactivate($account, $data['reason'], $account->account_id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        session()->forget('tutor_account_id');

        return redirect()->route('customer.home')->with('success', 'Tài khoản của bạn đã ngừng hoạt động.');
    }

    /** TODO (sau khi Khang làm xong Auth): trả về tài khoản đang đăng nhập thay vì đọc session tạm */
    private function currentAccount(): ?Account
    {
        $id = session('tutor_account_id');

        return $id ? Account::withProfile()->where('accounts.account_id', $id)->first() : null;
    }
}
