
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Quản lý bài viết')

@section('heading', 'Quản lý bài viết')

@section('description', 'Quản lý và theo dõi các bài viết của Trung tâm.')

@section('content')

{{-- Thông báo --}}
@if (session('success'))
    <div class="mb-4">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4">
        {{ session('error') }}
    </div>
@endif

{{-- Nút thêm bài viết --}}
<div class="flex justify-end">
    <a href="{{ route('staff.articles.create') }}" class="btn btn-primary">
        + Thêm bài viết
    </a>
</div>

{{-- Danh sách bài viết --}}
<div class="card mt-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left">Mã bài viết</th>
                    <th class="px-4 py-3 text-left">Tiêu đề</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-left">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($articles as $article)
                    <tr>
                        {{-- Mã bài viết --}}
                        <td class="px-4 py-3">
                            {{ $article->post_id }}
                        </td>

                        {{-- Tiêu đề --}}
                        <td class="px-4 py-3">
                            {{ $article->title }}
                        </td>

                        {{-- Trạng thái --}}
                        <td class="px-4 py-3">
                            @if ($article->visibility_status === 'draft')
                                Bản nháp
                            @elseif ($article->visibility_status === 'published')
                                Đã xuất bản
                            @elseif ($article->visibility_status === 'hidden')
                                Đã ẩn
                            @else
                                {{ $article->visibility_status }}
                            @endif
                        </td>

                        {{-- Thao tác --}}

<td class="px-4 py-3">
    <div class="flex items-center gap-2 whitespace-nowrap">

        {{-- Nút chỉnh sửa --}}
        <a href="{{ route('staff.articles.edit', $article->post_id) }}"
           class="btn btn-secondary">
            Chỉnh sửa
        </a>
{{-- Nút xóa bài viết --}}
<form action="{{ route('staff.articles.destroy', $article->post_id) }}"
      method="POST">
    @csrf
    @method('DELETE')

    <button type="submit"
        style="background-color: #dc2626 !important; color: #ffffff !important; border: none;"
        class="px-3 py-2 rounded-md font-medium"
        onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này không? Hành động này không thể hoàn tác.')">
    Xóa
</button>
</form>
        @if ($article->visibility_status === 'draft')

            {{-- Nút đăng bài --}}
            <form action="{{ route('staff.articles.publish', $article->post_id) }}"
                  method="POST">
                @csrf
                @method('PATCH')

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Bạn có chắc chắn muốn đăng bài viết này không?')">
                    Đăng bài
                </button>
            </form>

        @elseif ($article->visibility_status === 'published')

            {{-- Nút ẩn bài --}}
            <form action="{{ route('staff.articles.hide', $article->post_id) }}"
                  method="POST">
                @csrf
                @method('PATCH')

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Bạn có chắc chắn muốn ẩn bài viết này không?')">
                    Ẩn bài
                </button>
            </form>

        @elseif ($article->visibility_status === 'hidden')

            {{-- Nút hiện lại --}}
            <form action="{{ route('staff.articles.unhide', $article->post_id) }}"
                  method="POST">
                @csrf
                @method('PATCH')

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Bạn có chắc chắn muốn hiện lại bài viết này không?')">
                    Hiện lại
                </button>
            </form>

        @endif

    </div>
</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center">
                            Chưa có bài viết nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Phân trang --}}
<div class="mt-4">
    {{ $articles->links() }}
</div>

@endsection
