<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\TutorSearchRequestController;
use App\Http\Controllers\Staff\ArticleController;
use App\Http\Controllers\Customer\PublicArticleController;
//của khách hàng
Route::get('/', [TutorSearchRequestController::class, 'index'])
    ->name('customer.home');
// Danh sách bài viết công khai.
Route::get('/bai-viet', [PublicArticleController::class, 'index'])
    ->name('customer.articles.index');

// Hiển thị chi tiết bài viết công khai.
Route::get('/bai-viet/{postId}', [PublicArticleController::class, 'show'])
    ->name('customer.articles.show');

Route::get('/tim-gia-su', [TutorSearchRequestController::class, 'create'])
    ->name('customer.tutor-requests.create');
Route::post(
    '/tim-gia-su',
    [TutorSearchRequestController::class, 'store']
)->name('customer.tutor-requests.store');

//nhom route cua quan ly bai viet của nhân viên marketing
Route::get('/admin/bai-viet', [ArticleController::class, 'index'])
    ->name('staff.articles.index');
Route::get('/admin/bai-viet/tao-moi', [ArticleController::class, 'create'])
    ->name('staff.articles.create');
// Tiếp nhận dữ liệu tạo bài viết.
Route::post('/admin/bai-viet', [ArticleController::class, 'store'])
    ->name('staff.articles.store');
// Hiển thị form chỉnh sửa bài viết.
Route::get('/admin/bai-viet/{postId}/chinh-sua', [ArticleController::class, 'edit'])
    ->name('staff.articles.edit');
// Cập nhật bài viết.
Route::put('/admin/bai-viet/{postId}', [ArticleController::class, 'update'])
    ->name('staff.articles.update');
// Đăng bài viết.
Route::patch('/admin/bai-viet/{postId}/dang', [ArticleController::class, 'publish'])
    ->name('staff.articles.publish');
// Ẩn bài viết.
Route::patch('/admin/bai-viet/{postId}/an', [ArticleController::class, 'hide'])
    ->name('staff.articles.hide');
// Hiện lại bài viết.
Route::patch('/admin/bai-viet/{postId}/hien-lai', [ArticleController::class, 'unhide'])
    ->name('staff.articles.unhide');
// Xóa bài viết.
Route::delete('/admin/bai-viet/{postId}', [ArticleController::class, 'destroy'])
    ->name('staff.articles.destroy');
//Route::get('/', fn () => view('welcome'));
Route::view('/mau-giao-dien', 'mau-giao-dien');