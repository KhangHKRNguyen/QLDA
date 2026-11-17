{{-- Gia sư — danh sách yêu cầu hoàn phí của mình --}}
@php use App\Services\RefundService; @endphp
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Yêu cầu hoàn phí')
@section('heading', 'Yêu cầu hoàn phí')
@section('description', 'Theo dõi các yêu cầu hoàn phí của bạn. Muốn gửi yêu cầu mới, vào lớp tương ứng trong "Lớp của tôi".')

@section('content')
<section class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Mã</th><th>Lớp</th><th>Lý do</th><th class="text-right">Mức hoàn</th><th>Trạng thái</th><th>Ngày gửi</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($refunds as $r)
                    @php $class = $r->payment?->tutorClass; @endphp
                    <tr>
                        <td class="text-muted-foreground">#{{ $r->refund_request_id }}</td>
                        <td class="whitespace-nowrap">{{ $class ? "{$class->subject} – {$class->grade_level}" : '—' }}</td>
                        <td class="max-w-64"><span class="line-clamp-2 text-sm">{{ $r->reasonLabel() ?? $r->reason }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <span class="block font-medium">{{ RefundService::money($r->actual_refund_amount ?? $r->expected_refund_amount) }}</span>
                            <span class="block text-xs text-muted-foreground">{{ $r->actual_refund_amount !== null ? 'chính thức' : 'dự kiến' }}</span>
                        </td>
                        <td><x-status-badge :tone="$r->statusTone()">{{ $r->statusLabel() }}</x-status-badge></td>
                        <td class="whitespace-nowrap text-muted-foreground">{{ $r->received_at->format('d/m/Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('tutor.refunds.show', $r->refund_request_id) }}"
                               @class(['btn btn-sm', 'btn-primary' => $r->status === 'decided', 'btn-outline' => $r->status !== 'decided'])>
                                {{ $r->status === 'decided' ? 'Phản hồi' : 'Xem' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-muted-foreground">Bạn chưa có yêu cầu hoàn phí nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
