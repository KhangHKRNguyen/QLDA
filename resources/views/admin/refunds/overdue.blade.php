{{-- Giám đốc — "Danh sách yêu cầu quá hạn" (PTTKHT 2.2.9.2 luồng phụ 1, FR-HP-06) --}}
@php
    use App\Models\RefundRequest;
    use App\Services\RefundService;
@endphp
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Hoàn phí quá hạn')
@section('heading', 'Yêu cầu hoàn phí quá hạn')
@section('description', 'Các yêu cầu quá ' . RefundRequest::PROCESSING_DAYS . ' ngày mà Quản lý Trung tâm chưa ra quyết định. Gửi yêu cầu để Quản lý tiếp tục xử lý.')

@section('content')
<div class="space-y-6">
    <section class="card">
        <header class="card-header">
            <h2 class="card-title">Đang quá hạn</h2>
            <span class="text-sm font-semibold {{ $refunds->isEmpty() ? 'text-success' : 'text-danger' }}">{{ $refunds->count() }} yêu cầu</span>
        </header>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Mã</th><th>Lớp / Gia sư</th><th>Lý do</th><th class="text-right">Dự kiến</th><th>Tiếp nhận</th><th>Quá hạn</th><th>Đã nhắc</th><th></th></tr></thead>
                <tbody>
                    @forelse ($refunds as $r)
                        @php
                            $c = $r->payment?->tutorClass;
                            [$times, $last] = $reminders[RefundRequest::objectRef($r)] ?? [0, null];
                        @endphp
                        <tr>
                            <td class="text-muted-foreground">#{{ $r->refund_request_id }}</td>
                            <td class="whitespace-nowrap">
                                <span class="block font-medium">{{ $c ? "{$c->subject} – {$c->grade_level}" : '—' }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $r->payment?->account?->person?->full_name }}</span>
                            </td>
                            <td class="max-w-56"><span class="line-clamp-2 text-sm">{{ $r->reasonLabel() ?? $r->reason }}</span></td>
                            <td class="text-right whitespace-nowrap">{{ RefundService::money($r->expected_refund_amount) }}</td>
                            <td class="whitespace-nowrap text-muted-foreground">{{ $r->received_at->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap font-medium text-danger">{{ abs($r->daysLeft() ?? 0) }} ngày</td>
                            <td class="whitespace-nowrap text-sm text-muted-foreground">
                                {{ $times ? "{$times} lần · " . \Illuminate\Support\Carbon::parse($last)->format('d/m H:i') : 'Chưa' }}
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-primary btn-sm"
                                    data-confirm data-confirm-title="Yêu cầu Quản lý xử lý"
                                    data-confirm-message="Gửi thông báo yêu cầu Quản lý Trung tâm xử lý ngay yêu cầu hoàn phí #{{ $r->refund_request_id }}."
                                    data-confirm-action="{{ route('admin.refunds.overdue.remind', $r->refund_request_id) }}"
                                    data-confirm-reason="Ghi chú cho Quản lý (không bắt buộc)" data-confirm-reason-required="false"
                                    data-confirm-label="Gửi yêu cầu">
                                    <x-icon name="bell" /> Yêu cầu xử lý
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-muted-foreground">Không có yêu cầu hoàn phí nào quá hạn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($resolved->isNotEmpty())
        <section class="card">
            <header class="card-header">
                <div>
                    <h2 class="card-title">Đã được xử lý tiếp</h2>
                    <p class="card-desc">Các yêu cầu từng quá hạn, Quản lý đã ra quyết định sau đó.</p>
                </div>
            </header>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mã</th><th>Lớp / Gia sư</th><th>Trạng thái</th><th class="text-right">Mức hoàn</th><th>Quyết định lúc</th></tr></thead>
                    <tbody>
                        @foreach ($resolved as $r)
                            @php $c = $r->payment?->tutorClass; @endphp
                            <tr>
                                <td class="text-muted-foreground">#{{ $r->refund_request_id }}</td>
                                <td class="whitespace-nowrap">{{ $c ? "{$c->subject} – {$c->grade_level}" : '—' }} · <span class="text-muted-foreground">{{ $r->payment?->account?->person?->full_name }}</span></td>
                                <td><x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge></td>
                                <td class="text-right">{{ RefundService::money($r->actual_refund_amount) }}</td>
                                <td class="whitespace-nowrap text-muted-foreground">{{ $r->decided_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
