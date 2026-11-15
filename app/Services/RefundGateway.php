<?php

namespace App\Services;

use App\Models\RefundTransaction;

/**
 * Cổng thanh toán — phần hoàn tiền về phương thức ban đầu.
 * Hiện là bản GIẢ LẬP vì nhóm chưa tích hợp cổng thật; khi tích hợp chỉ cần thay thân hàm refund().
 * Kết quả giả lập đặt trong .env: REFUND_GATEWAY_RESULT=success | failed | pending (mặc định success).
 */
class RefundGateway
{
    public const SUCCESS = 'success';
    public const FAILED  = 'failed';
    public const PENDING = 'pending'; // cổng chưa trả kết quả

    public function refund(RefundTransaction $transaction): string
    {
        $result = config('services.refund_gateway.result', self::SUCCESS);

        return in_array($result, [self::SUCCESS, self::FAILED, self::PENDING], true) ? $result : self::SUCCESS;
    }
}
