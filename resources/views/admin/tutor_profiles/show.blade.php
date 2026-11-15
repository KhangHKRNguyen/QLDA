{{-- QLGS: chi tiết hồ sơ + duyệt / yêu cầu bổ sung / từ chối (quy trình 2.1.5, HĐ4–HĐ6) --}}
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Hồ sơ gia sư')
@section('heading', $account->full_name)
@section('description', 'Hồ sơ đăng ký tài khoản gia sư #' . $account->account_id)
@section('breadcrumb')
    <a href="{{ route('admin.tutor-profiles.index') }}">Tài khoản gia sư</a> / <span class="font-medium text-foreground">#{{ $account->account_id }}</span>
@endsection
@section('actions')
    <a href="{{ route('admin.tutor-profiles.index') }}" class="btn btn-outline">← Danh sách</a>
@endsection

@section('content')
@php
    $canReview = $account->status === 'pending_verification';
    $canReject = in_array($account->status, ['pending_verification', 'supplement_required'], true);
@endphp
<div class="space-y-6">
    @if ($errors->any())
        <div class="notice notice-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="card">
        <header class="card-header">
            <h2 class="card-title">Thông tin cá nhân</h2>
            <x-status-badge :tone="$account->statusTone()">{{ $account->statusLabel() }}</x-status-badge>
        </header>
        <dl class="card-body grid gap-4 text-sm sm:grid-cols-2">
            @foreach ([
                'Họ tên' => $account->full_name,
                'Email (tên đăng nhập)' => $account->email,
                'Số điện thoại' => $account->phone_number,
                'Ngày sinh' => $account->date_of_birth?->format('d/m/Y'),
                'Giới tính' => $account->gender,
                'Khu vực' => $account->district,
                'Địa chỉ' => $account->address,
            ] as $label => $value)
                <div>
                    <dt class="text-xs text-muted-foreground">{{ $label }}</dt>
                    <dd class="mt-0.5 font-medium">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="card">
        <header class="card-header"><h2 class="card-title">Giấy tờ xác minh</h2></header>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Giấy tờ</th><th>Tệp</th><th>Kết quả</th><th>Ghi chú / hạn bổ sung</th></tr></thead>
                <tbody>
                    @forelse ($docs as $doc)
                        <tr>
                            <td class="font-medium">{{ $doc->typeLabel() }}</td>
                            <td>
                                @if ($doc->attachment_file)
                                    <a href="{{ route('admin.tutor-profiles.document', $doc->verification_profile_id) }}" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">Xem tệp</a>
                                @else — @endif
                            </td>
                            <td><x-status-badge :tone="$doc->statusTone()">{{ $doc->statusLabel() }}</x-status-badge></td>
                            <td>
                                {{ $doc->processing_reason }}
                                @if ($doc->supplement_deadline)
                                    <span class="block text-xs text-muted-foreground">Hạn: {{ $doc->supplement_deadline->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-muted-foreground">Chưa có giấy tờ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canReview || $canReject)
        <section class="card card-body flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-muted-foreground">
                @if ($canReview) Kiểm tra giấy tờ rồi chọn kết quả xử lý hồ sơ. @else Đang chờ gia sư bổ sung hồ sơ. @endif
            </p>
            <div class="flex flex-wrap gap-2">
                @if ($canReview)
                    <button type="button" class="btn btn-primary"
                        data-confirm
                        data-confirm-title="Duyệt hồ sơ"
                        data-confirm-message="Tài khoản {{ $account->full_name }} sẽ được kích hoạt và có thể nhận lớp."
                        data-confirm-action="{{ route('admin.tutor-profiles.approve', $account->account_id) }}"
                        data-confirm-label="Duyệt hồ sơ">Duyệt hồ sơ</button>
                    <button type="button" class="btn btn-outline" data-modal-open="supplement-modal">Yêu cầu bổ sung</button>
                @endif
                @if ($canReject)
                    <button type="button" class="btn btn-danger"
                        data-confirm data-confirm-tone="danger"
                        data-confirm-title="Từ chối hồ sơ"
                        data-confirm-message="Hồ sơ của {{ $account->full_name }} sẽ bị từ chối và gia sư được thông báo."
                        data-confirm-action="{{ route('admin.tutor-profiles.reject', $account->account_id) }}"
                        data-confirm-label="Từ chối"
                        data-confirm-reason="Lý do từ chối">Từ chối</button>
                @endif
            </div>
        </section>
    @endif

    <section class="card">
        <header class="card-header"><h2 class="card-title">Lịch sử xử lý</h2></header>
        <ul class="divide-y">
            @forelse ($logs as $log)
                <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
                    <span>{{ $log->action }}</span>
                    <span class="text-muted-foreground">{{ \Illuminate\Support\Carbon::parse($log->occurred_at)->format('d/m/Y H:i') }}</span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-muted-foreground">Chưa có lịch sử.</li>
            @endforelse
        </ul>
    </section>
</div>

@if ($canReview)
    <dialog id="supplement-modal" class="modal">
        <form method="POST" action="{{ route('admin.tutor-profiles.supplement', $account->account_id) }}">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title">Yêu cầu bổ sung hồ sơ</h2>
                <p class="modal-desc">Gia sư có {{ \App\Services\TutorRegistrationService::SUPPLEMENT_DAYS }} ngày để bổ sung; quá hạn đăng ký sẽ bị hủy.</p>
            </div>
            <div class="modal-body space-y-4">
                <fieldset class="field">
                    <legend class="field-label">Giấy tờ chưa đạt <span class="req">*</span></legend>
                    @foreach ($docs as $doc)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="documents[]" value="{{ $doc->verification_profile_id }}"> {{ $doc->typeLabel() }}
                        </label>
                    @endforeach
                </fieldset>
                <div class="field">
                    <label for="sup-reason" class="field-label">Lý do / nội dung cần bổ sung <span class="req">*</span></label>
                    <textarea id="sup-reason" name="reason" class="input" rows="3" required minlength="5" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Hủy</button>
                <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
            </div>
        </form>
    </dialog>
@endif
@endsection
