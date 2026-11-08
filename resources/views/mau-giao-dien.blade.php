{{--
    TRANG XEM MẪU GIAO DIỆN (chỉ để xem, không phải trang của website)
    Đặt tại: resources/views/mau-giao-dien.blade.php
    Mở:  /mau-giao-dien                → giao diện khách hàng (xem header, footer)
         /mau-giao-dien?layout=admin   → giao diện admin (xem sidebar, topbar)
         /mau-giao-dien?layout=tutor   → giao diện gia sư
--}}
@php
    $mode    = request('layout');
    $isPanel = in_array($mode, ['admin', 'tutor'], true);
    $layout  = $isPanel ? 'layouts.panel' : 'layouts.public';
@endphp
@extends($layout, ['panel' => $mode === 'tutor' ? 'tutor' : 'admin'])

@section('title', 'Mẫu giao diện')
@section('heading', 'Mẫu giao diện')
@section('description', 'Tất cả thành phần dùng chung. Bấm thử các nút để xem popup và thông báo.')
@section('breadcrumb')
    <a href="{{ url('/mau-giao-dien') }}">Mẫu giao diện</a> / <span class="font-medium text-foreground">Thành phần</span>
@endsection
@section('actions')
    <button type="button" class="btn btn-primary" data-modal-open="mau-popup">+ Nút ở góc tiêu đề</button>
@endsection

