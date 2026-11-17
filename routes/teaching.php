<?php

/*
| Giảng dạy và xử lý hoàn phí cho các vai trò khác (Trang) — PTTKHT 2.1.8, 2.2.9.2
| Gọi từ routes/web.php: require __DIR__ . '/teaching.php';
*/

use App\Http\Controllers\Admin\OverdueRefundController;
use App\Http\Controllers\Admin\TeachingController as StaffTeachingController;
use App\Http\Controllers\Tutor\TeachingController as TutorTeachingController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Gia sư: "Lớp của tôi" + "Yêu cầu hoàn phí" (chỉ thấy dữ liệu của chính mình)
Route::middleware(['auth', EnsureRole::class . ':Gia sư'])
    ->prefix('gia-su')->name('tutor.')->controller(TutorTeachingController::class)->group(function () {
        Route::get('/lop-cua-toi', 'classes')->name('classes.index');
        Route::get('/lop-cua-toi/{id}', 'showClass')->whereNumber('id')->name('classes.show');
        Route::get('/lop-cua-toi/{id}/yeu-cau-hoan-phi', 'createRefund')->whereNumber('id')->middleware('can:refund.create')->name('refunds.create');
        Route::post('/lop-cua-toi/{id}/yeu-cau-hoan-phi', 'storeRefund')->whereNumber('id')->middleware('can:refund.create')->name('refunds.store');

        Route::get('/hoan-phi', 'refunds')->name('refunds.index');
        Route::get('/hoan-phi/{id}', 'showRefund')->whereNumber('id')->name('refunds.show');
        Route::get('/hoan-phi/{id}/bang-chung', 'evidence')->whereNumber('id')->name('refunds.evidence');
        Route::post('/hoan-phi/{id}/xac-nhan', 'confirm')->whereNumber('id')->middleware('can:refund.update')->name('refunds.confirm');
        Route::post('/hoan-phi/{id}/xem-xet-lai', 'reconsider')->whereNumber('id')->middleware('can:refund.update')->name('refunds.reconsider');
    });

// NV Quản lý khách hàng: "Lớp đang dạy" — dạy thử, hoàn thành, phiếu hoàn phí theo phản ánh phụ huynh
Route::middleware(['auth', EnsureRole::class . ':Nhân viên Quản lý khách hàng'])
    ->prefix('admin/giang-day')->name('admin.teaching.')->controller(StaffTeachingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}/day-thu', 'trial')->whereNumber('id')->name('trial');
        Route::post('/{id}/hoan-thanh', 'complete')->whereNumber('id')->name('complete');
        Route::get('/{id}/yeu-cau-hoan-phi', 'createRefund')->whereNumber('id')->middleware('can:refund.create')->name('refund.create');
        Route::post('/{id}/yeu-cau-hoan-phi', 'storeRefund')->whereNumber('id')->middleware('can:refund.create')->name('refund.store');

        Route::get('/hoan-phi', 'refunds')->name('refunds.index');
        Route::get('/hoan-phi/{id}', 'showRefund')->whereNumber('id')->name('refunds.show');
        Route::get('/hoan-phi/{id}/bang-chung', 'evidence')->whereNumber('id')->name('refunds.evidence');
        Route::post('/hoan-phi/{id}/xac-nhan', 'confirm')->whereNumber('id')->middleware('can:refund.update')->name('refunds.confirm');
        Route::post('/hoan-phi/{id}/xem-xet-lai', 'reconsider')->whereNumber('id')->middleware('can:refund.update')->name('refunds.reconsider');
    });

// Giám đốc: danh sách yêu cầu hoàn phí quá hạn
Route::middleware(['auth', EnsureRole::class . ':Giám đốc'])
    ->prefix('admin/hoan-phi-qua-han')->name('admin.refunds.overdue.')->controller(OverdueRefundController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{id}/nhac-xu-ly', 'remind')->whereNumber('id')->name('remind');
    });
