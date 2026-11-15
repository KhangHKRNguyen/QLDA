{{-- Quản lý tài khoản — danh sách + bộ lọc (Frm_QuanLyTaiKhoan) --}}
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Quản lý tài khoản')
@section('heading', 'Quản lý tài khoản')
@section('description', 'Tra cứu, xem xét và xử lý vi phạm của các tài khoản trong hệ thống.')
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> / <span class="font-medium text-foreground">Tài khoản</span>
@endsection
@section('actions')
    <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">+ Thêm tài khoản</a>
@endsection

@section('content')
@php
    $filters  = array_filter(request()->only('q', 'role', 'status'));
    $cards = [
        ['label' => 'Tổng tài khoản', 'value' => $stats['all'],                  'status' => null,                   'color' => 'text-foreground'],
        ['label' => 'Đang hoạt động', 'value' => $stats['active'],               'status' => 'active',               'color' => 'text-success'],
        ['label' => 'Tạm khóa',       'value' => $stats['locked'],               'status' => 'locked',               'color' => 'text-warning'],
        ['label' => 'Đình chỉ',       'value' => $stats['suspended'],            'status' => 'suspended',            'color' => 'text-danger'],
        ['label' => 'Chờ xác minh',   'value' => $stats['pending_verification'], 'status' => 'pending_verification', 'color' => 'text-primary'],
    ];
@endphp
<div class="space-y-6">
    {{-- ===== Thống kê nhanh (bấm để lọc theo trạng thái) ===== --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($cards as $c)
            @php $current = request('status') == $c['status'] && ! request('q') && ! request('role'); @endphp
            <a href="{{ route('admin.accounts.index', array_filter(['status' => $c['status']])) }}"
               @class(['card p-4 transition-colors hover:border-primary/40', 'border-primary ring-3 ring-primary/15' => $current])
               @if ($current) aria-current="true" @endif>
                <p class="text-xs font-medium text-muted-foreground">{{ $c['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $c['color'] }}">{{ $c['value'] }}</p>
            </a>
        @endforeach
    </div>

    <section class="card">
        {{-- ===== Bộ lọc ===== --}}
        <form method="GET" action="{{ route('admin.accounts.index') }}"
              class="grid gap-3 border-b p-4 md:grid-cols-[1fr_13rem_13rem_auto]">
            {{-- Giữ cách sắp xếp hiện tại khi tìm kiếm / lọc --}}
            @if (request()->has('sort'))
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">
            @endif
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground" />
                <input type="search" name="q" value="{{ request('q') }}" class="input pl-9"
                       placeholder="Tìm theo mã (VD: 12), họ tên, tên đăng nhập, email, SĐT..." aria-label="Tìm kiếm tài khoản">
            </div>
            <select name="role" class="input" aria-label="Lọc theo vai trò" onchange="this.form.submit()">
                <option value="">Tất cả vai trò</option>
                @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>
                @endforeach
            </select>
            <select name="status" class="input" aria-label="Lọc theo trạng thái" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                @foreach ($statuses as $key => [$label])
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary h-10"><x-icon name="search" /> Tìm</button>
                @if ($filters)
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-ghost h-10">Xóa lọc</a>
                @endif
            </div>
        </form>

        {{-- ===== Bảng tài khoản ===== --}}
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        {{-- Bấm tiêu đề để sắp xếp; bấm lại để đảo chiều --}}
                        @foreach (['ma' => 'Mã', 'ten' => 'Tài khoản', 'lien-he' => 'Liên hệ', 'vai-tro' => 'Vai trò', 'trang-thai' => 'Trạng thái', 'cap-nhat' => 'Cập nhật'] as $key => $title)
                            @php
                                $active = $sort === $key;
                                $next   = $active && $dir === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <th aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next, 'page' => null]) }}"
                                   @class(['group inline-flex items-center gap-1 whitespace-nowrap hover:text-foreground', 'text-foreground' => $active])
                                   title="Sắp xếp theo {{ mb_strtolower($title) }}">
                                    {{ $title }}
                                    @if ($active)
                                        <x-icon :name="$dir === 'asc' ? 'arrow-up' : 'arrow-down'" class="size-3.5 text-primary" />
                                    @else
                                        <x-icon name="sort" class="size-3.5 opacity-40 group-hover:opacity-100" />
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $a)
                        <tr>
                            <td class="font-medium whitespace-nowrap text-muted-foreground">#{{ $a->account_id }}</td>
                            <td>
                                <a href="{{ route('admin.accounts.show', $a->account_id) }}" class="group flex items-center gap-3">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">{{ $a->initial }}</span>
                                    <span class="min-w-0">
                                        <span class="block font-medium whitespace-nowrap group-hover:text-primary">{{ $a->full_name }}</span>
                                        <span class="block text-xs text-muted-foreground">{{ $a->username }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="block">{{ $a->email ?: '—' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $a->phone_number }}</span>
                            </td>
                            <td class="whitespace-nowrap">{{ $a->role_name }}</td>
                            <td>
                                <x-status-badge :tone="$a->statusTone()">{{ $a->statusLabel() }}</x-status-badge>
                                @if ($a->status === 'locked' && $a->lock_expires_at)
                                    <span class="mt-1 flex items-center gap-1 text-xs whitespace-nowrap text-muted-foreground">
                                        <x-icon name="clock" class="size-3" /> đến {{ $a->lock_expires_at->format('d/m/Y') }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-muted-foreground">{{ $a->status_updated_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    @if ($a->status === 'active')
                                        <button type="button" class="btn btn-ghost btn-sm"
                                            data-lock-action="{{ route('admin.accounts.lock', $a->account_id) }}"
                                            data-lock-name="{{ $a->full_name }}">
                                            <x-icon name="lock" /> Tạm khóa
                                        </button>
                                    @elseif ($a->status === 'locked')
                                        <button type="button" class="btn btn-ghost btn-sm"
                                            data-confirm
                                            data-confirm-title="Mở khóa tài khoản"
                                            data-confirm-message="Tài khoản {{ $a->full_name }} sẽ hoạt động trở lại trước thời hạn và người dùng nhận được thông báo."
                                            data-confirm-action="{{ route('admin.accounts.unlock', $a->account_id) }}"
                                            data-confirm-label="Mở khóa">
                                            <x-icon name="unlock" /> Mở khóa
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.accounts.show', $a->account_id) }}" class="btn btn-outline btn-sm">
                                        <x-icon name="eye" /> Xem
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-muted-foreground">
                                Không tìm thấy tài khoản phù hợp.
                                <a href="{{ route('admin.accounts.index') }}" class="font-medium text-primary hover:underline">Xóa bộ lọc</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground">
            <span>
                @if ($accounts->total())
                    Hiển thị <strong class="text-foreground">{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</strong>
                    / {{ $accounts->total() }} tài khoản
                @else
                    0 tài khoản
                @endif
            </span>
            @if ($accounts->hasPages())
                <div class="flex gap-2">
                    <a href="{{ $accounts->previousPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => $accounts->onFirstPage()])>‹ Trước</a>
                    <span class="self-center">Trang {{ $accounts->currentPage() }} / {{ $accounts->lastPage() }}</span>
                    <a href="{{ $accounts->nextPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => ! $accounts->hasMorePages()])>Sau ›</a>
                </div>
            @endif
        </div>
    </section>
</div>

@include('admin.accounts._lock-modal')
@endsection
