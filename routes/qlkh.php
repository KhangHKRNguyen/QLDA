<?php

use App\Http\Controllers\TutorClassController;
use App\Http\Controllers\TutorRequestController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

$qlkhMiddleware = ['auth', EnsureRole::class . ':Nhân viên Quản lý Khách hàng,Quản lý,Giám đốc'];

Route::prefix('admin/yeu-cau')
    ->name('admin.tutor-requests.')
    ->middleware($qlkhMiddleware)
    ->controller(TutorRequestController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });

Route::prefix('admin/lop-hoc')
    ->name('admin.tutor-classes.')
    ->middleware($qlkhMiddleware)
    ->controller(TutorClassController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::post('/tao-tu-yeu-cau/{requestId}', 'storeFromRequest')->whereNumber('requestId')->name('store-from-request');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::patch('/{id}/an-hien', 'toggleVisibility')->whereNumber('id')->name('toggle-visibility');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
    });