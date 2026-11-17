<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chỉ cho vai trò (roles.role_name) nằm trong danh sách đi tiếp.
 * Dùng sau middleware 'auth':
 *   ->middleware(['auth', EnsureRole::class . ':Gia sư'])
 *   ->middleware(['auth', EnsureRole::class . ':Quản lý,Giám đốc'])
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->role_name;

        abort_unless($role !== null && in_array($role, $roles, true), 403, 'Bạn không có quyền truy cập chức năng này.');

        return $next($request);
    }
}