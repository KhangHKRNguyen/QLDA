{{-- QLGS: danh sách thông báo (hồ sơ gia sư mới, hồ sơ bổ sung, ...) --}}
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Thông báo')
@section('heading', 'Thông báo')
@section('description', $unread > 0 ? "Bạn có {$unread} thông báo chưa đọc." : 'Bạn đã đọc hết thông báo.')
@section('actions')
    @if ($unread > 0)
        <form method="POST" action="{{ route('admin.tutor-profiles.notifications.read') }}">
            @csrf
            <button type="submit" class="btn btn-outline">Đánh dấu tất cả đã đọc</button>
        </form>
    @endif
@endsection

@section('content')
<section class="card">
    <ul class="divide-y">
        @forelse ($items as $n)
            <li class="flex items-start gap-3 px-5 py-4 {{ $n->is_read ? '' : 'bg-secondary/40' }}">
                <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-border' : 'bg-primary' }}" aria-hidden="true"></span>
                <p class="text-sm {{ $n->is_read ? 'text-muted-foreground' : 'font-medium text-foreground' }}">{{ $n->content }}</p>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-sm text-muted-foreground">Chưa có thông báo nào.</li>
        @endforelse
    </ul>

    @if ($items->hasPages())
        <div class="flex items-center justify-between gap-3 border-t px-5 py-3 text-sm text-muted-foreground">
            <a href="{{ $items->previousPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => $items->onFirstPage()])>‹ Trước</a>
            <span>Trang {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
            <a href="{{ $items->nextPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => ! $items->hasMorePages()])>Sau ›</a>
        </div>
    @endif
</section>
@endsection
