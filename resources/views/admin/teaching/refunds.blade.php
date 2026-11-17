{{-- NV QLKH — phiếu hoàn phí do Trung tâm tạo theo phản ánh của phụ huynh --}}
@php use App\Services\RefundService; @endphp
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Phiếu hoàn phí')
@section('heading', 'Phiếu hoàn phí')
@section('description', 'Các phiếu do nhân viên Quản lý khách hàng tạo. Khi Quản lý Trung tâm có kết quả, xác nhận hoặc yêu cầu xem xét lại tại đây.')
@section('actions')
    <a href="{{ route('admin.teaching.index') }}" class="btn btn-outline">Lớp đang dạy</a>
@endsection

@section('content')
<section class="card">
    <nav class="flex gap-2 border-b p-4">
        <a href="{{ route('admin.teaching.refunds.index') }}" @class(['btn btn-sm', 'btn-primary' => ! request('loc'), 'btn-outline' => request('loc')])>Tất cả</a>
        <a href="{{ route('admin.teaching.refunds.index', ['loc' => 'cho-xac-nhan']) }}" @class(['btn btn-sm', 'btn-primary' => request('loc'), 'btn-outline' => ! request('loc')])>Chờ xác nhận kết quả</a>
    </nav>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Mã</th><th>Lớp</th><th>Gia sư</th><th class="text-right">Mức hoàn</th><th>Trạng thái</th><th>Người tạo</th><th></th></tr></thead>
            <tbody>
                @forelse ($refunds as $r)
                    @php $c = $r->payment?->tutorClass; @endphp
                    <tr>
                        <td class="text-muted-foreground">#{{ $r->refund_request_id }}</td>
                        <td class="whitespace-nowrap">{{ $c ? "{$c->subject} – {$c->grade_level}" : '—' }}</td>
                        <td class="whitespace-nowrap">{{ $r->payment?->account?->person?->full_name }}</td>
                        <td class="text-right whitespace-nowrap">
                            <span class="block font-medium">{{ RefundService::money($r->actual_refund_amount ?? $r->expected_refund_amount) }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $r->actual_refund_amount !== null ? 'chính thức' : 'dự kiến' }}</span>
                        </td>
                        <td><x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge></td>
                        <td class="whitespace-nowrap text-sm text-muted-foreground">{{ $r->requester?->person?->full_name }}<br>{{ $r->received_at->format('d/m/Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.teaching.refunds.show', $r->refund_request_id) }}"
                               @class(['btn btn-sm', 'btn-primary' => $r->status === 'decided', 'btn-outline' => $r->status !== 'decided'])>
                                {{ $r->status === 'decided' ? 'Phản hồi' : 'Xem' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-muted-foreground">Chưa có phiếu nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($refunds->hasPages())
        <div class="flex justify-end gap-2 border-t px-4 py-3">
            <a href="{{ $refunds->previousPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => $refunds->onFirstPage()])>‹ Trước</a>
            <a href="{{ $refunds->nextPageUrl() ?? '#' }}" @class(['btn btn-outline btn-sm', 'pointer-events-none opacity-50' => ! $refunds->hasMorePages()])>Sau ›</a>
        </div>
    @endif
</section>
@endsection
