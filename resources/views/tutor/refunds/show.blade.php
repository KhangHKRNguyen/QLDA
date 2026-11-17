{{-- Gia sư — chi tiết yêu cầu hoàn phí + xác nhận / xem xét lại --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Yêu cầu hoàn phí #' . $refund->refund_request_id)
@section('heading', 'Yêu cầu hoàn phí #' . $refund->refund_request_id)
@section('breadcrumb')
    <a href="{{ route('tutor.refunds.index') }}">Yêu cầu hoàn phí</a> / <span class="font-medium text-foreground">#{{ $refund->refund_request_id }}</span>
@endsection
@section('actions')
    <a href="{{ route('tutor.refunds.index') }}" class="btn btn-outline"><x-icon name="back" /> Danh sách</a>
@endsection

@section('content')
    @include('refunds._detail', [
        'evidenceUrl'      => route('tutor.refunds.evidence', $refund->refund_request_id),
        'confirmAction'    => route('tutor.refunds.confirm', $refund->refund_request_id),
        'reconsiderAction' => route('tutor.refunds.reconsider', $refund->refund_request_id),
    ])
@endsection
