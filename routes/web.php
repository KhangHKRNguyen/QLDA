<?php

use Illuminate\Support\Facades\Route;

// Trang chủ mặc định
Route::get('/', function () {
    return view('welcome');
});

// Giao diện Quản lý Khách hàng
Route::get('/admin/yeu-cau', function () {
    return view('admin.tutor_requests.index');
});

// Giao diện Quản lý Lớp học
Route::get('/admin/lop-hoc', function () {
    return view('admin.tutor_classes.index');
});