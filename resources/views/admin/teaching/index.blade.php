{{-- NV QLKH — "Lớp đang dạy": các lớp đã có gia sư (PTTKHT 2.1.8) --}}
@php use App\Services\TeachingService as T; @endphp
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Lớp đang dạy')
@section('heading', 'Lớp đang dạy')
@section('description', 'Theo dõi lớp đã có gia sư: ghi nhận kết quả dạy thử, tạo phiếu hoàn phí theo phản ánh của phụ huynh, ghi nhận hoàn thành.')
@section('actions')
    <a href="{{ route('admin.teaching.refunds.index') }}" class="btn btn-outline">Phiếu hoàn phí
        @if ($stats['awaiting'])<span class="ml-1 rounded-full bg-warning px-1.5 text-xs text-white">{{ $stats['awaiting'] }}</span>@endif
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Chờ kết quả dạy thử', $stats['trial'], 'day-thu', 'text-primary'],
            ['Dạy chính thức', $stats['official'], 'chinh-thuc', 'text-success'],
            ['Đang xử lý hoàn phí', $stats['refund'], 'hoan-phi', 'text-warning'],
            ['Phiếu chờ xác nhận', $stats['awaiting'], null, 'text-danger'],
        ] as [$label, $value, $key, $color])
            @php $active = $key && $filter === $key; @endphp
            <a href="{{ $key ? route('admin.teaching.index', ['loc' => $key]) : route('admin.teaching.refunds.index', ['loc' => 'cho-xac-nhan']) }}"
               @class(['card p-4 transition-colors hover:border-primary/40', 'border-primary ring-3 ring-primary/15' => $active])>
                <p class="text-xs font-medium text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <section class="card">
        <form method="GET" class="grid gap-3 border-b p-4 md:grid-cols-[1fr_14rem_auto]">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground" />
                <input type="search" name="q" value="{{ request('q') }}" class="input pl-9" placeholder="Tìm theo mã lớp, môn, tên gia sư, phụ huynh, SĐT..." aria-label="Tìm lớp">
            </div>
            <select name="loc" class="input" onchange="this.form.submit()" aria-label="Lọc">
                @foreach (['' => 'Tất cả', 'day-thu' => 'Chờ kết quả dạy thử', 'chinh-thuc' => 'Dạy chính thức', 'hoan-phi' => 'Đang xử lý hoàn phí', 'hoan-thanh' => 'Hoàn thành'] as $k => $l)
                    <option value="{{ $k }}" @selected($filter === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-primary h-10"><x-icon name="search" /> Tìm</button>
                @if (request('q') || $filter)<a href="{{ route('admin.teaching.index') }}" class="btn btn-ghost h-10">Xóa lọc</a>@endif
            </div>
        </form>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Lớp</th><th>Gia sư</th><th>Phụ huynh</th><th>Trạng thái</th><th>Bảo hành</th><th></th></tr></thead>
                <tbody>
                    @forelse ($classes as $c)
                        @php $left = T::warrantyDaysLeft($c); @endphp
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="block font-medium">{{ $c->subject }} – {{ $c->grade_level }}</span>
                                <span class="block text-xs text-muted-foreground">#{{ $c->class_id }} · {{ $c->learning_format }}</span>
                            </td>
                            <td class="whitespace-nowrap">{{ $c->tutor_name ?? '—' }}</td>
                            <td class="whitespace-nowrap">
                                <span class="block">{{ $c->customer_name ?? '—' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $c->customer_phone }}</span>
                            </td>
                            <td>
                                <x-status-badge :tone="T::statusTone($c)">{{ T::statusLabel($c) }}</x-status-badge>
                                @if ($c->status === 'teaching' && T::stageLabel($c))
                                    <span class="mt-1 block text-xs text-muted-foreground">{{ T::stageLabel($c) }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm {{ $left > 0 && $left <= 7 ? 'font-medium text-warning' : 'text-muted-foreground' }}">
                                {{ $c->status === 'completed' ? '—' : ($left > 0 ? "còn {$left} ngày" : 'hết hạn') }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.teaching.show', $c->class_id) }}"
                                   @class(['btn btn-sm', 'btn-primary' => $c->status === 'teaching' && $c->teaching_stage === 'trial', 'btn-outline' => ! ($c->status === 'teaching' && $c->teaching_stage === 'trial')])>
                                    {{ $c->status === 'teaching' && $c->teaching_stage === 'trial' ? 'Ghi nhận dạy thử' : 'Xem' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-muted-foreground">Không có lớp phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($classes->hasPages())
            <div class="flex justify-between border-t px-4 py-3 text-sm text-muted-foreground">
                <span>{{ $classes->firstItem() }}–{{ $classes->lastItem() }} / {{ $classes->total() }} lớp</span>
                <span class="flex gap-2">
                    <a href="{{ $classes->previousPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => $classes->onFirstPage()])>‹ Trước</a>
                    <a href="{{ $classes->nextPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => ! $classes->hasMorePages()])>Sau ›</a>
                </span>
            </div>
        @endif
    </section>
</div>
@endsection
