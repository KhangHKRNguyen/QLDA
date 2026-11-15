<?php

/*
 * Thông tin chung + menu của website. Sửa tại đây, không cần sửa blade.
 * 'path' dùng đường dẫn tương đối; 'roles' để trống = ai cũng thấy.
 * Tên role bên dưới chỉ là gợi ý — đổi cho khớp bảng VaiTro của nhóm.
 */
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
            ['label' => 'Tổng quan',         'path' => '/admin'],
            // roles phải khớp đúng roles.role_name trong DB (xem RoleSeeder)
            ['label' => 'Yêu cầu khách hàng', 'path' => '/admin/yeu-cau',  'roles' => ['Nhân viên Quản lý khách hàng']],
            ['label' => 'Lớp học',           'path' => '/admin/lop-hoc',  'roles' => ['Nhân viên Quản lý khách hàng', 'Quản lý']],
            ['label' => 'Tài khoản gia sư',  'path' => '/admin/gia-su',   'roles' => ['Nhân viên Quản lý Gia sư']],
            ['label' => 'Quản lý tài khoản', 'path' => '/admin/tai-khoan', 'roles' => ['Quản lý']], // Chương 2: chỉ vai trò Quản lý
            ['label' => 'Yêu cầu hoàn phí',  'path' => '/admin/hoan-phi', 'roles' => ['Quản lý']], // xử lý hoàn phí: chỉ Quản lý (quyền refund.view)
            ['label' => 'Bài viết',          'path' => '/admin/bai-viet', 'roles' => ['Nhân viên Marketing']],
            ['label' => 'Báo cáo',           'path' => '/admin/bao-cao',  'roles' => ['Giám đốc']],
        ],
        'tutor' => [
            ['label' => 'Tổng quan',       'path' => '/gia-su'],
            ['label' => 'Lớp đang tuyển',  'path' => '/gia-su/lop-dang-tuyen'],
            ['label' => 'Lớp của tôi',     'path' => '/gia-su/lop-cua-toi'],
            ['label' => 'Hoàn phí',        'path' => '/gia-su/hoan-phi'],
            ['label' => 'Hồ sơ cá nhân',   'path' => '/gia-su/ho-so'],
        ],
    ],

    'panel_names' => ['admin' => 'Quản trị Trung tâm', 'tutor' => 'Cổng gia sư'],
];
