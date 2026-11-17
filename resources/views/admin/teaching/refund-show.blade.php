{{-- NV QLKH — chi tiết phiếu hoàn phí + xác nhận / xem xét lại (với phiếu do Trung tâm tạo) --}}
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Phiếu hoàn phí #' . $refund->refund_request_id)
@section('heading', 'Phiếu hoàn phí #' . $refund->refund_request_id)
@section('breadcrumb')
    <a href="{{ route('admin.teaching.refunds.index') }}">Phiếu hoàn phí</a> / <span class="font-medium text-foreground">#{{ $refund->refund_request_id }}</span>
@endsection
@section('actions')
    @if ($class)
        <a href="{{ route('admin.teaching.show', $class->class_id) }}" class="btn btn-outline"><x-icon name="back" /> Về lớp #{{ $class->class_id }}</a>
    @endif
@endsection

@section('content')
    @include('refunds._detail', [
        'evidenceUrl'      => route('admin.teaching.refunds.evidence', $refund->refund_request_id),
        'confirmAction'    => route('admin.teaching.refunds.confirm', $refund->refund_request_id),
        'reconsiderAction' => route('admin.teaching.refunds.reconsider', $refund->refund_request_id),
    ])
@endsection
