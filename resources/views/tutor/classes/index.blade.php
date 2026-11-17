{{-- Gia sư — "Lớp của tôi": các lớp đã nhận (PTTKHT 2.1.8) --}}
@php use App\Services\TeachingService as T; @endphp
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Lớp của tôi')
@section('heading', 'Lớp của tôi')
@section('description', 'Các lớp bạn đã nhận. Thời hạn bảo hành ' . T::WARRANTY_DAYS . ' ngày kể từ ngày nhận lớp; trong thời hạn này bạn có thể gửi yêu cầu hoàn phí.')

@section('content')
<div class="space-y-6">
    <nav class="flex flex-wrap gap-2" aria-label="Lọc lớp">
        @foreach (['' => 'Tất cả', 'dang-day' => 'Đang dạy', 'hoan-thanh' => 'Hoàn thành'] as $key => $label)
            <a href="{{ route('tutor.classes.index', array_filter(['loc' => $key])) }}"
               @class(['btn btn-sm', 'btn-primary' => $filter === $key, 'btn-outline' => $filter !== $key])>{{ $label }}</a>
        @endforeach
    </nav>

    @forelse ($classes as $c)
        @php $left = T::warrantyDaysLeft($c); @endphp
        <a href="{{ route('tutor.classes.show', $c->class_id) }}" class="card block p-5 transition-colors hover:border-primary/40">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground">Lớp #{{ $c->class_id }} · {{ $c->learning_format }}</p>
                    <p class="mt-0.5 text-lg font-semibold">{{ $c->subject }} – {{ $c->grade_level }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $c->sessions_per_week }} buổi/tuần · {{ \App\Services\RefundService::money($c->fee_per_session) }}/buổi · {{ $c->location ?: 'Chưa có địa điểm' }}</p>
                </div>
                <div class="flex flex-col items-end gap-1.5">
                    <x-status-badge :tone="T::statusTone($c)">{{ T::statusLabel($c) }}</x-status-badge>
                    @if ($c->status === 'teaching' && T::stageLabel($c))
                        <span class="text-xs font-medium text-muted-foreground">{{ T::stageLabel($c) }}</span>
                    @endif
                </div>
            </div>
            @if ($c->status === 'teaching')
                @php $pct = max(0, min(100, 100 - $left / T::WARRANTY_DAYS * 100)); @endphp
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-muted-foreground">
                        <span>Nhận lớp {{ \Illuminate\Support\Carbon::parse($c->accepted_at)->format('d/m/Y') }}</span>
                        <span>{{ $left > 0 ? "Bảo hành còn {$left} ngày" : 'Đã hết bảo hành' }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full {{ $left > 0 ? 'bg-primary' : 'bg-muted-foreground' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endif
        </a>
    @empty
        <div class="card p-10 text-center text-muted-foreground">
            <p>Bạn chưa có lớp nào{{ $filter ? ' ở mục này' : '' }}.</p>
            @if (Route::has('tutor.open-classes'))
                <a href="{{ route('tutor.open-classes') }}" class="mt-2 inline-flex font-medium text-primary hover:underline">Xem lớp đang tuyển</a>
            @endif
        </div>
    @endforelse
</div>
@endsection
