{{-- Gia sư - HĐ2-HĐ3: chi tiết lớp đang tuyển --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', "{$class->subject} – {$class->grade_level}")
@section('heading', "{$class->subject} – {$class->grade_level}")
@section('description', 'Thông tin lớp đang tuyển · Mã lớp #' . $class->class_id)
@section('breadcrumb')
    <a href="{{ route('tutor.open-classes') }}">Lớp đang tuyển</a> / <span class="font-medium text-foreground">#{{ $class->class_id }}</span>
@endsection
@section('actions')
    <a href="{{ route('tutor.open-classes') }}" class="btn btn-outline">← Danh sách lớp</a>
@endsection

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        @if (session('error'))
            <div class="notice notice-danger">{{ session('error') }}</div>
        @endif
        @if ($isLocked)
            <div class="notice notice-warning">
                @if ($isHeldByCurrentTutor)
                    Bạn đang giữ lớp này. Hãy tiếp tục màn hình thanh toán tương ứng để hoàn tất trong thời hạn 5 phút.
                @else
                    Lớp đang được Gia sư khác giữ chỗ chờ thanh toán. Bạn chưa thể nhận lớp này.
                @endif
            </div>
        @endif

        <section class="card">
            <header class="card-header"><h2 class="card-title">Thông tin lớp học</h2></header>
            <dl class="divide-y text-sm">
                @foreach ([
                    ['Môn học', $class->subject],
                    ['Khối lớp', $class->grade_level],
                    ['Hình thức học', $class->learning_format],
                    ['Lịch học', $class->schedule],
                    ['Số buổi / tuần', $class->sessions_per_week],
                    ['Học phí / buổi', number_format((float) $class->fee_per_session, 0, ',', '.') . ' đ'],
                    ['Địa điểm', $class->location],
                    ['Phí nhận lớp', number_format((float) $class->enrollment_fee, 0, ',', '.') . ' đ'],
                ] as [$label, $value])
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-muted-foreground">{{ $label }}</dt>
                        <dd class="text-right font-medium">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        @if ($class->tutorRequest?->notes)
            <section class="card">
                <header class="card-header"><h2 class="card-title">Yêu cầu thêm từ khách hàng</h2></header>
                <div class="card-body whitespace-pre-line text-sm">{{ $class->tutorRequest->notes }}</div>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        <section class="card">
            <header class="card-header"><h2 class="card-title">Nhận lớp</h2></header>
            <div class="card-body space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Trạng thái</span>
                    @if ($isLocked)
                        <x-status-badge tone="warning">Đang giữ chỗ</x-status-badge>
                    @else
                        <x-status-badge tone="success">Đang tuyển</x-status-badge>
                    @endif
                </div>
                <div class="rounded-lg bg-muted p-3">
                    <p class="text-sm text-muted-foreground">Phí nhận lớp</p>
                    <p class="mt-1 text-2xl font-bold">{{ number_format((float) $class->enrollment_fee, 0, ',', '.') }} đ</p>
                </div>
                @if (! $isLocked && $class->status === 'pending')
                    <button type="button" class="btn btn-primary w-full"
                        data-confirm
                        data-confirm-title="Xác nhận nhận lớp"
                        data-confirm-message="Hệ thống sẽ giữ lớp trong 5 phút. Bạn cần hoàn tất thanh toán trong thời gian này."
                        data-confirm-action="{{ route('tutor.open-classes.claim', $class->class_id) }}"
                        data-confirm-label="Tiếp tục thanh toán">
                        Nhận lớp
                    </button>
                @else
                    <button type="button" class="btn btn-outline w-full" disabled>Hiện chưa thể nhận lớp</button>
                @endif
                <p class="text-xs text-muted-foreground">Thông tin liên hệ khách hàng chỉ được mở sau khi thanh toán thành công trong thời hạn giữ chỗ.</p>
            </div>
        </section>
    </aside>
</div>
@endsection