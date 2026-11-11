<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Module: account
            [
                'permission_name' => 'account.view',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.search',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.create',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.delete',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.lock_temporary',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.lock_permanent',
                'module' => 'account',
            ],
            [
                'permission_name' => 'account.unlock',
                'module' => 'account',
            ],

            // Module: verification
            [
                'permission_name' => 'verification.view',
                'module' => 'verification',
            ],
            [
                'permission_name' => 'verification.approve',
                'module' => 'verification',
            ],
            [
                'permission_name' => 'verification.reject',
                'module' => 'verification',
            ],
            [
                'permission_name' => 'verification.request_supplement',
                'module' => 'verification',
            ],

            // Module: tutor_request
            [
                'permission_name' => 'tutor_request.view',
                'module' => 'tutor_request',
            ],
            [
                'permission_name' => 'tutor_request.create',
                'module' => 'tutor_request',
            ],
            [
                'permission_name' => 'tutor_request.update',
                'module' => 'tutor_request',
            ],
            [
                'permission_name' => 'tutor_request.delete',
                'module' => 'tutor_request',
            ],

            // Module: class
            [
                'permission_name' => 'class.view',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.create',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.update',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.delete',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.hide',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.show',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.update_status',
                'module' => 'class',
            ],
            [
                'permission_name' => 'class.register',
                'module' => 'class',
            ],

            // Module: post
            [
                'permission_name' => 'post.view',
                'module' => 'post',
            ],
            [
                'permission_name' => 'post.create',
                'module' => 'post',
            ],
            [
                'permission_name' => 'post.update',
                'module' => 'post',
            ],
            [
                'permission_name' => 'post.delete',
                'module' => 'post',
            ],
            [
                'permission_name' => 'post.hide',
                'module' => 'post',
            ],
            [
                'permission_name' => 'post.show',
                'module' => 'post',
            ],

            // Module: payment
            [
                'permission_name' => 'payment.view',
                'module' => 'payment',
            ],
            [
                'permission_name' => 'payment.pay_class_fee',
                'module' => 'payment',
            ],

            // Module: refund
            [
                'permission_name' => 'refund.view',
                'module' => 'refund',
            ],
            [
                'permission_name' => 'refund.create',
                'module' => 'refund',
            ],
            [
                'permission_name' => 'refund.update',
                'module' => 'refund',
            ],
            [
                'permission_name' => 'refund.approve',
                'module' => 'refund',
            ],
            [
                'permission_name' => 'refund.reject',
                'module' => 'refund',
            ],
            [
                'permission_name' => 'refund.execute',
                'module' => 'refund',
            ],

            // Module: report
            [
                'permission_name' => 'report.view',
                'module' => 'report',
            ],
            [
                'permission_name' => 'report.export',
                'module' => 'report',
            ],
        ];

       foreach ($permissions as $permission) {
    DB::table('permissions')->updateOrInsert(
        [
            'permission_name' => $permission['permission_name'],
        ],
        [
            'module' => $permission['module'],
        ]
    );
}
}
}