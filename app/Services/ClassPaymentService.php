<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\TutorClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClassPaymentService
{
    public function findOwnedTransaction(int $transactionId, int $accountId): PaymentTransaction
    {
        return PaymentTransaction::query()
            ->with(['tutorClass.tutorRequest'])
            ->where('account_id', $accountId)
            ->findOrFail($transactionId);
    }

    /**
     * Xử lý kết quả đã được xác minh từ cổng thanh toán.
     * Trả về: success | failed | late_success_pending_refund.
     */
    public function recordResult(
        int $transactionId,
        string $result,
        ?string $externalReference = null,
        ?array $payload = null,
        ?string $provider = null,
    ): string {
        if (! in_array($result, ['success', 'failed'], true)) {
            throw new \InvalidArgumentException('Kết quả thanh toán không hợp lệ.');
        }

        return DB::transaction(function () use ($transactionId, $result, $externalReference, $payload, $provider) {
            /** @var PaymentTransaction $payment */
            $payment = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transactionId);

            // Callback có thể được gửi lại nhiều lần; chỉ xử lý một lần.
            if ($payment->status !== PaymentTransaction::STATUS_PENDING) {
                return $payment->status;
            }

            if ($provider && $payment->payment_provider && $payment->payment_provider !== $provider) {
                throw new \DomainException('Nhà cung cấp thanh toán không khớp với giao dịch.');
            }

            /** @var TutorClass $class */
            $class = TutorClass::query()
                ->lockForUpdate()
                ->findOrFail($payment->class_id);

            if ($externalReference) {
                $payment->external_reference = Str::limit($externalReference, 255, '');
            }
            if ($provider) {
                $payment->payment_provider = Str::limit($provider, 50, '');
            }
            if ($payload !== null) {
                $payment->gateway_payload = $payload;
            }

            $sameReservation = $class->status === TutorClass::STATUS_RESERVED
                && (int) $class->locked_by_account_id === (int) $payment->account_id
                && $class->lock_expires_at !== null
                && $payment->reservation_expires_at !== null
                && $class->lock_expires_at->equalTo($payment->reservation_expires_at);

            if ($result === 'failed') {
                $payment->status = PaymentTransaction::STATUS_FAILED;
                $payment->save();

                if ($sameReservation) {
                    // Thất bại được xác nhận thì giải phóng ngay, không phải chờ hết 5 phút.
                    $class->update([
                        'status' => TutorClass::STATUS_OPEN,
                        'locked_at' => null,
                        'locked_by_account_id' => null,
                        'lock_expires_at' => null,
                    ]);
                }

                return PaymentTransaction::STATUS_FAILED;
            }

            $withinReservation = $sameReservation
                && now()->lt($payment->reservation_expires_at);

            $payment->paid_at = now();

            if ($withinReservation) {
                $payment->status = PaymentTransaction::STATUS_SUCCESS;
                $payment->save();

                // account_id vẫn là trường Gia sư phụ trách lớp theo TeachingController hiện tại.
                $class->update([
                    'status' => TutorClass::STATUS_TEACHING,
                    'account_id' => $payment->account_id,
                    'accepted_at' => now(),
                    'teaching_stage' => 'trial',
                    'locked_at' => null,
                    'locked_by_account_id' => null,
                    'lock_expires_at' => null,
                ]);

                return PaymentTransaction::STATUS_SUCCESS;
            }

            // Thành công đến sau hạn: tuyệt đối không gán lớp cho Gia sư này.
            // Đưa giao dịch vào trạng thái đối soát/hoàn tiền; cần gateway refund thật để tự động hoàn.
            $payment->status = PaymentTransaction::STATUS_LATE_SUCCESS;
            $payment->save();

            // Chỉ mở khóa nếu lớp vẫn đang được giữ bởi đúng giao dịch cũ.
            if ($sameReservation) {
                $class->update([
                    'status' => TutorClass::STATUS_OPEN,
                    'locked_at' => null,
                    'locked_by_account_id' => null,
                    'lock_expires_at' => null,
                ]);
            }

            return PaymentTransaction::STATUS_LATE_SUCCESS;
        }, 3);
    }

    /** Chuẩn kiểm tra webhook dùng HMAC-SHA256 trên raw body. Adapter của cổng thật phải gửi chữ ký tương ứng. */
    public function isValidWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('class_payment.webhook_secret');
        if ($secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
