<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            // Gia sư
            'Gia sư' => [
                'class.view',
                'class.register',
                'payment.view',
                'payment.pay_class_fee',
                'refund.create',
                'refund.update',
            ],

            // Phụ huynh
            'Phụ huynh' => [
                'tutor_request.create',
            ],

            // Nhân viên Marketing
            'Nhân viên Marketing' => [
                'post.view',
                'post.create',
                'post.update',
                'post.delete',
                'post.hide',
                'post.show',
            ],

            // Nhân viên Quản lý khách hàng
            'Nhân viên Quản lý khách hàng' => [
                'tutor_request.view',
                'tutor_request.create',
                'tutor_request.update',
                'tutor_request.delete',
                'class.view',
                'class.create',
                'class.update',
                'class.delete',
                'class.hide',
                'class.show',
                'class.update_status',
                'refund.create',
                'refund.update',
            ],

            // Giám đốc
            'Giám đốc' => [
                'report.view',
                'report.export',
            ],

            // Nhân viên Quản lý Gia sư
            'Nhân viên Quản lý Gia sư' => [
                'tutor_request.view',
                'verification.view',
                'verification.approve',
                'verification.reject',
                'verification.request_supplement',
                'account.lock_temporary',
                'account.lock_permanent',
                'account.unlock',
            ],

            // Quản lý
            'Quản lý' => [
                'account.view',
                'account.search',
                'account.create',
                'account.delete',
                'account.lock_temporary',
                'account.lock_permanent',
                'account.unlock',
                'refund.view',
                'refund.approve',
                'refund.reject',
                'refund.execute',
            ],
        ];

        // Lấy ID vai trò theo tên
        $roles = DB::table('roles')
            ->pluck('role_id', 'role_name');

        // Lấy ID quyền theo tên
        $permissions = DB::table('permissions')
            ->pluck('permission_id', 'permission_name');

        $data = [];

        // Ghép ID vai trò với ID quyền
        foreach ($rolePermissions as $roleName => $permissionNames) {
            if (!isset($roles[$roleName])) {
                throw new \RuntimeException(
                    "Không tìm thấy vai trò: {$roleName}"
                );
            }

            foreach ($permissionNames as $permissionName) {
                if (!isset($permissions[$permissionName])) {
                    throw new \RuntimeException(
                        "Không tìm thấy quyền: {$permissionName}"
                    );
                }

                $data[] = [
                    'role_id' => $roles[$roleName],
                    'permission_id' => $permissions[$permissionName],
                ];
            }
        }

        // Thêm dữ liệu vào bảng trung gian
DB::table('permission_role')->insertOrIgnore($data);
    }
}
