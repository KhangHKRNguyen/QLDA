
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Chỉnh sửa bài viết')

@section('heading', 'Chỉnh sửa bài viết')

@section('description', 'Cập nhật thông tin bài viết của Trung tâm.')

@section('content')

<div class="card mt-4 p-6">

<form action="{{ route('staff.articles.update', $article->post_id) }}"
      method="POST">
    @csrf
    @method('PUT')

        {{-- Tiêu đề bài viết --}}
        <div class="mb-4">
            <label for="title" class="block mb-2">
                Tiêu đề bài viết
            </label>

            <input
                type="text"
                id="title"
                name="title"
                value="{{ old('title', $article->title) }}"
                class="w-full"
            >
        </div>

        {{-- Nội dung bài viết --}}
        <div class="mb-4">
            <label for="content" class="block mb-2">
                Nội dung bài viết
            </label>

            <textarea
                id="content"
                name="content"
                rows="10"
                class="w-full"
            >{{ old('content', $article->content) }}</textarea>
        </div>

        {{-- Nút thao tác --}}
        <div class="flex justify-end gap-2">
            <a href="{{ route('staff.articles.index') }}"
               class="btn btn-secondary">
                Hủy
            </a>

            <button type="submit" class="btn btn-primary">
                Lưu thay đổi
            </button>
        </div>

    </form>

</div>

@endsection
