
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Thêm bài viết')

@section('heading', 'Thêm bài viết mới')

@section('description', 'Nhập thông tin để tạo bài viết mới cho Trung tâm.')

@section('content')
<form action="{{ route('staff.articles.store') }}" method="POST">
    @csrf

    <div class="card p-6">

        {{-- Trường tiêu đề --}}
        <div>
            <label for="title" class="block text-sm font-medium mb-2">
                Tiêu đề bài viết
            </label>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="255"
                value="{{ old('title') }}"
                placeholder="Nhập tiêu đề bài viết..."
                class="w-full rounded-md border px-3 py-2"
            >

            @error('title')
    <p class="mt-1 text-sm font-medium" style="color: #dc2626;">
        {{ $message }}
    </p>
@enderror
        </div>

        {{-- Trường nội dung --}}
        <div class="mt-5">
            <label for="content" class="block text-sm font-medium mb-2">
                Nội dung bài viết
            </label>

            <textarea
                id="content"
                name="content"
                rows="10"
                placeholder="Nhập nội dung bài viết..."
                class="w-full rounded-md border px-3 py-2"
            >{{ old('content') }}</textarea>

            @error('content')
    <p class="mt-1 text-sm font-medium" style="color: #dc2626;">
        {{ $message }}
    </p>
@enderror
        </div>

        {{-- Các nút thao tác --}}
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('staff.articles.index') }}"
               class="btn btn-ghost">
                Hủy
            </a>

            <button type="submit" class="btn btn-primary">
                Lưu bản nháp
            </button>
        </div>

    </div>
</form>
@endsection
