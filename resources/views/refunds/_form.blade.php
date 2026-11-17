{{-- Biểu mẫu "Gửi yêu cầu hoàn phí" (PTTKHT 2.1.8 HĐ8–HĐ11, SRS FR-HP-02/03).
     Biến: $action (URL), $payment (giao dịch phí nhận lớp), $source ('tutor' | 'staff'), $preset (mã lý do chọn sẵn, tùy chọn) --}}
@php
    use App\Models\RefundRequest;
    use App\Services\RefundService;
    $fee = (float) $payment->amount;
    $selected = old('reason_code', $preset ?? null);
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" data-refund-submit data-fee="{{ (int) $fee }}">
    @csrf

    <section class="card">
        <header class="card-header">
            <div>
                <h2 class="card-title">1. Lý do hoàn phí <span class="text-danger">*</span></h2>
                <p class="card-desc">
                    Tỷ lệ hoàn theo bảng chính sách của Trung tâm.
                    @if ($source === 'tutor')
                        Gia sư chỉ chọn được các lý do khách quan; lý do khác vui lòng liên hệ Trung tâm.
                    @endif
                </p>
            </div>
        </header>
        <fieldset class="card-body space-y-2">
            <legend class="sr-only">Lý do hoàn phí</legend>
            @foreach (RefundRequest::REASONS as $code => [$label, $rate, $tutorAllowed])
                @php $locked = $source === 'tutor' && ! $tutorAllowed; @endphp
                <label @class(['flex items-start gap-3 rounded-lg border p-3', 'cursor-pointer has-checked:border-primary has-checked:bg-secondary' => ! $locked, 'cursor-not-allowed opacity-50' => $locked])>
                    <input type="radio" name="reason_code" value="{{ $code }}" data-rate="{{ $rate }}" class="mt-1 accent-primary"
                           @checked($selected === $code) @disabled($locked)>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium">{{ $label }}</span>
                        @if ($locked)
                            <span class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground"><x-icon name="lock" class="size-3" /> Chỉ Trung tâm ghi nhận</span>
                        @endif
                    </span>
                    <span class="shrink-0 text-sm font-semibold {{ $rate > 0 ? 'text-primary' : 'text-muted-foreground' }}">{{ $rate * 100 }}%</span>
                </label>
            @endforeach
            @error('reason_code')<p class="field-error">{{ $message }}</p>@enderror
            <p class="field-hint">Trường hợp phụ huynh chỉ giảm số buổi hoặc số giờ học thì Trung tâm tính lại phí, không áp dụng bảng này.</p>
        </fieldset>
    </section>

    <section class="card">
        <header class="card-header"><h2 class="card-title">2. Nội dung và bằng chứng</h2></header>
        <div class="card-body space-y-4">
            <div class="field">
                <label for="reason" class="field-label">Nội dung phản ánh <span class="req">*</span></label>
                <textarea id="reason" name="reason" rows="4" @class(['input', 'is-invalid' => $errors->has('reason')])
                          placeholder="{{ $source === 'staff' ? 'Ghi lại phản ánh của phụ huynh: thời điểm, nội dung, mong muốn...' : 'Mô tả sự việc: thời điểm, nội dung trao đổi với phụ huynh...' }}">{{ old('reason') }}</textarea>
                @error('reason')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="evidence" class="field-label">Bằng chứng (nếu có)</label>
                <input id="evidence" type="file" name="evidence" accept=".jpg,.jpeg,.png,.pdf" @class(['input py-1.5', 'is-invalid' => $errors->has('evidence')])>
                <p class="field-hint">Ảnh chụp tin nhắn, ghi chú buổi học... JPG, PNG hoặc PDF, tối đa 5 MB.</p>
                @error('evidence')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- HĐ10: hiển thị mức hoàn dự kiến trước khi xác nhận gửi --}}
    <section class="card border-primary/40">
        <header class="card-header"><h2 class="card-title">3. Xác nhận mức hoàn dự kiến</h2></header>
        <div class="card-body space-y-4">
            <div class="grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-lg bg-muted p-3">
                    <p class="text-xs text-muted-foreground">Phí nhận lớp đã thanh toán</p>
                    <p class="mt-1 font-semibold">{{ RefundService::money($fee) }}</p>
                </div>
                <div class="rounded-lg bg-muted p-3">
                    <p class="text-xs text-muted-foreground">Tỷ lệ hoàn</p>
                    <p class="mt-1 font-semibold" data-rate-out>—</p>
                </div>
                <div class="rounded-lg bg-secondary p-3">
                    <p class="text-xs text-muted-foreground">Mức hoàn dự kiến</p>
                    <p class="mt-1 text-lg font-bold text-primary" data-amount-out>—</p>
                </div>
            </div>
            <p class="text-xs text-muted-foreground">
                Đây là mức dự kiến. Mức chính thức do Quản lý Trung tâm quyết định sau khi xác minh với phụ huynh,
                trong vòng {{ RefundRequest::PROCESSING_DAYS }} ngày kể từ khi tiếp nhận.
            </p>
            <label class="flex cursor-pointer items-start gap-2 text-sm">
                <input type="checkbox" name="confirm" value="1" class="mt-0.5 accent-primary" @checked(old('confirm'))>
                <span>Tôi xác nhận thông tin trên là đúng và đồng ý gửi yêu cầu hoàn phí.</span>
            </label>
            @error('confirm')<p class="field-error">{{ $message }}</p>@enderror
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary"><x-icon name="check" /> Gửi yêu cầu</button>
            </div>
        </div>
    </section>
</form>

<script>
    // Tính mức hoàn dự kiến = phí nhận lớp × tỷ lệ của lý do đang chọn (FR-HP-03)
    (() => {
        const form = document.querySelector('[data-refund-submit]');
        if (!form) return;
        const fee = Number(form.dataset.fee);
        const money = n => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' đ';
        const sync = () => {
            const picked = form.querySelector('input[name="reason_code"]:checked');
            const rate = picked ? Number(picked.dataset.rate) : null;
            form.querySelector('[data-rate-out]').textContent = rate === null ? '—' : Math.round(rate * 100) + '%';
            form.querySelector('[data-amount-out]').textContent = rate === null ? 'Chọn lý do' : money(fee * rate);
        };
        form.addEventListener('change', e => { if (e.target.name === 'reason_code') sync(); });
        sync();
    })();
</script>
