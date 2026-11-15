<?php

use App\Http\Controllers\Admin\TutorProfileController;
use App\Http\Controllers\Tutor\TutorAccountController;
use Illuminate\Support\Facades\Route;

// Gia sư: đăng ký, xem trạng thái, bổ sung hồ sơ, xóa tài khoản
Route::prefix('gia-su')->name('tutor.')->controller(TutorAccountController::class)->group(function () {
    Route::get('/dang-ky', 'create')->name('register');
    Route::post('/dang-ky', 'store')->name('register.store');
    Route::get('/trang-thai', 'status')->name('status');
    Route::post('/bo-sung-ho-so', 'supplement')->name('supplement');
    Route::delete('/tai-khoan', 'destroy')->name('account.destroy');
});

// Nhân viên Quản lý Gia sư: duyệt hồ sơ
Route::prefix('admin/gia-su')->name('admin.tutor-profiles.')->controller(TutorProfileController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/tai-lieu/{documentId}', 'document')->whereNumber('documentId')->name('document');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}/duyet', 'approve')->whereNumber('id')->name('approve');
    Route::post('/{id}/yeu-cau-bo-sung', 'requestSupplement')->whereNumber('id')->name('supplement');
    Route::post('/{id}/tu-choi', 'reject')->whereNumber('id')->name('reject');
});
