<?php

return [
    // mock chỉ dùng cho môi trường local/testing. Chưa phải kết nối cổng thanh toán thật.
    'driver' => env('CLASS_PAYMENT_DRIVER', 'mock'),

    // Bí mật webhook do adapter/cổng thanh toán chia sẻ. Không commit giá trị bí mật lên Git.
    'webhook_secret' => env('CLASS_PAYMENT_WEBHOOK_SECRET'),
];
