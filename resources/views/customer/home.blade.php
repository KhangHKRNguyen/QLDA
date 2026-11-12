@extends('layouts.public')
@section('title', 'Trang chủ')
@section('content')

    <section class="container-page py-12">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight">
                Chào mừng đến với Trung tâm Gia sư
            </h1>

            <p class="mt-4 text-muted-foreground">
                Kết nối phụ huynh với gia sư phù hợp,
                giúp học sinh nâng cao kết quả học tập.
            </p>

            <div class="mt-6">
                <a href="{{ url('/tim-gia-su') }}" class="btn btn-primary">
                    Tìm gia sư ngay
                </a>
            </div>
        </div>
    </section>

@endsection
