<?php

namespace App\Console\Commands;

use App\Services\TutorRegistrationService;
use Illuminate\Console\Command;

class ProcessTutorRegistrations extends Command
{
    protected $signature = 'tutor:process-registrations';

    protected $description = 'Nhắc hạn bổ sung hồ sơ, hủy đăng ký quá hạn và xóa hồ sơ đã hủy quá 30 ngày';

    public function handle(TutorRegistrationService $service): int
    {
        $r = $service->processScheduled();
        $this->info("Đã nhắc: {$r['reminded']} | Đã hủy: {$r['cancelled']} | Đã xóa vĩnh viễn: {$r['purged']}");

        return self::SUCCESS;
    }
}
