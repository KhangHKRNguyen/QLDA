<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563EB">
    <title>@hasSection('title')@yield('title') | @endif{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap&subset=vietnamese" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @yield('body')

    {{-- ===== POPUP XÁC NHẬN DÙNG CHUNG (điều khiển bằng data-confirm-*, xem app.js) ===== --}}
    <dialog id="confirm-modal" class="modal" aria-labelledby="confirm-title">
        <form id="confirm-form" method="POST" action="#" novalidate>
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-header">
                <h2 id="confirm-title" class="modal-title">Xác nhận</h2>
                <p id="confirm-message" class="modal-desc"></p>
            </div>
            <div id="confirm-reason-box" class="modal-body" hidden>
                <div class="field">
                    <label id="confirm-reason-label" for="confirm-reason" class="field-label">Lý do</label>
                    <textarea id="confirm-reason" name="reason" class="input" rows="3" placeholder="Nhập lý do (tối thiểu 5 ký tự)"></textarea>
                    <p id="confirm-reason-error" class="field-error" role="alert" hidden>Vui lòng nhập lý do (tối thiểu 5 ký tự).</p>
                </div>
            </div>
            <div class="modal-footer mt-4">
                <button type="button" class="btn btn-outline" data-modal-close>Hủy</button>
                <button type="submit" id="confirm-submit" class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </dialog>

    {{-- ===== TOAST: flash message từ server ===== --}}
    <div id="toast-root" aria-live="polite"></div>
    @foreach (['success', 'error', 'info'] as $type)
        @if (session($type))
            <span hidden data-flash="{{ $type }}">{{ session($type) }}</span>
        @endif
    @endforeach
    @if ($errors->any())
        <span hidden data-flash="error">Vui lòng kiểm tra lại thông tin đã nhập.</span>
    @endif

    @stack('scripts')
</body>
</html>
