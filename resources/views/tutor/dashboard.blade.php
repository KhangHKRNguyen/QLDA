{{-- Tổng quan của gia sư --}}
@php
    $now = now('Asia/Ho_Chi_Minh');
    $hour = (int) $now->format('G');
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
    $days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    $firstName = \Illuminate\Support\Str::afterLast($account->full_name, ' ');
@endphp
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Tổng quan')
@section('heading', $greeting . ', ' . $firstName)
@section('description', 'Chúc bạn một ngày giảng dạy hiệu quả.')

@section('content')
<div class="space-y-6">
    @if ($account->status === 'pending_verification')
        <div class="notice notice-info">Hồ sơ của bạn đang chờ Trung tâm xác minh. <a href="{{ route('tutor.status') }}" class="font-medium underline">Xem chi tiết</a></div>
    @elseif ($account->status === 'supplement_required')
        <div class="notice notice-warning">Hồ sơ của bạn cần bổ sung. <a href="{{ route('tutor.status') }}" class="font-medium underline">Bổ sung ngay</a></div>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Lớp đã nhận', $totalClasses, 'text-primary'],
            ['Lớp đang dạy', $teaching, 'text-success'],
            ['Yêu cầu hoàn phí đang chờ', $pendingRefunds, 'text-warning'],
        ] as [$label, $value, $color])
            <div class="card p-5">
                <p class="text-sm text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card">
            <header class="card-header"><h2 class="card-title">Tài khoản của bạn</h2></header>
            <div class="card-body space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-muted-foreground">Trạng thái</span>
                    <x-status-badge :tone="$account->statusTone()">{{ $account->statusLabel() }}</x-status-badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-muted-foreground">Email đăng nhập</span><span class="font-medium">{{ $account->email }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-muted-foreground">Số điện thoại</span><span class="font-medium">{{ $account->phone_number }}</span>
                </div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <a href="{{ route('tutor.status') }}" class="btn btn-outline btn-sm">Trạng thái hồ sơ</a>
                    <a href="{{ route('tutor.profile.edit') }}" class="btn btn-outline btn-sm">Hồ sơ cá nhân</a>
                </div>
            </div>
        </section>

        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Thông báo gần đây @if ($unread > 0)<span class="badge badge-info ml-2">{{ $unread }} mới</span>@endif</h2>
                <a href="{{ route('tutor.notifications') }}" class="text-sm font-medium text-primary hover:underline">Xem tất cả ›</a>
            </header>
            <ul class="divide-y">
                @forelse ($notifications as $n)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-border' : 'bg-primary' }}" aria-hidden="true"></span>
                        <p class="text-sm {{ $n->is_read ? 'text-muted-foreground' : 'font-medium' }}">{{ $n->content }}</p>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted-foreground">Chưa có thông báo nào.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
