{{-- Thêm tài khoản (vai trò Quản lý) --}}
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Thêm tài khoản')
@section('heading', 'Thêm tài khoản')
@section('description', 'Tạo tài khoản đăng nhập cho nhân viên hoặc gia sư. Tài khoản mới ở trạng thái Hoạt động.')
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> /
    <a href="{{ route('admin.accounts.index') }}">Tài khoản</a> /
    <span class="font-medium text-foreground">Thêm mới</span>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.accounts.store') }}" class="space-y-6" novalidate>
    @csrf

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- ===== Thông tin cá nhân (bảng people) ===== --}}
        <section class="card">
            <header class="card-header"><h2 class="card-title">Thông tin cá nhân</h2></header>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['full_name', 'Họ tên', 'text', true, 'Nguyễn Văn A', 'sm:col-span-2'],
                    ['phone_number', 'Số điện thoại', 'tel', true, '09xxxxxxxx', ''],
                    ['email', 'Email', 'email', true, 'ten@gmail.com', ''],
                    ['date_of_birth', 'Ngày sinh', 'date', false, '', ''],
                ] as [$name, $label, $type, $required, $placeholder, $span])
                    <div class="field {{ $span }}">
                        <label for="{{ $name }}" class="field-label">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}"
                               placeholder="{{ $placeholder }}" @class(['input', 'is-invalid' => $errors->has($name)])
                               @required($required) @error($name) aria-describedby="{{ $name }}-error" @enderror>
                        @error($name)<p id="{{ $name }}-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="field">
                    <label for="gender" class="field-label">Giới tính</label>
                    <select id="gender" name="gender" @class(['input', 'is-invalid' => $errors->has('gender')])>
                        <option value="">— Chọn —</option>
                        @foreach (['Nam', 'Nữ', 'Khác'] as $g)
                            <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                    @error('gender')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="field sm:col-span-2">
                    <label for="district" class="field-label">Khu vực</label>
                    <input id="district" name="district" value="{{ old('district') }}" placeholder="VD: Cầu Giấy"
                           @class(['input', 'is-invalid' => $errors->has('district')])>
                    @error('district')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="field sm:col-span-2">
                    <label for="address" class="field-label">Địa chỉ</label>
                    <textarea id="address" name="address" rows="2" placeholder="Số nhà, đường, phường..."
                              @class(['input', 'is-invalid' => $errors->has('address')])>{{ old('address') }}</textarea>
                    @error('address')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- ===== Thông tin đăng nhập (bảng accounts) ===== --}}
        <section class="card h-fit">
            <header class="card-header"><h2 class="card-title">Thông tin đăng nhập</h2></header>
            <div class="card-body grid gap-4">
                <div class="field">
                    <label for="role_id" class="field-label">Vai trò <span class="req">*</span></label>
                    <select id="role_id" name="role_id" required @class(['input', 'is-invalid' => $errors->has('role_id')])>
                        <option value="">— Chọn vai trò —</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->role_id }}" @selected(old('role_id') == $role->role_id)>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="username" class="field-label">Tên đăng nhập <span class="req">*</span></label>
                    <input id="username" name="username" value="{{ old('username') }}" required autocomplete="off"
                           placeholder="VD: qlkh02" @class(['input', 'is-invalid' => $errors->has('username')])>
                    @error('username')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @else
                        <p class="field-hint">Chữ không dấu, số, dấu - và _, tối thiểu 4 ký tự.</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password" class="field-label">Mật khẩu <span class="req">*</span></label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                           @class(['input', 'is-invalid' => $errors->has('password')])>
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @else
                        <p class="field-hint">Tối thiểu 8 ký tự.</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation" class="field-label">Nhập lại mật khẩu <span class="req">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                </div>
            </div>
        </section>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline">Hủy</a>
        <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
    </div>
</form>
@endsection
