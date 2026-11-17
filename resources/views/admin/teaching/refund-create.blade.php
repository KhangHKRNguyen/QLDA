{{-- NV QLKH — tạo phiếu yêu cầu hoàn phí theo phản ánh của phụ huynh (HĐ8 TH2: chọn được cả 6 lý do) --}}
@extends('layouts.panel', ['panel' => 'admin'])
@section('title', 'Tạo phiếu hoàn phí')
@section('heading', 'Tạo phiếu yêu cầu hoàn phí')
@section('description', "Lớp {$class->subject} – {$class->grade_level} (#{$class->class_id}) · gia sư " . ($tutor?->person?->full_name ?? '—'))
@section('breadcrumb')
    <a href="{{ route('admin.teaching.index') }}">Lớp đang dạy</a> /
    <a href="{{ route('admin.teaching.show', $class->class_id) }}">#{{ $class->class_id }}</a> /
    <span class="font-medium text-foreground">Tạo phiếu hoàn phí</span>
@endsection

@section('content')
<div class="max-w-3xl">
    @include('refunds._form', ['action' => route('admin.teaching.refund.store', $class->class_id), 'source' => 'staff'])
</div>
@endsection
