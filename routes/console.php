<?php

use App\Services\AccountService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\TutorClass;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mở khóa tài khoản định kỳ

Artisan::command('accounts:unlock-expired', function (AccountService $service) {
    $this->info('Đã mở khóa ' . $service->unlockExpired() . ' tài khoản hết hạn tạm khóa.');
})->purpose('Mở khóa các tài khoản đã hết thời hạn tạm khóa');

Schedule::command('accounts:unlock-expired')->everyFiveMinutes();


// Mở khóa lớp học gia sư định kỳ
Schedule::call(function () {
    TutorClass::where('status', 'Processing')
         ->where('locked_until', '<=', now())
         ->update([
             'status' => 'Recruiting',
             'holding_tutor_id' => null,
             'locked_until' => null
         ]);
})->everyMinute()->name('unlock_expired_tutor_classes')->withoutOverlapping();