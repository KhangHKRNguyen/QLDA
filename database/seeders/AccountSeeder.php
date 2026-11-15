<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        // Danh sách thông tin cá nhân
        $people = [
            [
                'full_name' => 'Nguyễn Văn An',
                'phone_number' => '0900000001',
                'email' => 'tutor01@gmail.com',
            ],
            [
                'full_name' => 'Trần Thị Mai',
                'phone_number' => '0900000002',
                'email' => 'parent01@gmail.com',
            ],
            [
                'full_name' => 'Lê Văn Cường',
                'phone_number' => '0900000003',
                'email' => 'marketing01@gmail.com',
            ],
            [
                'full_name' => 'Phạm Thị Dung',
                'phone_number' => '0900000004',
                'email' => 'qlkh01@gmail.com',
            ],
            [
                'full_name' => 'Hoàng Văn Em',
                'phone_number' => '0900000005',
                'email' => 'director01@gmail.com',
            ],
            [
                'full_name' => 'Đỗ Thị Hoa',
                'phone_number' => '0900000006',
                'email' => 'qlgs01@gmail.com',
            ],
            [
                'full_name' => 'Nguyễn Thị Lan',
                'phone_number' => '0900000007',
                'email' => 'manager01@gmail.com',
            ],
        ];

        // Danh sách tài khoản cần tạo
        $accounts = [
            [
                'username' => 'tutor01',
                'role_name' => 'Gia sư',
                'email' => 'tutor01@gmail.com',
            ],
            [
                'username' => 'marketing01',
                'role_name' => 'Nhân viên Marketing',
                'email' => 'marketing01@gmail.com',
            ],
            [
                'username' => 'qlkh01',
                'role_name' => 'Nhân viên Quản lý khách hàng',
                'email' => 'qlkh01@gmail.com',
            ],
            [
                'username' => 'director01',
                'role_name' => 'Giám đốc',
                'email' => 'director01@gmail.com',
            ],
            [
                'username' => 'qlgs01',
                'role_name' => 'Nhân viên Quản lý Gia sư',
                'email' => 'qlgs01@gmail.com',
            ],
            [
                'username' => 'manager01',
                'role_name' => 'Quản lý',
                'email' => 'manager01@gmail.com',
            ],
        ];

        // Lấy danh sách vai trò
        $roles = DB::table('roles')
            ->pluck('role_id', 'role_name');

        DB::transaction(function () use ($people, $accounts, $roles) {

            // Tạo hoặc cập nhật thông tin cá nhân
            foreach ($people as $person) {
                DB::table('people')->updateOrInsert(
                    [
                        'email' => $person['email'],
                    ],
                    [
                        'full_name' => $person['full_name'],
                        'phone_number' => $person['phone_number'],
                    ]
                );
            }

            // Tạo hoặc cập nhật tài khoản
            foreach ($accounts as $account) {

                if (!isset($roles[$account['role_name']])) {
                    throw new \RuntimeException(
                        "Không tìm thấy vai trò: {$account['role_name']}"
                    );
                }

                // Lấy person_id theo email
                $personId = DB::table('people')
                    ->where('email', $account['email'])
                    ->value('person_id');

                // Kiểm tra thông tin cá nhân
                if (!$personId) {
                    throw new \RuntimeException(
                        "Không tìm thấy thông tin cá nhân: {$account['email']}"
                    );
                }

                DB::table('accounts')->updateOrInsert(
                    [
                        'username' => $account['username'],
                    ],
                    [
                        'password' => Hash::make('12345678'),
                        'status' => 'active',
                        'role_id' => $roles[$account['role_name']],
                        'person_id' => $personId,
                    ]
                );
            }
        });
    }
}
