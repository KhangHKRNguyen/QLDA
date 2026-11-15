<?php

use App\Http\Controllers\Admin\AccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\TutorSearchRequestController;
use App\Http\Controllers\Staff\ArticleController;
use App\Http\Controllers\Customer\PublicArticleController;

/*
| 1. LUỒNG CÔNG KHAI
*/
// Trang chủ công khai
Route::get('/', [TutorSearchRequestController::class, 'index'])
    ->name('customer.home');

// Danh sách & Chi tiết bài viết công khai
Route::get('/bai-viet', [PublicArticleController::class, 'index'])
    ->name('customer.articles.index');
Route::get('/bai-viet/{postId}', [PublicArticleController::class, 'show'])
    ->name('customer.articles.show');

// Tính năng tìm gia sư của khách
Route::get('/tim-gia-su', [TutorSearchRequestController::class, 'create'])
    ->name('customer.tutor-requests.create');
Route::post('/tim-gia-su', [TutorSearchRequestController::class, 'store'])
    ->name('customer.tutor-requests.store');

// Trang mẫu giao diện (nếu cần test)
Route::view('/mau-giao-dien', 'mau-giao-dien');


/*
| 2. LUỒNG QUẢN TRỊ & NỘI BỘ
*/

// Nhóm quản lý tài khoản
Route::prefix('admin/tai-khoan')->name('admin.accounts.')->controller(AccountController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/them-moi', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}/tam-khoa', 'lock')->whereNumber('id')->name('lock');
    Route::post('/{id}/dinh-chi', 'suspend')->whereNumber('id')->name('suspend');
    Route::post('/{id}/mo-khoa', 'unlock')->whereNumber('id')->name('unlock');
    Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
});

// Nhóm quản lý bài viết
Route::prefix('admin')->name('staff.articles.')->group(function () {
    Route::get('/bai-viet', [ArticleController::class, 'index'])->name('index');
    Route::get('/bai-viet/tao-moi', [ArticleController::class, 'create'])->name('create');
    Route::post('/bai-viet', [ArticleController::class, 'store'])->name('store');
    Route::get('/bai-viet/{postId}/chinh-sua', [ArticleController::class, 'edit'])->name('edit');
    Route::put('/bai-viet/{postId}', [ArticleController::class, 'update'])->name('update');
    Route::patch('/bai-viet/{postId}/dang', [ArticleController::class, 'publish'])->name('publish');
    Route::patch('/bai-viet/{postId}/an', [ArticleController::class, 'hide'])->name('hide');
    Route::patch('/bai-viet/{postId}/hien-lai', [ArticleController::class, 'unhide'])->name('unhide');
    Route::delete('/bai-viet/{postId}', [ArticleController::class, 'destroy'])->name('destroy');
});

// Các giao diện quản lý bổ sung
Route::get('/admin/yeu-cau', function () {
    return view('admin.tutor_requests.index');
})->name('admin.tutor-requests.index');

Route::get('/admin/lop-hoc', function () {
    return view('admin.tutor_classes.index');
})->name('admin.tutor-classes.index');
require __DIR__.'/tutor-accounts.php';