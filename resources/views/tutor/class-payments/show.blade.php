{{-- Gia sư - HĐ4-HĐ7: thanh toán phí nhận lớp --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Thanh toán phí nhận lớp')
@section('heading', 'Thanh toán phí nhận lớp')
@section('description', 'Hoàn tất thanh toán trong thời hạn giữ chỗ 5 phút.')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    @if (session('error'))
        <div class="notice notice-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="notice notice-success">{{ session('success') }}</div>
    @endif

    <section class="card">
        <header class="card-header">
            <div>
                <h2 class="card-title">Thông tin giao dịch #{{ $payment->payment_transaction_id }}</h2>
                <p class="card-desc">Lớp #{{ $payment->class_id }} · {{ $payment->tutorClass?->subject }} – {{ $payment->tutorClass?->grade_level }}</p>
            </div>
            @if ($payment->status === 'pending')
                <x-status-badge tone="warning">Chờ thanh toán</x-status-badge>
            @elseif ($payment->status === 'failed')
                <x-status-badge tone="danger">Thất bại</x-status-badge>
            @elseif ($payment->status === 'late_success_pending_refund')
                <x-status-badge tone="warning">Chờ đối soát/hoàn tiền</x-status-badge>
            @endif
        </header>
        <div class="card-body space-y-5">
            <div class="flex items-center justify-between gap-4">
                <span class="text-sm text-muted-foreground">Phí nhận lớp</span>
                <strong class="text-2xl">{{ number_format((float) $payment->amount, 0, ',', '.') }} đ</strong>
            </div>

            @if ($payment->status === 'pending')
                <div class="rounded-xl bg-muted p-5 text-center">
                    <p class="text-sm text-muted-foreground">Thời gian giữ chỗ còn lại</p>
                    <p id="reservation-countdown" class="mt-1 text-4xl font-bold tabular-nums">--:--</p>
                    <p id="reservation-countdown-message" class="mt-2 text-xs text-muted-foreground">Lớp sẽ được mở lại khi hết thời hạn nếu chưa xác nhận thanh toán thành công.</p>
                </div>

                @if ($canSimulate)
                    <div class="notice notice-info">
                        Chế độ kiểm thử local: chưa kết nối cổng thanh toán thật. Hai nút bên dưới chỉ mô phỏng kết quả để kiểm tra quy trình HĐ5–HĐ9.
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('tutor.class-payments.simulate-success', $payment->payment_transaction_id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-full">Mô phỏng thanh toán thành công</button>
                        </form>
                        <form method="POST" action="{{ route('tutor.class-payments.simulate-failure', $payment->payment_transaction_id) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline w-full">Mô phỏng thanh toán thất bại</button>
                        </form>
                    </div>
                @else
                    <div class="notice notice-warning">
                        Cổng thanh toán chưa được cấu hình cho môi trường này. Không nhập thông tin thẻ hoặc chuyển tiền ngoài hệ thống.
                    </div>
                @endif
            @elseif ($payment->status === 'failed')
                <div class="notice notice-danger">Giao dịch thanh toán thất bại. Lớp đã được giải phóng nếu vẫn thuộc lượt giữ chỗ này.</div>
                <a href="{{ route('tutor.open-classes') }}" class="btn btn-primary">Quay lại lớp đang tuyển</a>
            @elseif ($payment->status === 'late_success_pending_refund')
                <div class="notice notice-warning">
                    Cổng thanh toán báo thành công sau khi thời hạn giữ chỗ kết thúc. Hệ thống không ghi nhận bạn nhận lớp để tránh nhận trùng. Giao dịch đang ở trạng thái chờ Trung tâm đối soát và xử lý hoàn tiền.
                </div>
                <a href="{{ route('tutor.open-classes') }}" class="btn btn-outline">Quay lại lớp đang tuyển</a>
            @endif
        </div>
    </section>
</div>

@if ($payment->status === 'pending')
<script>
(() => {
    const output = document.getElementById('reservation-countdown');
    const message = document.getElementById('reservation-countdown-message');
    const expiresAt = {{ $payment->reservation_expires_at?->timestamp ? $payment->reservation_expires_at->timestamp * 1000 : 'null' }};
    if (!output || !expiresAt) return;

    const tick = () => {
        const seconds = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
        const remain = String(seconds % 60).padStart(2, '0');
        output.textContent = `${minutes}:${remain}`;
        if (seconds <= 0) {
            message.textContent = 'Đã hết thời gian giữ chỗ. Tải lại trang để cập nhật trạng thái giao dịch.';
            document.querySelectorAll('form button[type="submit"]').forEach(button => button.disabled = true);
        }
    };
    tick();
    window.setInterval(tick, 1000);
})();
</script>
@endif
@endsection
