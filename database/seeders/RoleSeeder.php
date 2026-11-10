<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
//Cho phép sử dụng DB::table() để thao tác với bảng trong mysql

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Gia sư',
                'description' => 'Người đăng ký và nhận lớp dạy.',
            ],
            [
                'role_name' => 'Phụ huynh',
                'description' => 'Người gửi yêu cầu tìm gia sư.',
            ],
            [
                'role_name' => 'Nhân viên Marketing',
                'description' => 'Quản lý nội dung bài viết.',
            ],
            [
                'role_name' => 'Nhân viên Quản lý khách hàng',
                'description' => 'Quản lý yêu cầu tìm gia sư và lớp học.',
            ],
            [
                'role_name' => 'Giám đốc',
                'description' => 'Theo dõi báo cáo và thống kê.',
            ],
            [
                'role_name' => 'Nhân viên Quản lý Gia sư',
                'description' => 'Quản lý và xét duyệt hồ sơ gia sư.',
            ],
            [
                'role_name' => 'Quản lý',
                'description' => 'Quản lý tài khoản và xử lý hoàn tiền.',
            ],
        ];

       foreach ($roles as $role) {
    DB::table('roles')->updateOrInsert(
        [
            'role_name' => $role['role_name'],
        ],
        [
            'description' => $role['description'],
        ]
    );
}
    }
}