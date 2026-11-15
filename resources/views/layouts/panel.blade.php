{{-- GIAO DIỆN ĐĂNG NHẬP RỒI: admin (nhân sự Trung tâm) và tutor (gia sư).
     Dùng: @extends('layouts.panel', ['panel' => 'admin'])   (hoặc 'tutor')
     Các section: title, heading, description (tuỳ chọn), actions (tuỳ chọn), breadcrumb (tuỳ chọn), content --}}
@extends('layouts.base')

@section('body')
@php
    $panel = $panel ?? 'admin';
    $user  = auth()->user();
    $role  = $user->role ?? null;   // chưa có cột role / chưa đăng nhập → hiện đủ menu để dev xem
    $items = collect(config("site.menu.$panel"))
        ->filter(fn ($i) => empty($i['roles']) || ! $role || in_array($role, $i['roles'], true));
@endphp

<aside class="sidebar" aria-label="Thanh điều hướng">
    <div class="flex h-16 items-center border-b px-4">
        <x-logo :href="url($panel === 'tutor' ? '/gia-su' : '/admin')" :subtitle="config('site.panel_names.' . $panel)" />
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Menu chức năng">
        @foreach ($items as $item)
            @php $active = $loop->first ? request()->is(ltrim($item['path'], '/')) : request()->is(ltrim($item['path'], '/') . '*'); @endphp
            <a href="{{ url($item['path']) }}" class="nav-link" @if($active) aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
    </nav>
</aside>
<div id="sidebar-backdrop" class="sidebar-backdrop" hidden></div>

<div class="flex min-h-dvh flex-col lg:pl-64">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b bg-card/95 px-4 backdrop-blur sm:px-6">
        <button type="button" class="btn btn-ghost btn-icon lg:hidden" data-sidebar-toggle aria-label="Mở menu">
            <x-icon name="menu" class="size-5" />
        </button>
        <div class="flex-1"></div>
        <div class="flex items-center gap-1">
            <button type="button" class="btn btn-ghost btn-icon relative" aria-label="Thông báo">
                <x-icon name="bell" class="size-5" />
                {{-- chấm đỏ khi có thông báo chưa đọc: bỏ comment dòng dưới --}}
                {{-- <span class="absolute top-2 right-2 size-2 rounded-full bg-danger"></span> --}}
            </button>
            <span class="hidden px-2 text-sm font-medium sm:inline">{{ $user->name ?? 'Tài khoản' }}</span>
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm"><x-icon name="logout" /> Đăng xuất</button>
                </form>
            @endif
        </div>
    </header>

    <main class="container-page flex-1 py-6">
        <div class="mb-6 flex flex-col gap-3">
            @hasSection('breadcrumb')
                <nav aria-label="Đường dẫn" class="breadcrumb">@yield('breadcrumb')</nav>
            @endif
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="page-title">@yield('heading', 'Tổng quan')</h1>
                    @hasSection('description')<p class="page-desc">@yield('description')</p>@endif
                </div>
                @hasSection('actions')<div class="flex flex-wrap items-center gap-2">@yield('actions')</div>@endif
            </div>
        </div>

        @yield('content')
    </main>
</div>
@endsection
