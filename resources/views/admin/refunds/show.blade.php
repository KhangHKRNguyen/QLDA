{{-- Chi tiết yêu cầu hoàn phí + ra quyết định (PTTKHT 2.2.9.2, bước 3–13 và các luồng phụ) --}}
@php
    use App\Models\RefundRequest;
    use App\Services\RefundService;

    $id       = $refund->refund_request_id;
    $payment  = $refund->payment;
    $user     = auth()->user();
    $final    = $refund->isFinalDecision();
    $canDecide = $refund->awaitingDecision() && ($user->can('refund.approve') || $user->can('refund.reject'));
    $left     = $refund->daysLeft();
    $rate     = $refund->reasonRate();
    $tx       = $refund->transactions->sortByDesc('refund_transaction_id');
    $decision = old('decision', 'expected');
@endphp
@extends('layouts.panel', ['panel' => 'admin'])

@section('title', 'Yêu cầu hoàn phí #' . $id)
@section('heading', 'Yêu cầu hoàn phí #' . $id)
@section('description', 'Tiếp nhận ' . $refund->received_at?->format('d/m/Y H:i') . ' · ' . (RefundRequest::SOURCES[$refund->source_type] ?? $refund->source_type))
@section('breadcrumb')
    <a href="{{ url('/admin') }}">Tổng quan</a> /
    <a href="{{ route('admin.refunds.index') }}">Hoàn phí</a> /
    <span class="font-medium text-foreground">#{{ $id }}</span>
