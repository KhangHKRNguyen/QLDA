<?php

use App\Http\Controllers\Tutor\ClassPaymentController;
use Illuminate\Support\Facades\Route;

// Require file này từ routes/api.php để webhook không bị middleware CSRF của web.
Route::post('/webhooks/class-payment/{provider}', [ClassPaymentController::class, 'webhook'])
    ->where('provider', '[A-Za-z0-9_-]+')
    ->middleware('throttle:60,1')
    ->name('webhooks.class-payment');
