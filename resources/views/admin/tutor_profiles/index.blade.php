{{-- QLGS: danh sách hồ sơ gia sư chờ duyệt (quy trình 2.1.5, HĐ4) --}}
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Hồ sơ gia sư')
@section('heading', 'Tài khoản gia sư')
@section('description', 'Xem và xử lý hồ sơ đăng ký của gia sư.')
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> / <span class="font-medium text-foreground">Tài khoản gia sư</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.tutor-profiles.index', ['status' => $key]) }}"
               @class(['btn btn-sm', 'btn-primary' => $status === $key, 'btn-outline' => $status !== $key])>
                {{ $label }} ({{ $counts[$key] ?? 0 }})
            </a>
        @endforeach
        <a href="{{ route('admin.tutor-profiles.index', ['status' => 'all']) }}"
           @class(['btn btn-sm', 'btn-primary' => $status === 'all', 'btn-outline' => $status !== 'all'])>Tất cả</a>
    </div>

    <section class="card">
        <form method="GET" action="{{ route('admin.tutor-profiles.index') }}" class="flex flex-wrap gap-3 border-b p-4">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ request('q') }}" class="input max-w-sm" placeholder="Tìm theo họ tên, email, SĐT..." aria-label="Tìm hồ sơ gia sư">
            <button type="submit" class="btn btn-primary h-10">Tìm</button>
            @if (request('q'))<a href="{{ route('admin.tutor-profiles.index', ['status' => $status]) }}" class="btn btn-ghost h-10">Xóa lọc</a>@endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Mã</th><th>Gia sư</th><th>Liên hệ</th><th>Khu vực</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($accounts as $a)
                        <tr>
                            <td class="whitespace-nowrap text-muted-foreground">#{{ $a->account_id }}</td>
                            <td>
                                <a href="{{ route('admin.tutor-profiles.show', $a->account_id) }}" class="font-medium hover:text-primary">{{ $a->full_name }}</a>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="block">{{ $a->email ?: '—' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $a->phone_number }}</span>
                            </td>
                            <td>{{ $a->district ?: '—' }}</td>
                            <td><x-status-badge :tone="$a->statusTone()">{{ $a->statusLabel() }}</x-status-badge></td>
                            <td class="text-right">
                                <a href="{{ route('admin.tutor-profiles.show', $a->account_id) }}" class="btn btn-outline btn-sm">Xem hồ sơ</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-muted-foreground">Không có hồ sơ nào ở mục này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground">
            <span>
                @if ($accounts->total())
                    Hiển thị <strong class="text-foreground">{{ $accounts->firstItem() }}–{{ $accounts->lastItem() }}</strong> / {{ $accounts->total() }} hồ sơ
                @else
                    0 hồ sơ
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
@endsection