@endsection
@section('actions')
    <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline"><x-icon name="back" /> Danh sách</a>
    @if ($refund->status === 'refund_failed' && $user->can('refund.execute'))
        <button type="button" class="btn btn-primary"
            data-confirm
            data-confirm-title="Thực hiện lại hoàn tiền"
            data-confirm-message="Gửi lại yêu cầu hoàn {{ RefundService::money($refund->actual_refund_amount) }} đến cổng thanh toán."
            data-confirm-action="{{ route('admin.refunds.retry', $id) }}"
            data-confirm-label="Hoàn tiền lại">
            <x-icon name="check" /> Thực hiện lại hoàn tiền
        </button>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    {{-- ===== Thông báo theo trạng thái ===== --}}
    @switch($refund->status)
        @case('overdue')
            <p class="notice notice-danger">Yêu cầu đã <strong>quá hạn xử lý {{ RefundRequest::PROCESSING_DAYS }} ngày</strong> (hạn {{ $refund->deadline()->format('d/m/Y') }}). Giám đốc Trung tâm đã được thông báo — vui lòng ra quyết định ngay.</p>
            @break
        @case('pending')
            <p @class(['notice', 'notice-warning' => $left <= 3, 'notice-info' => $left > 3])>
                Chờ Trung tâm xác minh. Hạn ra quyết định: <strong>{{ $refund->deadline()->format('d/m/Y') }}</strong>
                ({{ $left === 0 ? 'hôm nay' : "còn {$left} ngày" }}).
            </p>
            @break
        @case('reconsidering')
            <p class="notice notice-warning">Người gửi <strong>yêu cầu xem xét lại</strong>. Đây là <strong>quyết định cuối cùng</strong> — sau bước này không nhận thêm yêu cầu xem xét lại.</p>
            @break
        @case('decided')
            <p class="notice notice-info">Đã gửi kết quả. Đang chờ người gửi <strong>xác nhận</strong> hoặc <strong>yêu cầu xem xét lại</strong>{{ $refund->reconsidered ? '' : ' (còn 1 lần)' }}.</p>
            @break
        @case('refunding')
            <p class="notice notice-info">Đang chờ cổng thanh toán trả kết quả hoàn tiền. Hệ thống tự kiểm tra lại mỗi 5 phút, không tạo giao dịch mới.</p>
            @break
        @case('refund_failed')
            <p class="notice notice-danger">Giao dịch hoàn tiền <strong>thất bại</strong>. Kiểm tra với cổng thanh toán rồi bấm "Thực hiện lại hoàn tiền".</p>
            @break
        @case('refunded')
            <p class="notice notice-success">Đã hoàn <strong>{{ RefundService::money($refund->actual_refund_amount) }}</strong> về phương thức thanh toán ban đầu.</p>
            @break
        @case('rejected_final')
        @case('completed')
            <p class="notice notice-info">Yêu cầu đã kết thúc ({{ $refund->statusLabel() }}), không phát sinh giao dịch hoàn tiền.</p>
            @break
    @endswitch

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- ===== Cột trái: lớp, gia sư, khách hàng, thanh toán ===== --}}
        <aside class="space-y-6">
            <section class="card">
                <div class="card-body flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Trạng thái</p>
                        <x-status-badge :tone="$refund->statusTone()" class="mt-1">{{ $refund->statusLabel() }}</x-status-badge>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium text-muted-foreground">{{ $refund->actual_refund_amount !== null ? 'Mức hoàn chính thức' : 'Mức hoàn dự kiến' }}</p>
                        <p class="mt-1 text-xl font-bold text-primary">{{ RefundService::money($refund->actual_refund_amount ?? $refund->expected_refund_amount) }}</p>
                    </div>
                </div>
            </section>

            @foreach ([
                'Lớp học' => [
                    ['Mã lớp', $class ? '#' . $class->class_id : null],
                    ['Môn / khối', $class ? "{$class->subject} – {$class->grade_level}" : null],
                    ['Hình thức', $class?->learning_format],
                    ['Ngày nhận lớp', $class?->accepted_at?->format('d/m/Y')],
                    ['Bảo hành đến', $class?->accepted_at?->copy()->addDays(60)->format('d/m/Y')],
                    ['Trạng thái lớp', $class?->status],
                ],
                'Gia sư' => [
                    ['Họ tên', $tutor?->person?->full_name],
                    ['Tên đăng nhập', $tutor?->username],
                    ['Số điện thoại', $tutor?->person?->phone_number],
                    ['Email', $tutor?->person?->email],
                ],
                'Khách hàng (Phụ huynh)' => [
                    ['Họ tên', $customer?->full_name],
                    ['Số điện thoại', $customer?->phone_number],
                    ['Khu vực', $customer?->district],
                ],
                'Giao dịch thanh toán' => [
                    ['Mã giao dịch', '#' . $payment->payment_transaction_id],
                    ['Phí nhận lớp', RefundService::money($payment->amount)],
                    ['Thời gian', $payment->transaction_at?->format('d/m/Y H:i')],
                    ['Mã tham chiếu', $payment->external_reference],
                ],
            ] as $title => $rows)
                <section class="card">
                    <header class="card-header"><h2 class="card-title">{{ $title }}</h2></header>
                    <dl class="divide-y text-sm">
                        @foreach ($rows as [$k, $v])
                            <div class="flex justify-between gap-4 px-5 py-2.5">
                                <dt class="shrink-0 text-muted-foreground">{{ $k }}</dt>
                                <dd class="text-right font-medium break-all">{{ $v ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </aside>

        <div class="space-y-6 lg:col-span-2">

            {{-- ===== Nội dung yêu cầu ===== --}}
            <section class="card">
                <header class="card-header">
                    <div>
                        <h2 class="card-title">Nội dung yêu cầu</h2>
                        <p class="card-desc">Gửi bởi {{ $refund->requester?->person?->full_name ?? $tutor?->person?->full_name ?? '—' }}</p>
                    </div>
                </header>
                <div class="card-body space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Lý do (bảng mức hoàn 6.3)</p>
                        <p class="mt-1 font-medium">{{ $refund->reasonLabel() ?? 'Không xác định' }}</p>
                        @if ($rate !== null)
                            <p class="mt-1 text-muted-foreground">
                                Tỷ lệ hoàn {{ $rate * 100 }}% × phí nhận lớp {{ RefundService::money($payment->amount) }}
                                = <strong class="text-foreground">{{ RefundService::money($refund->expected_refund_amount) }}</strong> (dự kiến)
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Nội dung phản ánh</p>
                        <p class="mt-1 whitespace-pre-line">{{ $refund->reason }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Bằng chứng</p>
                        @if ($refund->evidence_file)
                            <a href="{{ asset('storage/' . $refund->evidence_file) }}" target="_blank" rel="noopener" class="mt-1 inline-flex font-medium text-primary hover:underline">{{ basename($refund->evidence_file) }}</a>
                        @else
                            <p class="mt-1 text-muted-foreground">Không đính kèm</p>
                        @endif
                    </div>
                    @if ($refund->reconsidered && $refund->response_content)
                        <div class="rounded-lg border border-warning/40 bg-warning-soft p-3">
                            <p class="text-xs font-medium text-warning">Phản hồi khi yêu cầu xem xét lại</p>
                            <p class="mt-1 whitespace-pre-line">{{ $refund->response_content }}</p>
                        </div>
                    @endif
                </div>
            </section>

            {{-- ===== Ra quyết định ===== --}}
            @if ($canDecide)
                <section class="card border-primary/40" id="quyet-dinh">
                    <header class="card-header">
                        <div>
                            <h2 class="card-title">{{ $final ? 'Quyết định cuối cùng' : 'Ra quyết định' }}</h2>
                            <p class="card-desc">
                                {{ $final ? 'Kết quả này là cuối cùng. Nếu có khoản hoàn, hệ thống gửi yêu cầu hoàn tiền ngay.'
                                          : 'Kết quả được gửi cho người gửi yêu cầu để xác nhận hoặc yêu cầu xem xét lại.' }}
                            </p>
                        </div>
                    </header>
                    <form method="POST" class="card-body space-y-5" data-refund-form
                          data-approve-action="{{ route('admin.refunds.approve', $id) }}"
                          data-reject-action="{{ route('admin.refunds.reject', $id) }}"
                          action="{{ $decision === 'reject' ? route('admin.refunds.reject', $id) : route('admin.refunds.approve', $id) }}">
                        @csrf
                        <fieldset class="space-y-2">
                            <legend class="field-label mb-2">Quyết định <span class="req">*</span></legend>
                            @foreach ([
                                'expected' => ['Duyệt đúng mức dự kiến', RefundService::money($refund->expected_refund_amount), 'refund.approve'],
                                'adjust'   => ['Điều chỉnh mức hoàn rồi duyệt', 'Nhập mức chính thức và lý do điều chỉnh', 'refund.approve'],
                                'reject'   => ['Từ chối hoàn phí', 'Nhập lý do từ chối', 'refund.reject'],
                            ] as $value => [$label, $hint, $perm])
                                @can($perm)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 has-checked:border-primary has-checked:bg-secondary">
                                        <input type="radio" name="decision" value="{{ $value }}" class="mt-1 accent-primary" @checked($decision === $value)>
                                        <span>
                                            <span class="block text-sm font-medium">{{ $label }}</span>
                                            <span class="block text-xs text-muted-foreground">{{ $hint }}</span>
                                        </span>
                                    </label>
                                @endcan
                            @endforeach
                            <input type="hidden" name="mode" value="{{ $decision === 'adjust' ? 'adjust' : 'expected' }}">
                        </fieldset>

                        <div class="grid gap-4 sm:grid-cols-[14rem_1fr]" data-show-for="adjust" @if ($decision !== 'adjust') hidden @endif>
                            <div class="field">
                                <label for="amount" class="field-label">Mức hoàn chính thức (đ) <span class="req">*</span></label>
                                <input id="amount" type="number" name="amount" min="0" max="{{ (int) $payment->amount }}" step="1000"
                                       value="{{ old('amount', (int) $refund->expected_refund_amount) }}" @class(['input', 'is-invalid' => $errors->has('amount')])>
                                <p class="field-hint">Tối đa {{ RefundService::money($payment->amount) }}</p>
                                @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="note" class="field-label">Lý do điều chỉnh <span class="req">*</span></label>
                                <textarea id="note" name="note" rows="3" @class(['input', 'is-invalid' => $errors->has('note')])
                                          placeholder="VD: Đã xác minh với phụ huynh, gia sư dạy 2 buổi chính thức...">{{ old('note') }}</textarea>
                                @error('note')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="field" data-show-for="reject" @if ($decision !== 'reject') hidden @endif>
                            <label for="reason" class="field-label">Lý do từ chối <span class="req">*</span></label>
                            <textarea id="reason" name="reason" rows="3" @class(['input', 'is-invalid' => $errors->has('reason')])
                                      placeholder="VD: Đã xác minh với phụ huynh, lý do không thuộc trường hợp được hoàn...">{{ old('reason') }}</textarea>
                            @error('reason')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <fieldset class="space-y-2">
                            <legend class="field-label mb-2">Xử lý lớp sau khi kết thúc hoàn phí <span class="req">*</span></legend>
                            @foreach (RefundRequest::CLASS_OUTCOMES as $value => [$label])
                                <label class="flex cursor-pointer items-center gap-3 text-sm">
                                    <input type="radio" name="class_outcome" value="{{ $value }}" class="accent-primary"
                                           @checked(old('class_outcome', $refund->class_outcome ?? 'reopen') === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                            <p class="field-hint">Áp dụng khi yêu cầu kết thúc (hoàn tiền thành công, hoàn tất hoặc từ chối lần cuối).</p>
                            @error('class_outcome')<p class="field-error">{{ $message }}</p>@enderror
                        </fieldset>

                        <div class="flex flex-wrap justify-end gap-2 border-t pt-4">
                            <button type="submit" class="btn btn-primary" data-submit-label>
                                <x-icon name="check" /> <span>{{ $decision === 'reject' ? 'Từ chối' : 'Duyệt' }}{{ $final ? ' (cuối cùng)' : '' }}</span>
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- ===== Kết quả đã quyết định ===== --}}
            @if ($refund->decided_at)
                <section class="card">
                    <header class="card-header"><h2 class="card-title">Kết quả xử lý</h2></header>
                    <dl class="divide-y text-sm">
                        @foreach ([
                            ['Mức hoàn chính thức', RefundService::money($refund->actual_refund_amount)],
                            ['Lý do điều chỉnh', $refund->decision_note],
                            ['Lý do từ chối', $refund->rejection_reason],
                            ['Xử lý lớp', RefundRequest::CLASS_OUTCOMES[$refund->class_outcome][0] ?? null],
                            ['Đã xem xét lại', $refund->reconsidered ? 'Có (đã dùng 1/1 lần)' : 'Chưa'],
                            ['Người quyết định', $refund->decider?->person?->full_name ?? $refund->decider?->username],
                            ['Thời điểm', $refund->decided_at->format('d/m/Y H:i')],
                        ] as [$k, $v])
                            @if ($v)
                                <div class="flex justify-between gap-4 px-5 py-2.5">
                                    <dt class="shrink-0 text-muted-foreground">{{ $k }}</dt>
                                    <dd class="text-right font-medium">{{ $v }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </section>
            @endif

            {{-- ===== Giao dịch hoàn tiền ===== --}}
            @if ($tx->isNotEmpty())
                <section class="card">
                    <header class="card-header"><h2 class="card-title">Giao dịch hoàn tiền</h2></header>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Mã</th><th class="text-right">Số tiền</th><th>Trạng thái</th><th>Hoàn lúc</th></tr></thead>
                            <tbody>
                                @foreach ($tx as $t)
                                    <tr>
                                        <td class="text-muted-foreground">#{{ $t->refund_transaction_id }}</td>
                                        <td class="text-right font-medium">{{ RefundService::money($t->refund_amount) }}</td>
                                        <td><x-status-badge :tone="$t->statusTone()">{{ $t->statusLabel() }}</x-status-badge></td>
                                        <td class="text-muted-foreground">{{ $t->refunded_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- ===== Lịch sử xử lý ===== --}}
            <section class="card">
                <header class="card-header"><h2 class="card-title">Lịch sử xử lý</h2></header>
                @if ($logs->isEmpty())
                    <p class="card-body text-sm text-muted-foreground">Chưa có thao tác nào được ghi nhận.</p>
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
                                        {{ $log->occurred_at?->format('d/m/Y H:i') }}@if ($log->account) · {{ $log->account->username }}@endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>
</div>

@if ($canDecide)
    <script>
        // Đổi phần nhập liệu, action của form và nhãn nút theo quyết định đang chọn
        (() => {
            const form = document.querySelector('[data-refund-form]');
            if (!form) return;
            const final = @json($final ? ' (cuối cùng)' : '');
            const sync = () => {
                const value = form.querySelector('input[name="decision"]:checked')?.value ?? 'expected';
                form.querySelectorAll('[data-show-for]').forEach(el => { el.hidden = el.dataset.showFor !== value; });
                form.action = value === 'reject' ? form.dataset.rejectAction : form.dataset.approveAction;
                form.querySelector('input[name="mode"]').value = value === 'adjust' ? 'adjust' : 'expected';
                form.querySelector('[data-submit-label] span').textContent = (value === 'reject' ? 'Từ chối' : 'Duyệt') + final;
            };
            form.addEventListener('change', e => { if (e.target.name === 'decision') sync(); });
            sync();
        })();
    </script>
@endif
@endsection
