<?php

namespace App\Providers;

use App\Models\Account;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phân quyền theo bảng permissions: middleware('can:account.view'), @can('account.unlock'), Gate::allows(...)
        // Trả null khi không có quyền để các Policy / Gate khác (nếu nhóm thêm sau) vẫn được xét.
        Gate::before(fn ($user, string $ability) => $user instanceof Account && $user->hasPermission($ability) ? true : null);

        // SQLite so sánh chuỗi theo mã Unicode nên "Đỗ" đứng sau "Z", "Ngô" sau "Nguyễn".
        // Đăng ký collation "vi": so sánh theo chữ đã bỏ dấu (Đ ~ D), trùng thì so bản gốc.
        // Dùng: ORDER BY cot COLLATE vi. MySQL (utf8mb4_unicode_ci) đã đúng sẵn, không cần.
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            $pdo = $event->connection->getDriverName() === 'sqlite' ? $event->connection->getPdo() : null;

            if ($pdo instanceof \Pdo\Sqlite) {
                $pdo->createCollation('vi', fn (string $a, string $b) => strcmp(Str::lower(Str::ascii($a)), Str::lower(Str::ascii($b))) ?: strcmp($a, $b));
            }
        });
    }
}
