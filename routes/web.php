<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));
Route::view('/mau-giao-dien', 'mau-giao-dien');