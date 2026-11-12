@extends('layouts.public')

@section('title', 'Đăng ký tìm gia sư')

@section('content')

<section class="container-page py-10">
    <div class="mx-auto max-w-3xl">

        {{-- Tiêu đề --}}
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight">
                Đăng ký tìm gia sư
            </h1>

            <p class="mt-3 text-muted-foreground">
                Vui lòng cung cấp thông tin để trung tâm hỗ trợ tìm gia sư phù hợp.
            </p>
        </div>

        <form
    action="{{ route('customer.tutor-requests.store') }}"
    method="POST"
    class="rounded-2xl border bg-card p-6 shadow-sm md:p-8"
>
    @csrf
            {{-- Thông tin người liên hệ --}}
            <div class="mb-6">
                <h2 class="text-lg font-semibold">
                    1. Thông tin người liên hệ
                </h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    Thông tin phụ huynh hoặc người đăng ký tìm gia sư.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                <div class="field">
                    <label for="full_name" class="field-label">
                        Họ và tên <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="input"
                        placeholder="Nhập họ và tên"
                        value="{{ old('full_name') }}"
                        required
                    >
                </div>

<div class="field">
    <label for="phone_number" class="field-label">
        Số điện thoại <span class="text-red-500">*</span>
    </label>

   <input
    type="tel"
    id="phone_number"
    name="phone_number"
    class="input"
    placeholder="Nhập số điện thoại"
    value="{{ old('phone_number') }}"
    inputmode="numeric"
    minlength="10"
    maxlength="10"
    pattern="(03|05|07|08|09)[0-9]{8}"
    oninvalid="this.setCustomValidity(
        this.validity.valueMissing
        ? 'Vui lòng nhập số điện thoại.'
        : 'Số điện thoại không hợp lệ. Vui lòng nhập 10 chữ số, bắt đầu bằng 03, 05, 07, 08 hoặc 09.'
    )"
    oninput="this.setCustomValidity('')"
    required
>

    @error('phone_number')
        <p class="mt-1 text-sm text-red-500">
            {{ $message }}
        </p>
    @enderror
</div>

                <div class="field">
                    <label for="email" class="field-label">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input"
                        placeholder="Nhập địa chỉ email"
                        value="{{ old('email') }}"
                    >
                </div>

                <div class="field">
                    <label for="district" class="field-label">
                        Quận/Huyện
                    </label>

                    <input
                        type="text"
                        id="district"
                        name="district"
                        class="input"
                        placeholder="Nhập quận/huyện"
                        value="{{ old('district') }}"
                    >
                </div>

                <div class="field md:col-span-2">
                    <label for="address" class="field-label">
                        Địa chỉ
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        class="input"
                        placeholder="Nhập địa chỉ cụ thể"
                        value="{{ old('address') }}"
                    >
                </div>

            </div>

            <div class="my-8 border-t"></div>

            {{-- Thông tin yêu cầu tìm gia sư --}}
            <div class="mb-6">
                <h2 class="text-lg font-semibold">
                    2. Thông tin yêu cầu tìm gia sư
                </h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    Cho trung tâm biết nhu cầu học tập của học sinh.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                <div class="field">
                    <label for="subject" class="field-label">
                        Môn học <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        class="input"
                        placeholder="Ví dụ: Toán, Văn, Tiếng Anh"
                        value="{{ old('subject') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="grade_level" class="field-label">
                        Khối lớp <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="grade_level"
                        name="grade_level"
                        class="input"
                        placeholder="Ví dụ: Lớp 6, Lớp 12"
                        value="{{ old('grade_level') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="learning_format" class="field-label">
                        Hình thức học <span class="text-red-500">*</span>
                    </label>

                    <select
    id="learning_format"
    name="learning_format"
    class="input"
    required
>
    <option value="">-- Chọn hình thức học --</option>
    <option value="Trực tiếp" {{ old('learning_format') == 'Trực tiếp' ? 'selected' : '' }}>
        Trực tiếp
    </option>
    <option value="Trực tuyến" {{ old('learning_format') == 'Trực tuyến' ? 'selected' : '' }}>
        Trực tuyến
    </option>
    <option value="Linh hoạt" {{ old('learning_format') == 'Linh hoạt' ? 'selected' : '' }}>
        Linh hoạt
    </option>
</select>
                </div>

                <div class="field">
                    <label for="sessions_per_week" class="field-label">
                        Số buổi học mỗi tuần <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="sessions_per_week"
                        name="sessions_per_week"
                        class="input"
                        min="1"
                        placeholder="Ví dụ: 3"
                        value="{{ old('sessions_per_week') }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="desired_tuition_fee" class="field-label">
                        Học phí mong muốn (VNĐ/buổi)
                    </label>

                    <input
                        type="number"
                        id="desired_tuition_fee"
                        name="desired_tuition_fee"
                        class="input"
                        min="0"
                        placeholder="Ví dụ: 150000"
                        value="{{ old('desired_tuition_fee') }}"
                    >
                </div>

                <div class="field md:col-span-2">
                    <label for="schedule" class="field-label">
                        Lịch học mong muốn
                    </label>

                    <textarea
                        id="schedule"
                        name="schedule"
                        class="input"
                        rows="3"
                        placeholder="Ví dụ: Tối thứ 2, 4, 6 hoặc cuối tuần..."
                        value="{{old('schedule')}}"
                    ></textarea>
                </div>

                <div class="field md:col-span-2">
                    <label for="notes" class="field-label">
                        Ghi chú thêm
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        class="input"
                        rows="3"
                        placeholder="Nhập yêu cầu khác nếu có..."
                        value="{{old('notes')}}"
                    ></textarea>
                </div>

            </div>

            {{-- Nút gửi --}}
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ url('/') }}" class="btn btn-outline">
                    Quay lại
                </a>

                <button type="submit" class="btn btn-primary">
                    Gửi yêu cầu tìm gia sư
                </button>
                        </div>

        </form>
        @if (session('success'))
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-green-700">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
        <p class="font-semibold">Vui lòng kiểm tra lại thông tin:</p>

        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    </div>
</section>

@endsection