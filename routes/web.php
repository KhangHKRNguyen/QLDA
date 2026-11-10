<?php

use App\Http\Controllers\Admin\AccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/mau-giao-dien', 'mau-giao-dien');

// Quản lý tài khoản (TODO: thêm middleware đăng nhập + quyền account.* khi có auth)
Route::prefix('admin/tai-khoan')->name('admin.accounts.')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}/xem-xet', 'review')->whereNumber('id')->name('review');
    Route::post('/{id}/tam-khoa', 'lock')->whereNumber('id')->name('lock');
    Route::post('/{id}/dinh-chi', 'suspend')->whereNumber('id')->name('suspend');
    Route::post('/{id}/mo-khoa', 'unlock')->whereNumber('id')->name('unlock');
    Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
});