@section('content')
<div class="{{ $isPanel ? '' : 'container-page py-10' }} space-y-10">

    {{-- ===== Chuyển kiểu giao diện ===== --}}
    <section class="card card-body">
        <p class="text-sm font-medium">Đang xem giao diện:
            <strong class="text-primary">{{ $mode === 'tutor' ? 'Gia sư' : ($mode === 'admin' ? 'Admin' : 'Khách hàng') }}</strong>
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ url('/mau-giao-dien') }}" class="btn btn-outline btn-sm">Khách hàng</a>
            <a href="{{ url('/mau-giao-dien?layout=admin') }}" class="btn btn-outline btn-sm">Admin</a>
            <a href="{{ url('/mau-giao-dien?layout=tutor') }}" class="btn btn-outline btn-sm">Gia sư</a>
        </div>
    </section>

    {{-- ===== 1. Màu sắc ===== --}}
    <section>
        <h2 class="card-title mb-3">1. Màu sắc</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ([
                ['bg-primary', 'primary'], ['bg-secondary', 'secondary'], ['bg-muted', 'muted'],
                ['bg-success', 'success'], ['bg-warning', 'warning'], ['bg-danger', 'danger'],
                ['bg-success-soft', 'success-soft'], ['bg-warning-soft', 'warning-soft'], ['bg-danger-soft', 'danger-soft'],
                ['bg-info-soft', 'info-soft'], ['bg-card border', 'card'], ['bg-background border', 'background'],
            ] as [$cls, $name])
                <div class="card p-2">
                    <div class="h-12 rounded-md {{ $cls }}"></div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ $name }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===== 2. Nút ===== --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">2. Nút</h2></header>
        <div class="card-body space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <button class="btn btn-primary">Primary</button>
                <button class="btn btn-secondary">Secondary</button>
                <button class="btn btn-outline">Outline</button>
                <button class="btn btn-ghost">Ghost</button>
                <button class="btn btn-danger">Danger</button>
                <button class="btn btn-primary" disabled>Vô hiệu</button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button class="btn btn-primary btn-sm">Nhỏ</button>
                <button class="btn btn-primary">Mặc định</button>
                <button class="btn btn-primary btn-lg">Lớn</button>
                <button class="btn btn-outline btn-icon" aria-label="Thông báo"><x-icon name="bell" class="size-5" /></button>
                <button class="btn btn-outline"><x-icon name="mail" /> Có icon</button>
            </div>
        </div>
    </section>

    {{-- ===== 3. Form ===== --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">3. Form</h2></header>
        <div class="card-body grid gap-4 md:grid-cols-2">
            <div class="field">
                <label for="m-name" class="field-label">Họ tên <span class="req">*</span></label>
                <input id="m-name" class="input" placeholder="Nguyễn Văn A">
                <p class="field-hint">Gợi ý nhỏ dưới ô nhập.</p>
            </div>
            <div class="field">
                <label for="m-phone" class="field-label">Số điện thoại <span class="req">*</span></label>
                <input id="m-phone" class="input is-invalid" value="0912">
                <p class="field-error" role="alert">Số điện thoại phải có 10 số.</p>
            </div>
            <div class="field">
                <label for="m-sub" class="field-label">Môn học</label>
                <select id="m-sub" class="input">
                    <option>Toán</option><option>Ngữ văn</option><option>Tiếng Anh</option>
                </select>
            </div>
            <div class="field">
                <label for="m-dis" class="field-label">Ô bị vô hiệu</label>
                <input id="m-dis" class="input" value="Không sửa được" disabled>
            </div>
            <div class="field md:col-span-2">
                <label for="m-note" class="field-label">Ghi chú</label>
                <textarea id="m-note" class="input" placeholder="Nhập ghi chú..."></textarea>
            </div>
        </div>
    </section>

    {{-- ===== 4. Nhãn trạng thái ===== --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">4. Nhãn trạng thái</h2></header>
        <div class="card-body flex flex-wrap gap-2">
            <x-status-badge tone="success">success: Đang dạy</x-status-badge>
            <x-status-badge tone="warning">warning: Yêu cầu mới</x-status-badge>
            <x-status-badge tone="danger">danger: Đã hủy</x-status-badge>
            <x-status-badge tone="info">info: Đang tuyển</x-status-badge>
            <x-status-badge tone="neutral">neutral: Bản nháp</x-status-badge>
        </div>
    </section>

    {{-- ===== 5. Thông báo trong trang ===== --}}
    <section class="space-y-2">
        <h2 class="card-title">5. Thông báo trong trang</h2>
        <div class="notice notice-info">Thông tin: yêu cầu đang được tư vấn.</div>
        <div class="notice notice-success">Thành công: hồ sơ đã được duyệt.</div>
        <div class="notice notice-warning">Cảnh báo: có 2 yêu cầu sắp quá hạn xử lý.</div>
        <div class="notice notice-danger">Lỗi: tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.</div>
    </section>

    {{-- ===== 6. Bảng ===== --}}
    <section class="card">
        <header class="card-header">
            <div>
                <h2 class="card-title">6. Bảng dữ liệu</h2>
                <p class="card-desc">Cuộn ngang được trên màn hình nhỏ.</p>
            </div>
            <button class="btn btn-outline btn-sm">Xuất báo cáo</button>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Khách hàng</th><th>Môn học</th><th>Khu vực</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Phạm Hồng Nhung</td><td>Toán 9</td><td>Cầu Giấy</td>
                        <td><x-status-badge tone="warning">Yêu cầu mới</x-status-badge></td>
                        <td class="text-right"><button class="btn btn-outline btn-sm">Xem</button></td>
                    </tr>
                    <tr>
                        <td>Nguyễn Văn A</td><td>Tiếng Anh 7</td><td>Hà Đông</td>
                        <td><x-status-badge tone="info">Đã xác nhận</x-status-badge></td>
                        <td class="text-right"><button class="btn btn-outline btn-sm">Xem</button></td>
                    </tr>
                    <tr>
                        <td>Trần Thị B</td><td>Ngữ văn 12</td><td>Đống Đa</td>
                        <td><x-status-badge tone="success">Đã tạo lớp</x-status-badge></td>
                        <td class="text-right"><button class="btn btn-outline btn-sm">Xem</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ===== 7. Popup & Toast ===== --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">7. Popup và thông báo nhỏ (toast)</h2></header>
        <div class="card-body space-y-4">
            <div>
                <p class="mb-2 text-sm font-medium">Popup xác nhận</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-danger"
                        data-confirm data-confirm-tone="danger"
                        data-confirm-title="Xóa yêu cầu"
                        data-confirm-message="Yêu cầu sẽ bị xóa khỏi danh sách quản lý."
                        data-confirm-action="#" data-confirm-method="DELETE"
                        data-confirm-label="Xóa" data-confirm-reason="Lý do xóa">Xóa (có nhập lý do)</button>
                    <button type="button" class="btn btn-outline"
                        data-confirm
                        data-confirm-title="Ẩn bài viết"
                        data-confirm-message="Bài viết sẽ không còn hiển thị trên website."
                        data-confirm-action="#" data-confirm-label="Ẩn bài viết">Ẩn bài viết (không lý do)</button>
                </div>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium">Popup có form riêng</p>
                <button type="button" class="btn btn-primary" data-modal-open="mau-popup">Mở popup thêm yêu cầu</button>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium">Toast</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline" onclick="toast('Thêm yêu cầu thành công')">Thành công</button>
                    <button type="button" class="btn btn-outline" onclick="toast('Không thể xóa lớp đã có gia sư nhận', 'error')">Lỗi</button>
                    <button type="button" class="btn btn-outline" onclick="toast('Đã gửi thông báo cho gia sư', 'info')">Thông tin</button>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== 8. Icon ===== --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">8. Icon có sẵn</h2></header>
        <div class="card-body grid grid-cols-4 gap-4 sm:grid-cols-8">
            @foreach (['menu', 'x', 'bell', 'logout', 'cap', 'pin', 'mail', 'phone'] as $name)
                <div class="flex flex-col items-center gap-2 text-muted-foreground">
                    <x-icon :name="$name" class="size-6" />
                    <span class="text-xs">{{ $name }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>

{{-- Popup có form riêng (mở bằng data-modal-open="mau-popup") --}}
<dialog id="mau-popup" class="modal">
    <div class="modal-header">
        <h2 class="modal-title">Thêm yêu cầu mới</h2>
        <p class="modal-desc">Nhập thông tin sau khi đã tư vấn khách hàng.</p>
    </div>
    <div class="modal-body space-y-4">
        <div class="field">
            <label for="p-name" class="field-label">Họ tên <span class="req">*</span></label>
            <input id="p-name" class="input" placeholder="Nguyễn Văn A">
        </div>
        <div class="field">
            <label for="p-sub" class="field-label">Môn học</label>
            <select id="p-sub" class="input"><option>Toán</option><option>Ngữ văn</option></select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Hủy</button>
        <button type="button" class="btn btn-primary" data-modal-close onclick="toast('Thêm yêu cầu thành công')">Lưu</button>
    </div>
</dialog>
@endsection
