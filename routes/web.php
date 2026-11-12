<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer\TutorSearchRequestController;

Route::get('/', [TutorSearchRequestController::class, 'index'])
    ->name('customer.home');
Route::get('/tim-gia-su', [TutorSearchRequestController::class, 'create'])
    ->name('customer.tutor-requests.create');
Route::post(
    '/tim-gia-su',
    [TutorSearchRequestController::class, 'store']
)->name('customer.tutor-requests.store');
//Route::get('/', fn () => view('welcome'));
Route::view('/mau-giao-dien', 'mau-giao-dien');