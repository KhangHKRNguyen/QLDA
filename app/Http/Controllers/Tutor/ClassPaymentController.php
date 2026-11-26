<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\RequiresActiveTutor;
use App\Http\Controllers\Controller;
use App\Services\ClassPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassPaymentController extends Controller
{
    use RequiresActiveTutor;

    public function show(int $transactionId, ClassPaymentService $payments): View|RedirectResponse
    {
        $tutor = $this->activeTutor();
        $payment = $payments->findOwnedTransaction($transactionId, (int) $tutor->account_id);

        if ($payment->status === 'success') {
            return redirect()->route('tutor.classes.show', $payment->class_id)
                ->with('success', 'Nhận lớp thành công. Thông tin liên hệ khách hàng đã được mở.');
        }

        return view('tutor.class-payments.show', [
            'payment' => $payment,
            'secondsLeft' => $payment->reservation_expires_at
                ? max(0, (int) now()->diffInSeconds($payment->reservation_expires_at, false))
                : 0,
            'canSimulate' => app()->environment(['local', 'testing']),
        ]);
    }

    /** Chỉ dùng trong local/testing để kiểm thử HĐ5-HĐ8 khi chưa cấu hình cổng thanh toán thật. */
    public function simulateSuccess(int $transactionId, ClassPaymentService $payments): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $tutor = $this->activeTutor();
        $payment = $payments->findOwnedTransaction($transactionId, (int) $tutor->account_id);
        $status = $payments->recordResult(
            $payment->payment_transaction_id,
            'success',
            'LOCAL-' . $payment->payment_transaction_id,
            ['mode' => 'local_simulation', 'result' => 'success'],
            'mock',
        );

        if ($status === 'success') {
            return redirect()->route('tutor.classes.show', $payment->class_id)
                ->with('success', 'Nhận lớp thành công. Thông tin liên hệ khách hàng đã được mở.');
        }

        if ($status === 'late_success_pending_refund') {
            return redirect()->route('tutor.class-payments.show', $payment->payment_transaction_id)
                ->with('error', 'Giao dịch thành công nhưng đã hết thời gian giữ chỗ. Hệ thống không ghi nhận nhận lớp và đã đưa giao dịch vào trạng thái chờ đối soát/hoàn tiền.');
        }

        return redirect()->route('tutor.class-payments.show', $payment->payment_transaction_id)
            ->with('error', 'Giao dịch đã được xử lý trước đó.');
    }

    /** Chỉ dùng trong local/testing để kiểm thử nhánh HĐ7 - thanh toán thất bại. */
    public function simulateFailure(int $transactionId, ClassPaymentService $payments): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $tutor = $this->activeTutor();
        $payment = $payments->findOwnedTransaction($transactionId, (int) $tutor->account_id);
        $payments->recordResult(
            $payment->payment_transaction_id,
            'failed',
            'LOCAL-FAILED-' . $payment->payment_transaction_id,
            ['mode' => 'local_simulation', 'result' => 'failed'],
            'mock',
        );

        return redirect()->route('tutor.open-classes')
            ->with('error', 'Thanh toán thất bại. Lớp đã được giải phóng để Gia sư khác có thể nhận.');
    }

    /**
     * Webhook nhận payload đã được chuẩn hóa:
     * {"payment_transaction_id":123,"status":"success|failed","external_reference":"..."}
     * Chữ ký HMAC-SHA256 nằm ở header X-Class-Payment-Signature.
     */
    public function webhook(Request $request, string $provider, ClassPaymentService $payments): JsonResponse
    {
        $rawBody = $request->getContent();
        if (! $payments->isValidWebhookSignature($rawBody, $request->header('X-Class-Payment-Signature'))) {
            return response()->json(['message' => 'Chữ ký webhook không hợp lệ.'], 401);
        }

        $data = json_decode($rawBody, true);
        if (! is_array($data)) {
            return response()->json(['message' => 'Payload không hợp lệ.'], 422);
        }

        $validator = validator($data, [
            'payment_transaction_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:success,failed'],
            'external_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dữ liệu giao dịch không hợp lệ.', 'errors' => $validator->errors()], 422);
        }

        $status = $payments->recordResult(
            (int) $data['payment_transaction_id'],
            $data['status'],
            $data['external_reference'] ?? null,
            $data,
            $provider,
        );

        return response()->json([
            'message' => match ($status) {
                'success' => 'Thanh toán thành công, lớp đã được ghi nhận cho Gia sư.',
                'failed' => 'Thanh toán thất bại, lớp đã được giải phóng nếu còn thuộc giao dịch này.',
                'late_success_pending_refund' => 'Thanh toán thành công sau thời hạn giữ chỗ; giao dịch đang chờ đối soát/hoàn tiền, không ghi nhận nhận lớp.',
                default => 'Giao dịch đã được tiếp nhận trước đó.',
            },
            'status' => $status,
        ], $status === 'late_success_pending_refund' ? 202 : 200);
    }
}
