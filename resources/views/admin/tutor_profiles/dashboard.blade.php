{{-- Tổng quan của Nhân viên Quản lý Gia sư --}}
@php
    $now = now('Asia/Ho_Chi_Minh');
    $hour = (int) $now->format('G');
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
    $days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    $firstName = \Illuminate\Support\Str::afterLast((string) $name, ' ');
    $cards = [
        ['Chờ duyệt',      'pending_verification', 'text-primary'],
        ['Chờ bổ sung',    'supplement_required',  'text-warning'],
        ['Đang hoạt động', 'active',               'text-success'],
        ['Tạm khóa',       'locked',               'text-warning'],
        ['Đình chỉ',       'suspended',            'text-danger'],
    ];
@endphp
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Tổng quan')
@section('heading', $greeting . ', ' . $firstName)
@section('description', $days[$now->dayOfWeek] . ', ' . $now->format('d/m/Y') . ' · Tổng quan hồ sơ và tài khoản gia sư.')
@section('actions')
    <a href="{{ route('admin.tutor-profiles.index') }}" class="btn btn-primary">Danh sách hồ sơ gia sư</a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ($cards as [$label, $status, $color])
            <a href="{{ route('admin.tutor-profiles.index', ['status' => $status]) }}" class="card p-4 transition-colors hover:border-primary/40">
                <p class="text-xs font-medium text-muted-foreground">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $counts[$status] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="card">
            <header class="card-header">
                <h2 class="card-title">Hồ sơ chờ duyệt</h2>
                <a href="{{ route('admin.tutor-profiles.index') }}" class="text-sm font-medium text-primary hover:underline">Xem tất cả ›</a>
            </header>
            <ul class="divide-y">
                @forelse ($pending as $a)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $a->full_name }}</p>
                            <p class="text-xs text-muted-foreground">{{ $a->email }} · {{ $a->phone_number }}</p>
                        </div>
                        <a href="{{ route('admin.tutor-profiles.show', $a->account_id) }}" class="btn btn-outline btn-sm">Xem</a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted-foreground">Không có hồ sơ nào đang chờ duyệt.</li>
                @endforelse
            </ul>
        </section>

        <section class="card">
            <header class="card-header"><h2 class="card-title">Sắp hết hạn bổ sung hồ sơ</h2></header>
            <ul class="divide-y">
                @forelse ($expiring as $row)
                    @php
                        $deadline = \Illuminate\Support\Carbon::parse($row->deadline);
                        $left = (int) today()->diffInDays($deadline, false);
                    @endphp
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $row->full_name }}</p>
                            <p class="text-xs text-muted-foreground">Hạn: {{ $deadline->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($left < 0)
                                <x-status-badge tone="danger">Quá hạn</x-status-badge>
                            @else
                                <x-status-badge :tone="$left <= 3 ? 'warning' : 'info'">Còn {{ $left }} ngày</x-status-badge>
                            @endif
                            <a href="{{ route('admin.tutor-profiles.show', $row->account_id) }}" class="btn btn-outline btn-sm">Xem</a>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted-foreground">Không có hồ sơ nào đang chờ bổ sung.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="card">
        <header class="card-header">
            <h2 class="card-title">Thông báo gần đây @if ($unread > 0)<span class="badge badge-info ml-2">{{ $unread }} mới</span>@endif</h2>
            <a href="{{ route('admin.tutor-profiles.notifications') }}" class="text-sm font-medium text-primary hover:underline">Xem tất cả ›</a>
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
@endsection
