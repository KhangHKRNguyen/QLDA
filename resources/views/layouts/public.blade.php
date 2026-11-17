{{-- GIAO DIỆN KHÁCH HÀNG. Dùng: @extends('layouts.public') + @section('title') + @section('content') --}}
@extends('layouts.base')

@section('body')
@php
    $c = config('site.contact');
    $isTutor = auth()->check() && auth()->user()->role?->role_name === 'Gia sư';
    // Gia sư dùng trang hồ sơ riêng; các vai trò khác giữ trang /profile của Khang
    $profileUrl = $isTutor ? route('tutor.profile.edit') : route('profile.edit');
@endphp
<div class="flex min-h-dvh flex-col">

    <header class="sticky top-0 z-40 border-b bg-card/95 backdrop-blur">
        <div class="container-page flex h-16 items-center justify-between gap-4">
            <x-logo />

            <nav aria-label="Menu chính" class="hidden lg:block">
                <ul class="flex items-center gap-1">
                    @foreach (config('site.public_nav') as $item)
                        @php $active = $item['path'] === '/' ? request()->is('/') : request()->is(ltrim($item['path'], '/') . '*'); @endphp
                        <li>
                            <a href="{{ url($item['path']) }}" @if($active) aria-current="page" @endif
                               class="rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-muted {{ $active ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }}">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @auth
                <details class="relative hidden lg:block">
                    <summary class="btn btn-outline inline-flex cursor-pointer list-none items-center gap-2">
                        <x-icon name="user-circle" class="size-5" />
                        <span class="max-w-36 truncate">
                            {{ auth()->user()->name }}
                        </span>
                        <x-icon name="chevron-down" class="size-4" />
                    </summary>

                    <div class="absolute right-0 z-50 mt-2 w-56 rounded-xl border bg-card p-2 shadow-lg">
                        <a href="{{ route('dashboard') }}"
                        class="block rounded-lg px-3 py-2 text-sm hover:bg-muted">
                            Tổng quan
                        </a>

                        <a href="{{ $profileUrl }}"
                        class="block rounded-lg px-3 py-2 text-sm hover:bg-muted">
                            <x-icon name="user" class="mr-2 inline size-4" />
                            Thông tin cá nhân
                        </a>

                        <div class="my-1 border-t"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="block w-full rounded-lg px-3 py-2 text-left text-sm text-danger hover:bg-muted">
                                <x-icon name="log-out" class="mr-2 inline size-4" />
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </details>
            @else
                <div class="hidden items-center gap-2 lg:flex">
                    <a href="{{ route('login') }}" class="btn btn-outline">Đăng nhập</a>
                    <a href="{{ route('tutor.register') }}" class="btn btn-primary">Đăng ký làm gia sư</a>
                </div>
            @endauth

            <button type="button" class="btn btn-ghost btn-icon lg:hidden" data-toggle="#mobile-nav"
                    aria-label="Mở/đóng menu" aria-expanded="false" aria-controls="mobile-nav">
                <x-icon name="menu" class="size-5" />
            </button>
        </div>

        <nav id="mobile-nav" aria-label="Menu di động" class="border-t bg-card px-4 py-3 lg:hidden" hidden>
            <ul class="flex flex-col gap-1">
                @foreach (config('site.public_nav') as $item)
                    @php $active = $item['path'] === '/' ? request()->is('/') : request()->is(ltrim($item['path'], '/') . '*'); @endphp
                    <li>
                        <a href="{{ url($item['path']) }}" @if($active) aria-current="page" @endif
                           class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ $active ? 'bg-secondary text-primary' : 'text-foreground hover:bg-muted' }}">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
                <li class="pt-2">
                    @auth
                        <details>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg border px-3 py-2.5 text-sm font-medium">
                                <span class="flex items-center gap-2">
                                    <x-icon name="user-circle" class="size-5" />
                                    {{ auth()->user()->name }}
                                </span>
                                <x-icon name="chevron-down" class="size-4" />
                            </summary>

                            <div class="mt-2 rounded-lg border bg-card p-2">
                                <a href="{{ route('dashboard') }}"
                                class="block rounded-lg px-3 py-2.5 text-sm hover:bg-muted">
                                    Tổng quan
                                </a>

                                <a href="{{ $profileUrl }}"
                                class="block rounded-lg px-3 py-2.5 text-sm hover:bg-muted">
                                    <x-icon name="user" class="mr-2 inline size-4" />
                                    Thông tin cá nhân
                                </a>

                                <div class="my-1 border-t"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-danger hover:bg-muted">
                                        <x-icon name="log-out" class="mr-2 inline size-4" />
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </details>
                    @else
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('login') }}" class="btn btn-outline w-full">Đăng nhập</a>
                            <a href="{{ route('tutor.register') }}" class="btn btn-primary w-full">Đăng ký làm gia sư</a>
                        </div>
                    @endauth
                </li>
            </ul>
        </nav>
    </header>

    <main class="flex-1">@yield('content')</main>

    <footer class="border-t bg-card">
        <div class="container-page grid gap-8 py-10 md:grid-cols-3">
            <div class="flex flex-col gap-3">
                <x-logo />
                <p class="max-w-xs text-sm text-muted-foreground">{{ config('site.tagline') }}</p>
            </div>
            <div>
                <h2 class="text-sm font-semibold">Liên kết</h2>
                <ul class="mt-3 flex flex-col gap-2 text-sm text-muted-foreground">
                    <li><a class="hover:text-primary" href="{{ url('/tim-gia-su') }}">Đăng ký tìm gia sư</a></li>
                    <li><a class="hover:text-primary" href="{{ url('/bai-viet') }}">Bài viết</a></li>
                    @guest
                        <li><a class="hover:text-primary" href="{{ route('tutor.register') }}">Đăng ký làm gia sư</a></li>
                        <li><a class="hover:text-primary" href="{{ route('login') }}">Đăng nhập</a></li>
                    @endguest
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-semibold">Liên hệ</h2>
                <ul class="mt-3 flex flex-col gap-2 text-sm text-muted-foreground">
                    <li class="flex gap-2"><x-icon name="pin" class="mt-0.5" />{{ $c['address'] }}</li>
                    <li class="flex gap-2"><x-icon name="phone" class="mt-0.5" />{{ $c['phone'] }}</li>
                    <li class="flex gap-2"><x-icon name="mail" class="mt-0.5" />{{ $c['email'] }}</li>
                </ul>
            </div>
        </div>
        <div class="border-t">
            <p class="container-page py-4 text-xs text-muted-foreground">© {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </footer>
</div>
@endsection