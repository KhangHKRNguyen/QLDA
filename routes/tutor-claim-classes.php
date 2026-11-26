<?php

use App\Http\Controllers\Tutor\ClassPaymentController;
use App\Http\Controllers\Tutor\OpenClassController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('gia-su')->name('tutor.')->group(function () {
    // HĐ1-HĐ2: tìm kiếm và xem lớp đang tuyển.
    Route::get('/lop-dang-tuyen', [OpenClassController::class, 'index'])
        ->name('open-classes');

    Route::get('/lop-dang-tuyen/{classId}', [OpenClassController::class, 'show'])
        ->whereNumber('classId')
        ->name('open-classes.show');

    // HĐ3-HĐ4: kiểm tra khả dụng và giữ lớp 5 phút.
    Route::post('/lop-dang-tuyen/{classId}/nhan-lop', [OpenClassController::class, 'claim'])
        ->whereNumber('classId')
        ->name('open-classes.claim');

    // HĐ5-HĐ7: màn hình theo dõi giao dịch. Các route mô phỏng chỉ chạy local/testing.
    Route::get('/thanh-toan-nhan-lop/{transactionId}', [ClassPaymentController::class, 'show'])
        ->whereNumber('transactionId')
        ->name('class-payments.show');

    Route::post('/thanh-toan-nhan-lop/{transactionId}/mo-phong-thanh-cong', [ClassPaymentController::class, 'simulateSuccess'])
        ->whereNumber('transactionId')
        ->name('class-payments.simulate-success');

    Route::post('/thanh-toan-nhan-lop/{transactionId}/mo-phong-that-bai', [ClassPaymentController::class, 'simulateFailure'])
        ->whereNumber('transactionId')
        ->name('class-payments.simulate-failure');
});
