<?php

use App\Http\Controllers\TutorRequestController;
use App\Http\Controllers\TutorClassController;

// --- API CHUNG ---
Route::get('/yeu-cau', [TutorRequestController::class, 'index']);
Route::get('/yeu-cau/{id}', [TutorRequestController::class, 'show']);
Route::get('/lop-hoc', [TutorClassController::class, 'index']);
Route::get('/lop-hoc/{id}', [TutorClassController::class, 'show']);

// --- API DÀNH RIÊNG CHO QUẢN LÝ KHÁCH HÀNG (CRUD) ---
Route::post('/yeu-cau', [TutorRequestController::class, 'store']);
Route::put('/yeu-cau/{id}', [TutorRequestController::class, 'update']);
Route::delete('/yeu-cau/{id}', [TutorRequestController::class, 'destroy']);

Route::post('/yeu-cau/{id}/tao-lop', [TutorClassController::class, 'storeFromRequest']);
Route::put('/lop-hoc/{id}', [TutorClassController::class, 'update']);

// --- API DÀNH RIÊNG CHO GIA SƯ ---
Route::post('/lop-hoc/{id}/nhan-lop', [TutorClassController::class, 'assignClass']);