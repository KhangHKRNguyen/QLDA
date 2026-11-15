{{-- Chi tiết tài khoản — tạm khóa / đình chỉ / mở khóa / vô hiệu hóa (xóa) --}}
@php
    $id         = $account->account_id;
    $label      = $account->statusLabel();
    $canLock    = $account->status === 'active';
    $canSuspend = in_array($account->status, ['active', 'locked'], true);
    $canDelete  = in_array($account->status, ['active', 'locked'], true);
    $conditions = [
        [$checks['active_classes'] <= 0, 'Không có lớp đang dạy', "Còn {$checks['active_classes']} lớp đang dạy"],
        [$checks['unpaid_fees'] <= 0, 'Không nợ phí', 'Còn nợ phí ' . number_format($checks['unpaid_fees'], 0, ',', '.') . ' đ'],
        [$checks['pending_refunds'] <= 0, 'Không có yêu cầu hoàn phí chờ xử lý', "Còn {$checks['pending_refunds']} yêu cầu hoàn phí chờ xử lý"],
    ];
    $eligible = collect($conditions)->every(fn ($c) => $c[0]);
    $inVerification = in_array($account->status, ['incomplete', 'pending_verification', 'supplement_required', 'rejected'], true);
@endphp
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', $account->full_name . ' – Tài khoản')
@section('heading', $account->full_name)
@section('description', 'Mã tài khoản #' . $account->account_id . ' · Thông tin hồ sơ và lịch sử hoạt động.')
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> /
    <a href="{{ route('admin.accounts.index') }}">Tài khoản</a> /
    <span class="font-medium text-foreground">{{ $account->username }}</span>
@endsection
@section('actions')
    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline"><x-icon name="back" /> Danh sách</a>
    @if ($canLock)
        <button type="button" class="btn btn-secondary"
            data-lock-action="{{ route('admin.accounts.lock', $id) }}"
            data-lock-name="{{ $account->full_name }}">
            <x-icon name="lock" /> Tạm khóa
        </button>
    @endif
    @if ($canSuspend)
        <button type="button" class="btn btn-danger"
            data-confirm data-confirm-tone="danger"
            data-confirm-title="Đình chỉ vĩnh viễn"
            data-confirm-message="Tài khoản {{ $account->full_name }} sẽ bị khóa vĩnh viễn và KHÔNG THỂ khôi phục."
            data-confirm-action="{{ route('admin.accounts.suspend', $id) }}"
            data-confirm-reason="Lý do đình chỉ"
            data-confirm-label="Đình chỉ">
            <x-icon name="ban" /> Đình chỉ
        </button>
    @endif
@endsection

