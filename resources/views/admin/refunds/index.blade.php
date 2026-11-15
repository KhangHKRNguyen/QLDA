{{-- Trung tâm xử lý hoàn phí — danh sách yêu cầu (PTTKHT 2.2.9.2, bước 1–2) --}}
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Yêu cầu hoàn phí')
@section('heading', 'Trung tâm xử lý hoàn phí')
@section('description', 'Xác minh yêu cầu, quyết định mức hoàn và theo dõi hoàn tiền. Thời hạn xử lý ' . \App\Models\RefundRequest::PROCESSING_DAYS . ' ngày kể từ ngày tiếp nhận.')
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> / <span class="font-medium text-foreground">Hoàn phí</span>
@endsection

@section('content')
@php
    use App\Services\RefundService;
    $current = request('status');
    $cards = [
        ['label' => 'Cần xử lý',            'value' => $stats['awaiting'],      'status' => 'can-xu-ly',     'color' => 'text-primary'],
        ['label' => 'Quá hạn',              'value' => $stats['overdue'],       'status' => 'overdue',       'color' => 'text-danger'],
        ['label' => 'Chờ người gửi xác nhận', 'value' => $stats['decided'],     'status' => 'decided',       'color' => 'text-warning'],
        ['label' => 'Hoàn tiền thất bại',   'value' => $stats['refund_failed'], 'status' => 'refund_failed', 'color' => 'text-danger'],
        ['label' => 'Đã hoàn thành công',   'value' => RefundService::money($stats['refunded']), 'status' => 'refunded', 'color' => 'text-success'],
    ];
@endphp
<div class="space-y-6">
    {{-- ===== Thống kê nhanh (bấm để lọc) ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($cards as $c)
            @php $active = $current === $c['status'] && ! request('q'); @endphp
            <a href="{{ route('admin.refunds.index', ['status' => $c['status']]) }}"
               @class(['card p-4 transition-colors hover:border-primary/40', 'border-primary ring-3 ring-primary/15' => $active])
               @if ($active) aria-current="true" @endif>
                <p class="text-xs font-medium text-muted-foreground">{{ $c['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $c['color'] }}">{{ $c['value'] }}</p>
            </a>
        @endforeach
    </div>

    <section class="card">
        {{-- ===== Bộ lọc ===== --}}
        <form method="GET" action="{{ route('admin.refunds.index') }}" class="grid gap-3 border-b p-4 md:grid-cols-[1fr_16rem_auto]">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground" />
                <input type="search" name="q" value="{{ request('q') }}" class="input pl-9"
                       placeholder="Tìm theo mã yêu cầu (VD: 3), tên gia sư, môn học..." aria-label="Tìm yêu cầu hoàn phí">
            </div>
            <select name="status" class="input" aria-label="Lọc theo trạng thái" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="can-xu-ly" @selected($current === 'can-xu-ly')>— Cần xử lý (chờ quyết định) —</option>
                @foreach ($statuses as $key => [$label])
                    <option value="{{ $key }}" @selected($current === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary h-10"><x-icon name="search" /> Tìm</button>
                @if (request('q') || $current)
                    <a href="{{ route('admin.refunds.index') }}" class="btn btn-ghost h-10">Xóa lọc</a>
                @endif
            </div>
        </form>

        {{-- ===== Bảng yêu cầu ===== --}}
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Gia sư</th>
                        <th>Lớp</th>
                        <th>Lý do</th>
                        <th class="text-right">Mức hoàn</th>
                        <th>Trạng thái</th>
                        <th>Tiếp nhận</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refunds as $r)
                        @php $left = $r->daysLeft(); @endphp
                        <tr @class(['bg-danger-soft/40' => $r->status === 'overdue'])>
                            <td class="font-medium whitespace-nowrap text-muted-foreground">#{{ $r->refund_request_id }}</td>
                            <td class="whitespace-nowrap">
                                <span class="block font-medium">{{ $r->tutor_name }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $r->tutor_username }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="block">{{ $r->subject }} – {{ $r->grade_level }}</span>
                                <span class="block text-xs text-muted-foreground">Lớp #{{ $r->class_id }}</span>
                            </td>
                            <td class="max-w-64">
                                <span class="line-clamp-2 text-sm">{{ $r->reasonLabel() ?? $r->reason }}</span>
                                <span class="block text-xs text-muted-foreground">{{ \App\Models\RefundRequest::SOURCES[$r->source_type] ?? $r->source_type }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if ($r->actual_refund_amount !== null)
                                    <span class="block font-medium">{{ RefundService::money($r->actual_refund_amount) }}</span>
                                    <span class="block text-xs text-muted-foreground">dự kiến {{ RefundService::money($r->expected_refund_amount) }}</span>
                                @else
                                    <span class="block font-medium">{{ RefundService::money($r->expected_refund_amount) }}</span>
                                    <span class="block text-xs text-muted-foreground">dự kiến</span>
                                @endif
                            </td>
                            <td>
                                <x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge>
                                @if ($left !== null)
                                    <span @class(['mt-1 flex items-center gap-1 text-xs whitespace-nowrap',
                                        'text-danger font-medium' => $left < 0, 'text-warning font-medium' => $left >= 0 && $left <= 3,
                                        'text-muted-foreground' => $left > 3])>
                                        <x-icon name="clock" class="size-3" />
                                        {{ $left < 0 ? 'quá hạn ' . abs($left) . ' ngày' : ($left === 0 ? 'hết hạn hôm nay' : "còn {$left} ngày") }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-muted-foreground">{{ $r->received_at?->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.refunds.show', $r->refund_request_id) }}"
                                       @class(['btn btn-sm', 'btn-primary' => $r->awaitingDecision() || $r->status === 'refund_failed', 'btn-outline' => ! ($r->awaitingDecision() || $r->status === 'refund_failed')])>
                                        <x-icon name="eye" /> {{ $r->awaitingDecision() ? 'Xử lý' : ($r->status === 'refund_failed' ? 'Hoàn lại' : 'Xem') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-muted-foreground">
                                Không có yêu cầu hoàn phí phù hợp.
                                @if (request('q') || $current)
                                    <a href="{{ route('admin.refunds.index') }}" class="font-medium text-primary hover:underline">Xóa bộ lọc</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground">
            <span>
                @if ($refunds->total())
                    Hiển thị <strong class="text-foreground">{{ $refunds->firstItem() }}–{{ $refunds->lastItem() }}</strong> / {{ $refunds->total() }} yêu cầu
                @else
                    0 yêu cầu
                @endif
            </span>
            @if ($refunds->hasPages())
                <div class="flex gap-2">
                    <a href="{{ $refunds->previousPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => $refunds->onFirstPage()])>‹ Trước</a>
                    <span class="self-center">Trang {{ $refunds->currentPage() }} / {{ $refunds->lastPage() }}</span>
                    <a href="{{ $refunds->nextPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => ! $refunds->hasMorePages()])>Sau ›</a>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
