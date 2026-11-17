<?php

use App\Http\Controllers\Admin\TutorProfileController;
use App\Http\Controllers\Tutor\TutorAccountController;
use App\Http\Controllers\Tutor\TutorPersonalController;
use App\Http\Middleware\EnsureRole;
use App\Services\TutorRegistrationService;
use Illuminate\Support\Facades\Route;

// Gia sư chưa đăng nhập: đăng ký tài khoản + nộp hồ sơ
Route::middleware('guest')->prefix('gia-su')->name('tutor.')->controller(TutorAccountController::class)->group(function () {
    Route::get('/dang-ky', 'create')->name('register');
    Route::post('/dang-ky', 'store')->name('register.store');
});

// Gia sư đã đăng nhập: xem trạng thái, bổ sung hồ sơ, xóa tài khoản
Route::middleware(['auth', EnsureRole::class . ':' . TutorRegistrationService::TUTOR_ROLE])
    ->prefix('gia-su')->name('tutor.')->controller(TutorAccountController::class)->group(function () {
        Route::get('/', [TutorPersonalController::class, 'dashboard'])->name('dashboard');
        Route::get('/trang-thai', 'status')->name('status');
        Route::post('/bo-sung-ho-so', 'supplement')->name('supplement');
        Route::delete('/tai-khoan', 'destroy')->name('account.destroy');

        // Hồ sơ cá nhân + thông báo
        Route::get('/ho-so', [TutorPersonalController::class, 'edit'])->name('profile.edit');
        Route::put('/ho-so', [TutorPersonalController::class, 'update'])->name('profile.update');
        Route::put('/ho-so/mat-khau', [TutorPersonalController::class, 'updatePassword'])->name('profile.password');
        Route::get('/thong-bao', [TutorPersonalController::class, 'notifications'])->name('notifications');
        Route::post('/thong-bao/doc-tat-ca', [TutorPersonalController::class, 'readAll'])->name('notifications.read');
    });

// Nhân viên Quản lý Gia sư: duyệt hồ sơ
Route::middleware(['auth', EnsureRole::class . ':' . TutorRegistrationService::STAFF_ROLE])
    ->prefix('admin/gia-su')->name('admin.tutor-profiles.')->controller(TutorProfileController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/tong-quan', 'dashboard')->name('dashboard');
        Route::get('/thong-bao', 'notifications')->name('notifications');
        Route::post('/thong-bao/doc-tat-ca', 'readAllNotifications')->name('notifications.read');
        Route::get('/tai-lieu/{documentId}', 'document')->whereNumber('documentId')->name('document');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/{id}/duyet', 'approve')->whereNumber('id')->name('approve');
        Route::post('/{id}/yeu-cau-bo-sung', 'requestSupplement')->whereNumber('id')->name('supplement');
        Route::post('/{id}/tu-choi', 'reject')->whereNumber('id')->name('reject');
        Route::post('/{id}/tam-khoa', 'lock')->whereNumber('id')->name('lock');
        Route::post('/{id}/dinh-chi', 'suspend')->whereNumber('id')->name('suspend');
        Route::post('/{id}/mo-khoa', 'unlock')->whereNumber('id')->name('unlock');
    });
