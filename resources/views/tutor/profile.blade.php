{{-- Gia sư: hồ sơ cá nhân + đổi mật khẩu --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Hồ sơ cá nhân')
@section('heading', 'Hồ sơ cá nhân')
@section('description', 'Cập nhật thông tin liên hệ và mật khẩu của bạn.')

@section('content')
<div class="space-y-6">
    <section class="card">
        <header class="card-header"><h2 class="card-title">Thông tin cá nhân</h2></header>
        <form method="POST" action="{{ route('tutor.profile.update') }}" class="card-body grid gap-4 md:grid-cols-2" novalidate>
            @csrf
            @method('PUT')

            <div class="field md:col-span-2">
                <label for="email" class="field-label">Email (tên đăng nhập)</label>
                <input id="email" class="input" value="{{ $person->email }}" disabled>
                <p class="field-hint">Email dùng để đăng nhập nên không thể thay đổi.</p>
            </div>
            <div class="field md:col-span-2">
                <label for="full_name" class="field-label">Họ tên <span class="req">*</span></label>
                <input id="full_name" name="full_name" value="{{ old('full_name', $person->full_name) }}" class="input @error('full_name') is-invalid @enderror">
                @error('full_name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="phone_number" class="field-label">Số điện thoại <span class="req">*</span></label>
                <input id="phone_number" name="phone_number" value="{{ old('phone_number', $person->phone_number) }}" class="input @error('phone_number') is-invalid @enderror" inputmode="numeric" maxlength="10">
                @error('phone_number') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="date_of_birth" class="field-label">Ngày sinh <span class="req">*</span></label>
                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $person->date_of_birth?->format('Y-m-d')) }}" class="input @error('date_of_birth') is-invalid @enderror">
                @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="gender" class="field-label">Giới tính <span class="req">*</span></label>
                <select id="gender" name="gender" class="input @error('gender') is-invalid @enderror">
                    @foreach (['Nam', 'Nữ', 'Khác'] as $g)
                        <option value="{{ $g }}" @selected(old('gender', $person->gender) === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                @error('gender') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="district" class="field-label">Khu vực có thể dạy</label>
                <input id="district" name="district" value="{{ old('district', $person->district) }}" class="input @error('district') is-invalid @enderror">
                @error('district') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field md:col-span-2">
                <label for="address" class="field-label">Địa chỉ <span class="req">*</span></label>
                <input id="address" name="address" value="{{ old('address', $person->address) }}" class="input @error('address') is-invalid @enderror">
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end md:col-span-2">
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </section>

    <section class="card">
        <header class="card-header"><h2 class="card-title">Đổi mật khẩu</h2></header>
        <form method="POST" action="{{ route('tutor.profile.password') }}" class="card-body grid gap-4 md:grid-cols-2" novalidate>
            @csrf
            @method('PUT')

            <div class="field md:col-span-2">
                <label for="current_password" class="field-label">Mật khẩu hiện tại <span class="req">*</span></label>
                <input id="current_password" type="password" name="current_password" class="input @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                @error('current_password', 'updatePassword') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password" class="field-label">Mật khẩu mới <span class="req">*</span></label>
                <input id="password" type="password" name="password" class="input @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                <p class="field-hint">Ít nhất 8 ký tự.</p>
                @error('password', 'updatePassword') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password_confirmation" class="field-label">Nhập lại mật khẩu mới <span class="req">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password">
            </div>
            <div class="flex justify-end md:col-span-2">
                <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
            </div>
        </form>
    </section>
</div>
@endsection
