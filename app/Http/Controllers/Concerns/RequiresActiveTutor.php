<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;

trait RequiresActiveTutor
{
    protected function activeTutor(): Account
    {
        $account = Account::query()
            ->with('role')
            ->where('accounts.account_id', Auth::id())
            ->firstOrFail();

        abort_unless($account->role?->role_name === 'Gia sư', 403, 'Chức năng này chỉ dành cho Gia sư.');
        abort_unless($account->status === 'active', 403, 'Tài khoản Gia sư chưa được kích hoạt hoặc đang bị khóa.');

        return $account;
    }
}