@section('content')
<div class="space-y-6">

    {{-- ===== Thông báo theo trạng thái ===== --}}
    @if ($account->status === 'locked')
        <div class="notice notice-warning flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-semibold">Tài khoản đang bị tạm khóa
                    @if ($account->lock_expires_at)
                        đến {{ $account->lock_expires_at->format('d/m/Y H:i') }}
                        @if ($account->lock_expires_at->isFuture())
                            (còn {{ (int) ceil(now()->diffInDays($account->lock_expires_at)) }} ngày)
                        @endif
                    @endif
                </p>
                <p class="mt-1">Lý do: {{ $account->lock_reason ?: '—' }}</p>
                <p class="mt-1 text-xs opacity-80">Hệ thống sẽ tự động mở khóa và gửi thông báo khi hết thời hạn.</p>
            </div>
            <button type="button" class="btn btn-outline btn-sm"
                data-confirm
                data-confirm-title="Mở khóa tài khoản"
                data-confirm-message="Tài khoản {{ $account->full_name }} sẽ hoạt động trở lại trước thời hạn và người dùng nhận được thông báo."
                data-confirm-action="{{ route('admin.accounts.unlock', $id) }}"
                data-confirm-label="Mở khóa">
                <x-icon name="unlock" /> Mở khóa ngay
            </button>
        </div>
    @elseif ($account->status === 'suspended')
        <div class="notice notice-danger">
            <p class="font-semibold">Tài khoản đã bị đình chỉ vĩnh viễn và không thể khôi phục.</p>
            <p class="mt-1">Lý do: {{ $account->lock_reason ?: '—' }}</p>
        </div>
    @elseif ($account->status === 'inactive')
        <p class="notice notice-info">Tài khoản đã ngừng hoạt động. Dữ liệu lớp học và giao dịch vẫn được lưu trữ.</p>
    @elseif ($inVerification)
        <p class="notice notice-info">
            Tài khoản đang trong quy trình đăng ký/xác minh hồ sơ ({{ $label }}).
            Duyệt hồ sơ tại <a href="{{ url('/admin/gia-su') }}" class="font-medium underline">Tài khoản gia sư</a>.
        </p>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ===== Cột trái: thông tin tài khoản ===== --}}
        <aside class="card h-fit">
            <div class="card-body flex flex-col items-center text-center">
                <span class="flex size-16 items-center justify-center rounded-full bg-secondary text-2xl font-semibold text-secondary-foreground">{{ $account->initial }}</span>
                <p class="mt-3 text-lg font-semibold">{{ $account->full_name }}</p>
                <p class="text-sm text-muted-foreground">{{ $account->username }} · {{ $account->role_name }}</p>
                <x-status-badge :tone="$account->statusTone()" class="mt-3">{{ $label }}</x-status-badge>
            </div>
            <dl class="divide-y border-t text-sm">
                @foreach ([
                    ['Email', $account->email],
                    ['Số điện thoại', $account->phone_number],
                    ['Ngày sinh', $account->date_of_birth?->format('d/m/Y')],
                    ['Giới tính', $account->gender],
                    ['Địa chỉ', $account->address],
                    ['Khu vực', $account->district],
                    ['Cập nhật trạng thái', $account->status_updated_at?->format('d/m/Y H:i')],
                ] as [$k, $v])
                    <div class="flex justify-between gap-4 px-5 py-3">
                        <dt class="shrink-0 text-muted-foreground">{{ $k }}</dt>
                        <dd class="text-right font-medium break-all">{{ $v ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </aside>

        <div class="space-y-6 lg:col-span-2">

            {{-- ===== Lịch sử hoạt động (activity_logs) ===== --}}
            <section class="card">
                <header class="card-header"><h2 class="card-title">Lịch sử hoạt động</h2></header>
                @if ($logs->isEmpty())
                    <p class="card-body text-sm text-muted-foreground">Chưa có hoạt động nào được ghi nhận.</p>
                @else
                    <ol class="card-body space-y-4">
                        @foreach ($logs as $log)
                            <li class="relative flex gap-3">
                                @unless ($loop->last)
                                    <span class="absolute top-4 left-[0.3rem] h-full w-px bg-border" aria-hidden="true"></span>
                                @endunless
                                <span class="relative mt-1.5 size-2.5 shrink-0 rounded-full bg-primary ring-4 ring-secondary"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">{{ $log->action }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ $log->occurred_at?->format('d/m/Y H:i') }}
                                        @if ($log->account_id != $id && $log->account)
                                            · bởi {{ $log->account->username }}
                                        @endif
                                        @if ($log->affected_object && $log->affected_object !== 'accounts#' . $id)
                                            · {{ $log->affected_object }}
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>

            {{-- ===== Vô hiệu hóa (biểu đồ XoaTaiKhoan) ===== --}}
            @if ($canDelete)
                <section class="card border-danger/30">
                    <header class="card-header">
                        <div>
                            <h2 class="card-title text-danger">Vô hiệu hóa tài khoản</h2>
                            <p class="card-desc">Tài khoản chuyển sang "Ngừng hoạt động"; dữ liệu lớp học và giao dịch vẫn được giữ lại.</p>
                        </div>
                    </header>
                    <div class="card-body space-y-4">
                        <ul class="space-y-2 text-sm">
                            @foreach ($conditions as [$ok, $okText, $failText])
                                <li class="flex items-center gap-2">
                                    <span @class(['flex size-5 items-center justify-center rounded-full',
                                        'bg-success-soft text-success' => $ok, 'bg-danger-soft text-danger' => ! $ok])>
                                        <x-icon :name="$ok ? 'check' : 'x'" class="size-3.5" />
                                    </span>
                                    <span @class(['text-danger font-medium' => ! $ok])>{{ $ok ? $okText : $failText }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" class="btn btn-danger" @disabled(! $eligible)
                                data-confirm data-confirm-tone="danger"
                                data-confirm-title="Vô hiệu hóa tài khoản"
                                data-confirm-message="Tài khoản {{ $account->full_name }} sẽ chuyển sang trạng thái Ngừng hoạt động."
                                data-confirm-action="{{ route('admin.accounts.destroy', $id) }}"
                                data-confirm-method="DELETE"
                                data-confirm-reason="Lý do vô hiệu hóa"
                                data-confirm-label="Vô hiệu hóa">
                                <x-icon name="trash" /> Vô hiệu hóa
                            </button>
                            @unless ($eligible)
                                <span class="text-xs text-muted-foreground">Chưa đủ điều kiện, cần xử lý các mục trên trước.</span>
                            @endunless
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>

@include('admin.accounts._lock-modal')
@endsection
