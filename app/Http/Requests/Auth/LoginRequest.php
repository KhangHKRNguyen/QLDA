<?php

namespace App\Http\Requests\Auth;

use App\Models\Account;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::attemptWhen(
            $this->only('username', 'password'),

            function (Account $account) {
                // Các tài khoản được phép đăng nhập.
                // Tài khoản đang khóa hoặc đình chỉ sẽ không đăng nhập được.
                return in_array($account->status, [
                    'active',
                    'pending_verification',
                    'supplement_required',
                    'incomplete',
                ], true);
            },

            false // Chưa bật Remember Me vì chưa xác nhận accounts có remember_token.
        );

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'Tên đăng nhập hoặc mật khẩu không chính xác, hoặc tài khoản chưa được phép đăng nhập.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('username')) . '|' . $this->ip()
        );
    }
}