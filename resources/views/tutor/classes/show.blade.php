{{-- Gia sư — chi tiết lớp đã nhận: thông tin lớp, liên hệ phụ huynh, bảo hành, yêu cầu hoàn phí --}}
@php
    use App\Services\RefundService;
    use App\Services\TeachingService as T;
@endphp
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', "{$class->subject} – {$class->grade_level}")
@section('heading', "{$class->subject} – {$class->grade_level}")
@section('description', 'Lớp #' . $class->class_id . ' · nhận ngày ' . ($class->accepted_at ? \Illuminate\Support\Carbon::parse($class->accepted_at)->format('d/m/Y') : '—'))
@section('breadcrumb')
    <a href="{{ route('tutor.classes.index') }}">Lớp của tôi</a> / <span class="font-medium text-foreground">#{{ $class->class_id }}</span>
@endsection
@section('actions')
    <a href="{{ route('tutor.classes.index') }}" class="btn btn-outline"><x-icon name="back" /> Danh sách</a>
    {{-- FR-HP-01: chỉ hiện khi đủ điều kiện --}}
    @if (! $blocker)
        @can('refund.create')
            <a href="{{ route('tutor.refunds.create', $class->class_id) }}" class="btn btn-primary">Yêu cầu hoàn phí</a>
        @endcan
    @endif
@endsection

@section('content')
<div class="space-y-6">
    @if ($class->status === 'teaching' && $class->teaching_stage === 'trial')
        <p class="notice notice-info">Hãy liên hệ phụ huynh để hẹn <strong>buổi dạy thử miễn phí</strong>. Sau buổi dạy thử, Trung tâm sẽ ghi nhận phụ huynh đồng ý học chính thức hay không.</p>
    @elseif ($class->status === 'refund_processing')
        <p class="notice notice-warning">Lớp đang trong quá trình xử lý hoàn phí. Theo dõi kết quả ở mục <a href="{{ route('tutor.refunds.index') }}" class="font-medium underline">Yêu cầu hoàn phí</a>.</p>
    @elseif ($blocker && $class->status === 'teaching')
        <p class="notice notice-info">{{ $blocker }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Tình trạng lớp</h2>
                    <x-status-badge :tone="$statusTone">{{ $statusLabel }}</x-status-badge>
                </header>
                <div class="card-body space-y-4">
                    {{-- Các bước của quy trình giảng dạy --}}
                    @php
                        $done = [
                            'nhan'      => true,
                            'day-thu'   => in_array($class->teaching_stage, ['trial', 'official'], true),
                            'chinh-thuc'=> $class->teaching_stage === 'official',
                            'hoan-thanh'=> $class->status === 'completed',
                        ];
                        $steps = ['nhan' => 'Nhận lớp', 'day-thu' => 'Dạy thử', 'chinh-thuc' => 'Dạy chính thức', 'hoan-thanh' => 'Hoàn thành'];
                    @endphp
                    <ol class="grid grid-cols-4 gap-2 text-center text-xs">
                        @foreach ($steps as $key => $label)
                            <li class="flex flex-col items-center gap-1.5">
                                <span @class(['flex size-7 items-center justify-center rounded-full text-xs font-semibold',
                                    'bg-primary text-primary-foreground' => $done[$key], 'bg-muted text-muted-foreground' => ! $done[$key]])>
                                    @if ($done[$key])<x-icon name="check" class="size-3.5" />@else{{ $loop->iteration }}@endif
                                </span>
                                <span @class(['font-medium' => $done[$key], 'text-muted-foreground' => ! $done[$key]])>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>

                    @if ($warrantyEnd)
                        <div class="rounded-lg bg-muted p-3 text-sm">
                            <span class="text-muted-foreground">Bảo hành đến</span>
                            <strong>{{ $warrantyEnd->format('d/m/Y') }}</strong>
                            · {{ $warrantyLeft > 0 ? "còn {$warrantyLeft} ngày" : 'đã hết hạn' }}
                        </div>
                    @endif
                </div>
            </section>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Thông tin lớp</h2></header>
                <dl class="divide-y text-sm">
                    @foreach ([
                        ['Hình thức', $class->learning_format],
                        ['Số buổi / tuần', $class->sessions_per_week],
                        ['Học phí / buổi', RefundService::money($class->fee_per_session)],
                        ['Lịch học', $class->schedule],
                        ['Địa điểm', $class->location],
                        ['Phí nhận lớp đã trả', $payment ? RefundService::money($payment->amount) . ' (' . $payment->transaction_at?->format('d/m/Y') . ')' : null],
                    ] as [$k, $v])
                        <div class="flex justify-between gap-4 px-5 py-2.5">
                            <dt class="shrink-0 text-muted-foreground">{{ $k }}</dt>
                            <dd class="text-right font-medium">{{ $v ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card">
                <header class="card-header"><h2 class="card-title">Liên hệ phụ huynh</h2></header>
                @if ($customer)
                    <div class="card-body space-y-2 text-sm">
                        <p class="font-semibold">{{ $customer->full_name }}</p>
                        <p class="flex items-center gap-2"><x-icon name="phone" class="text-muted-foreground" /> <a href="tel:{{ $customer->phone_number }}" class="text-primary hover:underline">{{ $customer->phone_number }}</a></p>
                        @if ($customer->email)<p class="flex items-center gap-2"><x-icon name="mail" class="text-muted-foreground" /> {{ $customer->email }}</p>@endif
                        @if ($customer->address || $customer->district)<p class="flex items-center gap-2"><x-icon name="pin" class="text-muted-foreground" /> {{ collect([$customer->address, $customer->district])->filter()->implode(', ') }}</p>@endif
                    </div>
                @else
                    <p class="card-body text-sm text-muted-foreground">Thông tin liên hệ được mở sau khi thanh toán phí nhận lớp thành công.</p>
                @endif
            </section>

            <section class="card">
                <header class="card-header"><h2 class="card-title">Yêu cầu hoàn phí</h2></header>
                @forelse ($refunds as $r)
                    <a href="{{ route('tutor.refunds.show', $r->refund_request_id) }}" class="flex items-center justify-between gap-3 border-t px-5 py-3 text-sm first-of-type:border-t-0 hover:bg-muted/50">
                        <span>#{{ $r->refund_request_id }} · {{ $r->received_at->format('d/m/Y') }}</span>
                        <x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge>
                    </a>
                @empty
                    <p class="card-body text-sm text-muted-foreground">Chưa có yêu cầu nào cho lớp này.</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
