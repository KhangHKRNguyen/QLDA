<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\TutorClass;

Schedule::call(function () {
    TutorClass::where('status', 'Processing')
          ->where('locked_until', '<=', now())
          ->update([
              'status' => 'Recruiting',
              'holding_tutor_id' => null,
              'locked_until' => null
          ]);
})->everyMinute()->name('unlock_expired_tutor_classes')->withoutOverlapping();
