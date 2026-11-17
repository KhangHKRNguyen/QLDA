<?php


return [
    'contact' => [
        'address' => 'Số 136 Xuân Thủy, Cầu Giấy, Hà Nội',
        'phone'   => '0243 999 888',
        'email'   => 'lienhe@giasudohang.example',
        'hours'   => 'Thứ 2 – Thứ 7: 8:00 – 20:00',
    ],

    'tagline' => 'Kết nối phụ huynh với gia sư sư phạm đã được xác minh hồ sơ, hỗ trợ trong suốt quá trình học.',

    // Menu header trang khách hàng
    'public_nav' => [
        ['label' => 'Trang chủ',   'path' => '/'],
        ['label' => 'Giới thiệu',  'path' => '/gioi-thieu'],
        ['label' => 'Tìm gia sư',  'path' => '/tim-gia-su'],
        ['label' => 'Bài viết',    'path' => '/bai-viet'],
        ['label' => 'Liên hệ',     'path' => '/lien-he'],
    ],

    // Menu sidebar theo giao diện
    'menu' => [
        'admin' => [
            ['label' => 'Tổng quan',          'path' => '/dashboard', 'active' => ['admin/gia-su/tong-quan']],
            ['label' => 'Quản lý tài khoản',  'path' => '/admin/tai-khoan', 'roles' => ['Quản lý']],
            ['label' => 'Tài khoản gia sư',   'path' => '/admin/gia-su',    'roles' => ['Nhân viên Quản lý Gia sư']],
            ['label' => 'Yêu cầu khách hàng', 'path' => '/admin/yeu-cau',   'roles' => ['Nhân viên Quản lý khách hàng']],
            ['label' => 'Lớp học',            'path' => '/admin/lop-hoc',   'roles' => ['Nhân viên Quản lý khách hàng', 'Quản lý']],
            ['label' => 'Lớp đang dạy',       'path' => '/admin/giang-day', 'roles' => ['Nhân viên Quản lý khách hàng']],
            ['label' => 'Phiếu hoàn phí',     'path' => '/admin/giang-day/hoan-phi', 'roles' => ['Nhân viên Quản lý khách hàng']],
            ['label' => 'Yêu cầu hoàn phí',   'path' => '/admin/hoan-phi',  'roles' => ['Quản lý']], // xử lý hoàn phí: chỉ Quản lý (quyền refund.view)
            ['label' => 'Hoàn phí quá hạn',   'path' => '/admin/hoan-phi-qua-han', 'roles' => ['Giám đốc']],
            ['label' => 'Bài viết',           'path' => '/admin/bai-viet',  'roles' => ['Nhân viên Marketing']],
            ['label' => 'Báo cáo',            'path' => '/admin/bao-cao',   'roles' => ['Giám đốc']],
            ['label' => 'Thông báo',          'path' => '/admin/gia-su/thong-bao', 'roles' => ['Nhân viên Quản lý Gia sư']],
        ],
        'tutor' => [
            ['label' => 'Tổng quan',           'path' => '/gia-su'],
            ['label' => 'Lớp đang tuyển',      'path' => '/gia-su/lop-dang-tuyen'],
            ['label' => 'Lớp của tôi',         'path' => '/gia-su/lop-cua-toi'],
            ['label' => 'Yêu cầu hoàn phí',    'path' => '/gia-su/hoan-phi'],
            ['label' => 'Lịch sử thanh toán',  'path' => '/gia-su/thanh-toan'],
            ['label' => 'Hồ sơ cá nhân',       'path' => '/gia-su/ho-so'],
            ['label' => 'Thông báo',           'path' => '/gia-su/thong-bao'],
        ],
    ],

    'panel_names' => ['admin' => 'Quản trị Trung tâm', 'tutor' => 'Cổng gia sư'],
];