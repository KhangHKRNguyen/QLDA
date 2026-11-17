{{-- Chi tiết yêu cầu hoàn phí cho người gửi (Gia sư / NV QLKH) + Xác nhận / Yêu cầu xem xét lại (FR-HP-08, 09).
     Biến: $refund, $class, $evidenceUrl, $canRespond, $confirmAction, $reconsiderAction, $logs --}}
@php
    use App\Models\RefundRequest;
    use App\Services\RefundService;
    $decided = $refund->decided_at !== null;
    $rejected = $decided && (float) $refund->actual_refund_amount <= 0 && $refund->rejection_reason;
    $tx = $refund->transactions->sortByDesc('refund_transaction_id')->first();
@endphp
<div class="space-y-6">
    {{-- ===== Trạng thái ===== --}}
    @switch($refund->status)
        @case('pending')
        @case('overdue')
            <p class="notice notice-info">Trung tâm đang xác minh yêu cầu. Thời hạn xử lý {{ RefundRequest::PROCESSING_DAYS }} ngày kể từ ngày tiếp nhận ({{ $refund->received_at->format('d/m/Y') }}).</p>
            @break
        @case('decided')
            <p class="notice notice-warning">Trung tâm đã có kết quả. Vui lòng <strong>xác nhận</strong> hoặc <strong>yêu cầu xem xét lại</strong> (chỉ được 1 lần).</p>
            @break
        @case('reconsidering')
            <p class="notice notice-info">Đã gửi yêu cầu xem xét lại. Quản lý Trung tâm sẽ ra <strong>quyết định cuối cùng</strong>.</p>
            @break
        @case('refunding')
            <p class="notice notice-info">Trung tâm đang hoàn tiền về phương thức thanh toán ban đầu.</p>
            @break
        @case('refund_failed')
            <p class="notice notice-warning">Giao dịch hoàn tiền chưa thành công, Trung tâm đang xử lý lại.</p>
            @break
        @case('refunded')
            <p class="notice notice-success">Đã hoàn <strong>{{ RefundService::money($refund->actual_refund_amount) }}</strong>{{ $tx?->refunded_at ? ' ngày ' . $tx->refunded_at->format('d/m/Y') : '' }} về phương thức thanh toán ban đầu.</p>
            @break
        @default
            <p class="notice notice-info">Yêu cầu đã kết thúc ({{ $refund->statusLabel() }}).</p>
    @endswitch

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- ===== Kết quả ===== --}}
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Kết quả xử lý</h2>
                    <x-status-badge :tone="$refund->statusTone()">{{ $refund->statusLabel() }}</x-status-badge>
                </header>
                <div class="card-body grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-muted p-3">
                        <p class="text-xs text-muted-foreground">Mức hoàn dự kiến</p>
                        <p class="mt-1 font-semibold">{{ RefundService::money($refund->expected_refund_amount) }}</p>
                    </div>
                    <div @class(['rounded-lg p-3', 'bg-secondary' => $decided, 'bg-muted' => ! $decided])>
                        <p class="text-xs text-muted-foreground">Mức hoàn chính thức</p>
                        <p @class(['mt-1 text-lg font-bold', 'text-primary' => ! $rejected, 'text-danger' => $rejected])>
                            {{ $decided ? ($rejected ? 'Từ chối hoàn phí' : RefundService::money($refund->actual_refund_amount)) : 'Chờ Trung tâm quyết định' }}
                        </p>
                    </div>
                    @if ($refund->decision_note)
                        <p class="sm:col-span-2"><span class="text-muted-foreground">Lý do điều chỉnh:</span> {{ $refund->decision_note }}</p>
                    @endif
                    @if ($refund->rejection_reason)
                        <p class="sm:col-span-2"><span class="text-muted-foreground">Lý do từ chối:</span> {{ $refund->rejection_reason }}</p>
                    @endif
                </div>
            </section>

            {{-- ===== Phản hồi kết quả ===== --}}
            @if ($canRespond)
                <section class="card border-primary/40">
                    <header class="card-header">
                        <div>
                            <h2 class="card-title">Phản hồi kết quả</h2>
                            <p class="card-desc">Đồng ý thì bấm Xác nhận{{ (float) $refund->actual_refund_amount > 0 ? ', Trung tâm sẽ hoàn tiền ngay' : '' }}. Chưa đồng ý thì gửi yêu cầu xem xét lại kèm nội dung phản hồi.</p>
                        </div>
                    </header>
                    <div class="card-body space-y-5">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary"
                                data-confirm
                                data-confirm-title="Xác nhận kết quả"
                                data-confirm-message="{{ (float) $refund->actual_refund_amount > 0 ? 'Bạn đồng ý mức hoàn ' . RefundService::money($refund->actual_refund_amount) . '. Trung tâm sẽ hoàn tiền về phương thức thanh toán ban đầu.' : 'Bạn đồng ý với kết quả. Yêu cầu sẽ được ghi nhận hoàn tất.' }}"
                                data-confirm-action="{{ $confirmAction }}"
                                data-confirm-label="Xác nhận">
                                <x-icon name="check" /> Xác nhận kết quả
                            </button>
                        </div>
                        @if (! $refund->reconsidered)
                            <form method="POST" action="{{ $reconsiderAction }}" enctype="multipart/form-data" class="space-y-3 border-t pt-4">
                                @csrf
                                <p class="text-sm font-medium">Yêu cầu xem xét lại <span class="font-normal text-muted-foreground">(chỉ 1 lần, sau đó là quyết định cuối cùng)</span></p>
                                <div class="field">
                                    <label for="response_content" class="field-label">Nội dung phản hồi <span class="req">*</span></label>
                                    <textarea id="response_content" name="response_content" rows="3" @class(['input', 'is-invalid' => $errors->has('response_content')])
                                              placeholder="Vì sao bạn chưa đồng ý với kết quả...">{{ old('response_content') }}</textarea>
                                    @error('response_content')<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="field">
                                    <label for="evidence" class="field-label">Bổ sung bằng chứng (nếu có)</label>
                                    <input id="evidence" type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" @class(['input py-1.5', 'is-invalid' => $errors->has('evidence')])>
                                    @error('evidence')<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" class="btn btn-outline">Gửi yêu cầu xem xét lại</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            {{-- ===== Nội dung đã gửi ===== --}}
            <section class="card">
                <header class="card-header"><h2 class="card-title">Nội dung yêu cầu</h2></header>
                <div class="card-body space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Lý do</p>
                        <p class="mt-1 font-medium">{{ $refund->reasonLabel() ?? '—' }} @if ($refund->reasonRate() !== null)<span class="text-muted-foreground">({{ $refund->reasonRate() * 100 }}%)</span>@endif</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Nội dung phản ánh</p>
                        <p class="mt-1 whitespace-pre-line">{{ $refund->reason }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Bằng chứng</p>
                        @if ($refund->evidence_file)
                            <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex font-medium text-primary hover:underline">Xem file đính kèm</a>
                        @else
                            <p class="mt-1 text-muted-foreground">Không đính kèm</p>
                        @endif
                    </div>
                    @if ($refund->response_content)
                        <div class="rounded-lg border border-warning/40 bg-warning-soft p-3">
                            <p class="text-xs font-medium text-warning">Phản hồi khi yêu cầu xem xét lại</p>
                            <p class="mt-1 whitespace-pre-line">{{ $refund->response_content }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- ===== Cột phải: lớp + lịch sử ===== --}}
        <aside class="space-y-6">
            <section class="card">
                <header class="card-header"><h2 class="card-title">Thông tin</h2></header>
                <dl class="divide-y text-sm">
                    @foreach ([
                        ['Lớp', $class ? "{$class->subject} – {$class->grade_level} (#{$class->class_id})" : null],
                        ['Gia sư', $refund->payment?->account?->person?->full_name],
                        ['Nguồn', RefundRequest::SOURCES[$refund->source_type] ?? $refund->source_type],
                        ['Người gửi', $refund->requester?->person?->full_name],
                        ['Ngày tiếp nhận', $refund->received_at?->format('d/m/Y H:i')],
                        ['Phí nhận lớp', RefundService::money($refund->payment?->amount)],
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
            <section class="card">
                <header class="card-header"><h2 class="card-title">Lịch sử</h2></header>
                <ol class="card-body space-y-3">
                    @forelse ($logs as $log)
                        <li class="text-sm">
                            <p class="font-medium">{{ $log->action }}</p>
                            <p class="text-xs text-muted-foreground">{{ $log->occurred_at?->format('d/m/Y H:i') }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-muted-foreground">Chưa có thao tác.</li>
                    @endforelse
                </ol>
            </section>
        </aside>
    </div>
</div>
