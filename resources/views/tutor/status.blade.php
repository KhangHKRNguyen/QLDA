{{-- Gia sư xem trạng thái hồ sơ, bổ sung hồ sơ, xóa tài khoản (quy trình 2.1.5) --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Trạng thái tài khoản')
@section('heading', 'Trạng thái tài khoản')
@section('description', 'Theo dõi kết quả xác minh hồ sơ của bạn.')

@section('content')
@php
    $needs = $docs->where('review_status', 'needs_supplement');
    $reason = $docs->whereNotNull('processing_reason')->first()?->processing_reason;
@endphp
<div class="space-y-6">

    <section class="card card-body flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold">{{ $account->full_name }}</p>
            <p class="text-sm text-muted-foreground">{{ $account->email }} · {{ $account->phone_number }}</p>
        </div>
        <x-status-badge :tone="$account->statusTone()">{{ $account->statusLabel() }}</x-status-badge>
    </section>

    {{-- Thông báo theo trạng thái --}}
    @switch($account->status)
        @case('pending_verification')
            <div class="notice notice-info">Hồ sơ của bạn đang chờ Trung tâm xác minh. Bạn sẽ nhận được thông báo khi có kết quả.</div>
            @break
        @case('supplement_required')
            <div class="notice notice-warning">
                Hồ sơ cần bổ sung/chỉnh sửa
                @if ($deadline) trước ngày <strong>{{ $deadline->format('d/m/Y') }}</strong> @endif.
                Quá hạn đăng ký sẽ bị hủy.
                @if ($reason)<br>Lý do: {{ $reason }}@endif
            </div>
            @break
        @case('active')
            <div class="notice notice-success">Tài khoản đã được kích hoạt. Bạn có thể xem và nhận lớp.</div>
            @break
        @case('rejected')
        @case('cancelled')
            <div class="notice notice-danger">
                {{ $account->status === 'rejected' ? 'Hồ sơ đăng ký không được duyệt.' : 'Đăng ký đã bị hủy do quá hạn bổ sung hồ sơ.' }}
                @if ($reason)<br>Lý do: {{ $reason }}@endif
                <br><a href="{{ route('tutor.register') }}" class="font-medium underline">Đăng ký lại</a>
            </div>
            @break
    @endswitch

    {{-- Danh sách giấy tờ + form bổ sung --}}
    <section class="card">
        <header class="card-header"><h2 class="card-title">Hồ sơ xác minh</h2></header>

        @if ($account->status === 'supplement_required' && $needs->isNotEmpty())
            <form method="POST" action="{{ route('tutor.supplement') }}" enctype="multipart/form-data">
                @csrf
        @endif

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Giấy tờ</th><th>Kết quả</th><th>Ghi chú của Trung tâm</th></tr></thead>
                <tbody>
                    @forelse ($docs as $doc)
                        <tr>
                            <td class="font-medium">{{ $doc->typeLabel() }}</td>
                            <td><x-status-badge :tone="$doc->statusTone()">{{ $doc->statusLabel() }}</x-status-badge></td>
                            <td>
                                @if ($doc->processing_reason)<span class="block">{{ $doc->processing_reason }}</span>@endif
                                @if ($doc->review_status === 'needs_supplement' && $account->status === 'supplement_required')
                                    <div class="field mt-2">
                                        <label for="sup-{{ $doc->verification_profile_id }}" class="field-hint">Tải tệp thay thế (hạn {{ $doc->supplement_deadline?->format('d/m/Y') }})</label>
                                        <input id="sup-{{ $doc->verification_profile_id }}" type="file" name="documents[{{ $doc->verification_profile_id }}]"
                                               accept=".jpg,.jpeg,.png,.pdf" class="input h-auto py-2 @error('documents.' . $doc->verification_profile_id) is-invalid @enderror">
                                        @error('documents.' . $doc->verification_profile_id) <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-muted-foreground">Chưa có giấy tờ nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($account->status === 'supplement_required' && $needs->isNotEmpty())
                <div class="flex justify-end border-t p-4">
                    <button type="submit" class="btn btn-primary">Gửi hồ sơ bổ sung</button>
                </div>
            </form>
        @endif
    </section>

    {{-- Xóa tài khoản --}}
    @if (in_array($account->status, ['active', 'locked'], true))
        <section class="card">
            <header class="card-header">
                <div>
                    <h2 class="card-title">Xóa tài khoản</h2>
                    <p class="card-desc">Chỉ xóa được khi không còn lớp đang dạy, không nợ phí và không có yêu cầu hoàn phí đang chờ xử lý.</p>
                </div>
            </header>
            <div class="card-body space-y-3">
                @if ($blockers)
                    <div class="notice notice-warning">Chưa đủ điều kiện xóa: {{ implode('; ', $blockers) }}.</div>
                @endif
                <button type="button" class="btn btn-danger" @disabled($blockers)
                    data-confirm data-confirm-tone="danger"
                    data-confirm-title="Xóa tài khoản"
                    data-confirm-message="Tài khoản sẽ ngừng hoạt động và bạn không thể nhận lớp nữa."
                    data-confirm-action="{{ route('tutor.account.destroy') }}"
                    data-confirm-method="DELETE"
                    data-confirm-label="Xóa tài khoản"
                    data-confirm-reason="Lý do ngừng dạy">Xóa tài khoản</button>
            </div>
        </section>
    @endif
</div>
@endsection
