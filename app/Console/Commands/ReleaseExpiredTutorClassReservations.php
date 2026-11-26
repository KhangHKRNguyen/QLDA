<?php

namespace App\Console\Commands;

use App\Services\OpenClassService;
use Illuminate\Console\Command;

class ReleaseExpiredTutorClassReservations extends Command
{
    protected $signature = 'tutor-classes:release-expired-reservations';
    protected $description = 'Giải phóng các lớp đang được giữ chỗ nhưng đã hết thời hạn 5 phút';

    public function handle(OpenClassService $classes): int
    {
        $released = $classes->releaseExpiredReservations();
        $this->info("Đã giải phóng {$released} lớp hết thời hạn giữ chỗ.");

        return self::SUCCESS;
    }
}
