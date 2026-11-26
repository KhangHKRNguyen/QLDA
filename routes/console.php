<?php

use App\Services\AccountService;
use App\Services\RefundService;
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

// Hoàn phí: quá 15 ngày chưa quyết định → "Quá hạn" + báo Giám đốc; hỏi lại cổng thanh toán cho giao dịch chưa có kết quả

Artisan::command('refunds:mark-overdue', function (RefundService $service) {
    $this->info('Đã chuyển ' . $service->markOverdue() . ' yêu cầu hoàn phí sang Quá hạn.');
})->purpose('Đánh dấu yêu cầu hoàn phí quá hạn xử lý 15 ngày');

Artisan::command('refunds:sync', function (RefundService $service) {
    $this->info('Đã kiểm tra lại ' . $service->syncPendingRefunds() . ' giao dịch hoàn tiền chưa có kết quả.');
})->purpose('Hỏi lại cổng thanh toán các giao dịch hoàn tiền đang chờ');

Schedule::command('refunds:mark-overdue')->hourly();
Schedule::command('refunds:sync')->everyFiveMinutes();
Schedule::command('tutor-classes:release-expired-reservations')->everyMinute();

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
Schedule::command('tutor:process-registrations')->daily();