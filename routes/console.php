<?php

use App\Services\AccountService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Biểu đồ KhoaTaiKhoan: Scheduler định kỳ mở khóa các tài khoản hết hạn tạm khóa
Artisan::command('accounts:unlock-expired', function (AccountService $service) {
    $this->info('Đã mở khóa ' . $service->unlockExpired() . ' tài khoản hết hạn tạm khóa.');
})->purpose('Mở khóa các tài khoản đã hết thời hạn tạm khóa');

Schedule::command('accounts:unlock-expired')->everyFiveMinutes();
