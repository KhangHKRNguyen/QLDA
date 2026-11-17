{{-- Gia sư — biểu mẫu "Gửi yêu cầu hoàn phí" --}}
@extends('layouts.panel', ['panel' => 'tutor'])
@section('title', 'Yêu cầu hoàn phí')
@section('heading', 'Yêu cầu hoàn phí')
@section('description', "Lớp {$class->subject} – {$class->grade_level} (#{$class->class_id})")
@section('breadcrumb')
    <a href="{{ route('tutor.classes.index') }}">Lớp của tôi</a> /
    <a href="{{ route('tutor.classes.show', $class->class_id) }}">#{{ $class->class_id }}</a> /
    <span class="font-medium text-foreground">Yêu cầu hoàn phí</span>
@endsection

@section('content')
<div class="max-w-3xl">
    @include('refunds._form', ['action' => route('tutor.refunds.store', $class->class_id), 'source' => 'tutor'])
</div>
@endsection
