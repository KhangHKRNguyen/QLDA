{{-- Gia sư đăng ký tài khoản + nộp hồ sơ xác minh (quy trình 2.1.5, HĐ1–HĐ2) --}}
@extends('layouts.public')
@section('title', 'Đăng ký làm gia sư')

@section('content')
<section class="container-page max-w-3xl py-10">
    <h1 class="page-title">Đăng ký làm gia sư</h1>
    <p class="page-desc">Điền thông tin và tải giấy tờ để Trung tâm xác minh. Email của bạn sẽ là tên đăng nhập.</p>

    <form method="POST" action="{{ route('tutor.register.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6" novalidate>
        @csrf

        <section class="card">
            <header class="card-header"><h2 class="card-title">1. Thông tin cá nhân</h2></header>
            <div class="card-body grid gap-4 md:grid-cols-2">
                <div class="field md:col-span-2">
                    <label for="full_name" class="field-label">Họ tên <span class="req">*</span></label>
                    <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="input @error('full_name') is-invalid @enderror" autocomplete="name">
                    @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="phone_number" class="field-label">Số điện thoại <span class="req">*</span></label>
                    <input id="phone_number" name="phone_number" value="{{ old('phone_number') }}" class="input @error('phone_number') is-invalid @enderror" inputmode="numeric" maxlength="10" autocomplete="tel">
                    @error('phone_number') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="email" class="field-label">Email (dùng để đăng nhập) <span class="req">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" autocomplete="email">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="date_of_birth" class="field-label">Ngày sinh <span class="req">*</span></label>
                    <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="input @error('date_of_birth') is-invalid @enderror">
                    @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="gender" class="field-label">Giới tính <span class="req">*</span></label>
                    <select id="gender" name="gender" class="input @error('gender') is-invalid @enderror">
                        <option value="">-- Chọn --</option>
                        @foreach (['Nam', 'Nữ', 'Khác'] as $g)
                            <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field md:col-span-2">
                    <label for="address" class="field-label">Địa chỉ <span class="req">*</span></label>
                    <input id="address" name="address" value="{{ old('address') }}" class="input @error('address') is-invalid @enderror" autocomplete="street-address">
                    @error('address') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field md:col-span-2">
                    <label for="district" class="field-label">Khu vực có thể dạy (quận/huyện)</label>
                    <input id="district" name="district" value="{{ old('district') }}" class="input @error('district') is-invalid @enderror">
                    @error('district') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password" class="field-label">Mật khẩu <span class="req">*</span></label>
                    <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" autocomplete="new-password">
                    <p class="field-hint">Ít nhất 8 ký tự.</p>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="password_confirmation" class="field-label">Nhập lại mật khẩu <span class="req">*</span></label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password">
                </div>
            </div>
        </section>

        <section class="card">
            <header class="card-header">
                <div>
                    <h2 class="card-title">2. Hồ sơ xác minh</h2>
                    <p class="card-desc">Tệp JPG, PNG hoặc PDF, tối đa 5 MB mỗi tệp.</p>
                </div>
            </header>
            <div class="card-body grid gap-4">
                @foreach ($types as $type => [$label, $required])
                    <div class="field">
                        <label for="doc-{{ $type }}" class="field-label">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
                        <input id="doc-{{ $type }}" type="file" name="documents[{{ $type }}]" accept=".jpg,.jpeg,.png,.pdf"
                               class="input h-auto py-2 @error("documents.$type") is-invalid @enderror">
                        @error("documents.$type") <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ url('/') }}" class="btn btn-outline">Hủy</a>
            <button type="submit" class="btn btn-primary">Gửi hồ sơ đăng ký</button>
        </div>
    </form>
</section>
@endsection
