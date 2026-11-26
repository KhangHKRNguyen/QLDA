{{-- Gia sư - HĐ1: danh sách lớp đang tuyển và bộ lọc --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Lớp đang tuyển')
@section('heading', 'Lớp đang tuyển')
@section('description', 'Tìm lớp phù hợp theo khu vực, môn học, khối lớp và hình thức học.')

@section('content')
<div class="space-y-6">
    <form method="GET" action="{{ route('tutor.open-classes') }}" class="card card-body grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="field">
            <label for="area" class="field-label">Khu vực</label>
            <input id="area" name="area" value="{{ request('area') }}" class="input" placeholder="Ví dụ: Cầu Giấy">
        </div>
        <div class="field">
            <label for="subject" class="field-label">Môn học</label>
            <input id="subject" name="subject" value="{{ request('subject') }}" class="input" placeholder="Ví dụ: Toán">
        </div>
        <div class="field">
            <label for="grade_level" class="field-label">Khối lớp</label>
            <input id="grade_level" name="grade_level" value="{{ request('grade_level') }}" class="input" placeholder="Ví dụ: Lớp 9">
        </div>
        <div class="field">
            <label for="learning_format" class="field-label">Hình thức học</label>
            <select id="learning_format" name="learning_format" class="input">
                <option value="">Tất cả hình thức</option>
                @foreach (['Trực tiếp', 'Online', 'Kết hợp'] as $format)
                    <option value="{{ $format }}" @selected(request('learning_format') === $format)>{{ $format }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap justify-end gap-2 md:col-span-2 xl:col-span-4">
            <a href="{{ route('tutor.open-classes') }}" class="btn btn-outline">Xóa bộ lọc</a>
            <button type="submit" class="btn btn-primary">Tìm lớp</button>
        </div>
    </form>

    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">Tìm thấy {{ $classes->total() }} lớp đang tuyển</p>
        <a href="{{ route('tutor.classes.index') }}" class="text-sm font-medium text-primary hover:underline">Lớp của tôi →</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse ($classes as $class)
            <a href="{{ route('tutor.open-classes.show', $class->class_id) }}" class="card block p-5 transition-colors hover:border-primary/40">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">Lớp #{{ $class->class_id }} · {{ $class->learning_format }}</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ $class->subject }} – {{ $class->grade_level }}</h2>
                    </div>
                    <x-status-badge tone="success">Đang tuyển</x-status-badge>
                </div>
                <div class="mt-3 grid gap-2 text-sm text-muted-foreground sm:grid-cols-2">
                    <p>{{ $class->sessions_per_week }} buổi/tuần</p>
                    <p>{{ number_format((float) $class->fee_per_session, 0, ',', '.') }} đ/buổi</p>
                    <p class="sm:col-span-2">{{ $class->location ?: 'Chưa cập nhật địa điểm' }}</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t pt-3">
                    <span class="text-sm text-muted-foreground">Phí nhận lớp</span>
                    <strong class="text-base text-primary">{{ number_format((float) $class->enrollment_fee, 0, ',', '.') }} đ</strong>
                </div>
            </a>
        @empty
            <div class="card p-10 text-center text-sm text-muted-foreground lg:col-span-2">
                Không tìm thấy lớp đang tuyển phù hợp với điều kiện tìm kiếm.
            </div>
        @endforelse
    </div>

    @if ($classes->hasPages())
        <div>{{ $classes->links() }}</div>
    @endif
</div>
@endsection
