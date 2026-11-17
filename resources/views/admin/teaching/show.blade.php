{{-- NV QLKH — chi tiết lớp đang dạy: dạy thử (HĐ4), hoàn thành (HĐ6), phiếu hoàn phí (HĐ8) --}}
@php
    use App\Services\RefundService;
    use App\Services\TeachingService as T;
    $id = $class->class_id;
    $isTrial = $class->status === 'teaching' && $class->teaching_stage === 'trial';
@endphp
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', "Lớp #{$id}")
@section('heading', "{$class->subject} – {$class->grade_level}")
@section('description', "Lớp #{$id} · {$class->learning_format}")
@section('breadcrumb')
    <a href="{{ route('admin.teaching.index') }}">Lớp đang dạy</a> / <span class="font-medium text-foreground">#{{ $id }}</span>
@endsection
@section('actions')
    <a href="{{ route('admin.teaching.index') }}" class="btn btn-outline"><x-icon name="back" /> Danh sách</a>
    @if (! $blocker)
        @can('refund.create')
            <a href="{{ route('admin.teaching.refund.create', $id) }}" class="btn btn-outline">Tạo phiếu hoàn phí</a>
        @endcan
    @endif
    @if ($class->status === 'teaching' && ! $isTrial)
        <button type="button" class="btn btn-primary"
            data-confirm data-confirm-title="Ghi nhận lớp hoàn thành"
            data-confirm-message="Lớp kết thúc theo đúng thỏa thuận. Sau khi hoàn thành, lớp không thể yêu cầu hoàn phí."
            data-confirm-action="{{ route('admin.teaching.complete', $id) }}" data-confirm-label="Hoàn thành">
            <x-icon name="check" /> Ghi nhận hoàn thành
        </button>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    {{-- ===== HĐ4: phụ huynh đánh giá buổi dạy thử ===== --}}
    @if ($isTrial)
        <section class="card border-primary/40">
            <header class="card-header">
                <div>
                    <h2 class="card-title">Kết quả buổi dạy thử</h2>
                    <p class="card-desc">Liên hệ phụ huynh {{ $customer?->full_name }} ({{ $customer?->phone_number }}) để ghi nhận quyết định sau buổi dạy thử miễn phí.</p>
                </div>
            </header>
            <div class="card-body flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary"
                    data-confirm data-confirm-title="Phụ huynh đồng ý học chính thức"
                    data-confirm-message="Lớp chuyển sang Dạy chính thức, gia sư nhận được thông báo."
                    data-confirm-action="{{ route('admin.teaching.trial', $id) }}?agreed=1" data-confirm-label="Ghi nhận">
                    <x-icon name="check" /> Đồng ý học chính thức
                </button>
                <button type="button" class="btn btn-outline"
                    data-confirm data-confirm-tone="danger" data-confirm-title="Phụ huynh báo nghỉ"
                    data-confirm-message="Ghi nhận phụ huynh không tiếp tục sau buổi dạy thử, sau đó tạo phiếu yêu cầu hoàn phí cho gia sư."
                    data-confirm-action="{{ route('admin.teaching.trial', $id) }}?agreed=0" data-confirm-label="Ghi nhận và tạo phiếu">
                    <x-icon name="x" /> Phụ huynh báo nghỉ
                </button>
            </div>
        </section>
    @elseif ($blocker && $class->status === 'teaching')
        <p class="notice notice-info">{{ $blocker }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Tình trạng</h2>
                    <x-status-badge :tone="$statusTone">{{ $statusLabel }}</x-status-badge>
                </header>
                <dl class="divide-y text-sm">
                    @foreach ([
                        ['Giai đoạn', $stageLabel],
                        ['Ngày nhận lớp', $class->accepted_at ? \Illuminate\Support\Carbon::parse($class->accepted_at)->format('d/m/Y') : null],
                        ['Bảo hành đến', $warrantyEnd ? $warrantyEnd->format('d/m/Y') . ($warrantyLeft > 0 ? " (còn {$warrantyLeft} ngày)" : ' (đã hết)') : null],
                        ['Kết quả dạy thử ghi nhận lúc', $class->trial_decided_at ? \Illuminate\Support\Carbon::parse($class->trial_decided_at)->format('d/m/Y H:i') : null],
                        ['Phí nhận lớp', $payment ? RefundService::money($payment->amount) : 'Chưa thanh toán'],
                        ['Học phí', RefundService::money($class->fee_per_session) . "/buổi · {$class->sessions_per_week} buổi/tuần"],
                        ['Địa điểm', $class->location],
                    ] as [$k, $v])
                        <div class="flex justify-between gap-4 px-5 py-2.5">
                            <dt class="shrink-0 text-muted-foreground">{{ $k }}</dt>
                            <dd class="text-right font-medium">{{ $v ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Yêu cầu hoàn phí của lớp</h2></header>
                @forelse ($refunds as $r)
                    <a href="{{ route('admin.teaching.refunds.show', $r->refund_request_id) }}" class="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3 text-sm first-of-type:border-t-0 hover:bg-muted/50">
                        <span>
                            <span class="font-medium">#{{ $r->refund_request_id }}</span> · {{ $r->reasonLabel() ? \Illuminate\Support\Str::limit($r->reasonLabel(), 60) : '' }}
                            <span class="block text-xs text-muted-foreground">{{ \App\Models\RefundRequest::SOURCES[$r->source_type] ?? '' }} · {{ $r->received_at->format('d/m/Y') }}</span>
                        </span>
                        <x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge>
                    </a>
                @empty
                    <p class="card-body text-sm text-muted-foreground">Chưa có yêu cầu hoàn phí.</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6">
            @foreach (['Gia sư' => $tutor?->person, 'Phụ huynh' => $customer] as $title => $p)
                <section class="card">
                    <header class="card-header"><h2 class="card-title">{{ $title }}</h2></header>
                    <div class="card-body space-y-1.5 text-sm">
                        <p class="font-semibold">{{ $p?->full_name ?? '—' }}</p>
                        @if ($p?->phone_number)<p class="flex items-center gap-2"><x-icon name="phone" class="text-muted-foreground" /> {{ $p->phone_number }}</p>@endif
                        @if ($p?->email)<p class="flex items-center gap-2"><x-icon name="mail" class="text-muted-foreground" /> {{ $p->email }}</p>@endif
                    </div>
                </section>
            @endforeach
            <section class="card">
                <header class="card-header"><h2 class="card-title">Lịch sử lớp</h2></header>
                <ol class="card-body space-y-3">
                    @forelse ($logs as $log)
                        <li class="text-sm">
                            <p class="font-medium">{{ $log->action }}</p>
                            <p class="text-xs text-muted-foreground">{{ $log->occurred_at?->format('d/m/Y H:i') }} · {{ $log->account?->username }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-muted-foreground">Chưa có thao tác.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</div>
@endsection
