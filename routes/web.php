<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Customer\TutorSearchRequestController;
use App\Http\Controllers\Customer\PublicArticleController;
use App\Http\Controllers\Staff\ArticleController;
use Illuminate\Support\Facades\Route;


/*
| 1. LUỒNG CÔNG KHAI
*/

// Trang chủ
Route::get('/', [TutorSearchRequestController::class, 'index'])
    ->name('customer.home');

// Bài viết công khai
Route::get('/bai-viet', [PublicArticleController::class, 'index'])
    ->name('customer.articles.index');

Route::get('/bai-viet/{postId}', [PublicArticleController::class, 'show'])
    ->name('customer.articles.show');

// Khách hàng tìm gia sư
Route::get('/tim-gia-su', [TutorSearchRequestController::class, 'create'])
    ->name('customer.tutor-requests.create');

Route::post('/tim-gia-su', [TutorSearchRequestController::class, 'store'])
    ->name('customer.tutor-requests.store');

// Trang mẫu giao diện
Route::view('/mau-giao-dien', 'mau-giao-dien');


/*
| 2. QUẢN LÝ TÀI KHOẢN — phải đăng nhập; mỗi thao tác cần đúng quyền account.* (bảng permissions)
*/

Route::prefix('admin/tai-khoan')
    ->name('admin.accounts.')
    ->middleware('auth')
    ->controller(AccountController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->middleware('can:account.view')
            ->name('index');

        Route::get('/them-moi', 'create')
            ->middleware('can:account.create')
            ->name('create');

        Route::post('/', 'store')
            ->middleware('can:account.create')
            ->name('store');

        Route::get('/{id}', 'show')
            ->whereNumber('id')
            ->middleware('can:account.view')
            ->name('show');

        Route::post('/{id}/tam-khoa', 'lock')
            ->whereNumber('id')
            ->middleware('can:account.lock_temporary')
            ->name('lock');

        Route::post('/{id}/dinh-chi', 'suspend')
            ->whereNumber('id')
            ->middleware('can:account.lock_permanent')
            ->name('suspend');

        Route::post('/{id}/mo-khoa', 'unlock')
            ->whereNumber('id')
            ->middleware('can:account.unlock')
            ->name('unlock');

        Route::delete('/{id}', 'destroy')
            ->whereNumber('id')
            ->middleware('can:account.delete')
            ->name('destroy');
    });


/*
| 2b. TRUNG TÂM XỬ LÝ HOÀN PHÍ — vai trò Quản lý (quyền refund.* trong bảng permissions)
*/

Route::prefix('admin/hoan-phi')
    ->name('admin.refunds.')
    ->middleware(['auth', 'can:refund.view'])
    ->controller(RefundController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');

        Route::get('/{id}', 'show')
            ->whereNumber('id')
            ->name('show');

        Route::get('/{id}/bang-chung', 'evidence')
            ->whereNumber('id')
            ->name('evidence');

        Route::post('/{id}/duyet', 'approve')
            ->whereNumber('id')
            ->middleware('can:refund.approve')
            ->name('approve');

        Route::post('/{id}/tu-choi', 'reject')
            ->whereNumber('id')
            ->middleware('can:refund.reject')
            ->name('reject');

        Route::post('/{id}/hoan-tien-lai', 'retry')
            ->whereNumber('id')
            ->middleware('can:refund.execute')
            ->name('retry');
    });


/*
| 3. QUẢN LÝ BÀI VIẾT
*/

Route::prefix('admin')
    ->name('staff.articles.')
    ->group(function () {
        Route::get('/bai-viet', [ArticleController::class, 'index'])
            ->name('index');

        Route::get('/bai-viet/tao-moi', [ArticleController::class, 'create'])
            ->name('create');

        Route::post('/bai-viet', [ArticleController::class, 'store'])
            ->name('store');

        Route::get('/bai-viet/{postId}/chinh-sua', [ArticleController::class, 'edit'])
            ->name('edit');

        Route::put('/bai-viet/{postId}', [ArticleController::class, 'update'])
            ->name('update');

        Route::patch('/bai-viet/{postId}/dang', [ArticleController::class, 'publish'])
            ->name('publish');

        Route::patch('/bai-viet/{postId}/an', [ArticleController::class, 'hide'])
            ->name('hide');

        Route::patch('/bai-viet/{postId}/hien-lai', [ArticleController::class, 'unhide'])
            ->name('unhide');

        Route::delete('/bai-viet/{postId}', [ArticleController::class, 'destroy'])
            ->name('destroy');
    });


/*
| 4. CÁC GIAO DIỆN QUẢN LÝ BỔ SUNG
*/

Route::get('/admin/yeu-cau', function () {
    return view('admin.tutor_requests.index');
})->name('admin.tutor-requests.index');

Route::get('/admin/lop-hoc', function () {
    return view('admin.tutor_classes.index');
})->name('admin.tutor-classes.index');
require __DIR__ . '/qlkh.php';
require __DIR__.'/tutor-accounts.php';
require __DIR__ . '/teaching.php'; // Giảng dạy và hoàn phí cho Gia sư, NV QLKH, Giám đốc (Trang)


// Đăng nhập xong vào đây: mỗi vai trò thêm 1 nhánh if chuyển tới trang của mình,
// vai trò chưa có trang riêng thì vẫn thấy dashboard trắng.
Route::get('/dashboard', function () {
    $role = auth()->user()->role?->role_name; // tên vai trò trong bảng roles

    // Quản lý → Quản lý tài khoản (Trang)
    if ($role === 'Quản lý') {
        return redirect()->route('admin.accounts.index');
    }

    if ($role === 'Gia sư') {
        return redirect()->route('tutor.dashboard');
    }
    if ($role === 'Nhân viên Quản lý Gia sư') {
        return redirect()->route('admin.tutor-profiles.dashboard');
    }

    if ($role === 'Nhân viên Marketing') {
        return redirect()->route('staff.articles.index');
    }

    if ($role === 'Nhân viên Quản lý Khách hàng') {
        return redirect()->route('admin.tutor-requests.index');
    }

    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});

require __DIR__ . '/auth.php';