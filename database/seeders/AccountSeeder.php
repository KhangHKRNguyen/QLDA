<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'tutor01',
                'role_name' => 'Gia sư',
                'full_name' => 'Nguyễn Văn An',
                'phone_number' => '0900000001',
                'email' => 'tutor01@gmail.com',
            ],
           
            [
                'username' => 'marketing01',
                'role_name' => 'Nhân viên Marketing',
                'full_name' => 'Lê Văn Cường',
                'phone_number' => '0900000003',
                'email' => 'marketing01@gmail.com',
            ],
            [
                'username' => 'qlkh01',
                'role_name' => 'Nhân viên Quản lý khách hàng',
                'full_name' => 'Phạm Thị Dung',
                'phone_number' => '0900000004',
                'email' => 'qlkh01@gmail.com',
            ],
            [
                'username' => 'director01',
                'role_name' => 'Giám đốc',
                'full_name' => 'Hoàng Văn Em',
                'phone_number' => '0900000005',
                'email' => 'director01@gmail.com',
            ],
            [
                'username' => 'qlgs01',
                'role_name' => 'Nhân viên Quản lý Gia sư',
                'full_name' => 'Đỗ Thị Hoa',
                'phone_number' => '0900000006',
                'email' => 'qlgs01@gmail.com',
            ],
            [
                'username' => 'manager01',
                'role_name' => 'Quản lý',
                'full_name' => 'Nguyễn Thị Lan',
                'phone_number' => '0900000007',
                'email' => 'manager01@gmail.com',
            ],
        ];

        $roles = DB::table('roles')
            ->pluck('role_id', 'role_name');

        DB::transaction(function () use ($accounts, $roles) {
            foreach ($accounts as $account) {
                if (!isset($roles[$account['role_name']])) {
                    throw new \RuntimeException(
                        "Không tìm thấy vai trò: {$account['role_name']}"
                    );
                }

                // Tạo hoặc cập nhật thông tin cá nhân
                DB::table('people')->updateOrInsert(
                    [
                        'email' => $account['email'],
                    ],
                    [
                        'full_name' => $account['full_name'],
                        'phone_number' => $account['phone_number'],
                    ]
                );

                // Lấy person_id
                $personId = DB::table('people')
                    ->where('email', $account['email'])
                    ->value('person_id');

                // Tạo hoặc cập nhật tài khoản
                DB::table('accounts')->updateOrInsert(
                    [
                        'username' => $account['username'],
                    ],
                    [
                        'password' => Hash::make('123'),
                        'status' => 'active',
                        'role_id' => $roles[$account['role_name']],
                        'person_id' => $personId,
                    ]
                );
            }
        });
    }
}
