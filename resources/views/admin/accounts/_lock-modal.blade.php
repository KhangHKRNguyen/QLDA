{{-- Popup TẠM KHÓA (vi phạm nhẹ, 7–30 ngày).
     Mở bằng: <button type="button" data-lock-action="{{ route('admin.accounts.lock', $id) }}" data-lock-name="Tên"> --}}
<dialog id="lock-modal" class="modal" aria-labelledby="lock-title">
    <form id="lock-form" method="POST" action="#">
        @csrf
        <div class="modal-header">
            <h2 id="lock-title" class="modal-title">Tạm khóa tài khoản</h2>
            <p class="modal-desc">
                Áp dụng cho vi phạm nhẹ, có thể khắc phục. Tài khoản
                <strong id="lock-name" class="text-foreground"></strong> sẽ tự động mở khóa khi hết thời hạn.
            </p>
        </div>
        <div class="modal-body space-y-4">
            <div class="field">
                <label for="lock-reason" class="field-label">Lý do tạm khóa <span class="req">*</span></label>
                <textarea id="lock-reason" name="reason" class="input" rows="3" required minlength="5" maxlength="500"
                    placeholder="VD: Hủy buổi dạy không báo trước nhiều lần"></textarea>
            </div>
            <div class="field">
                <label for="lock-days" class="field-label">Thời hạn khóa <span class="req">*</span></label>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ([7, 14, 30] as $d)
                        <button type="button" class="btn btn-outline btn-sm" data-lock-days="{{ $d }}">{{ $d }} ngày</button>
                    @endforeach
                    <input id="lock-days" name="days" type="number" min="7" max="30" value="7" required class="input w-20">
                    <span class="text-sm text-muted-foreground">ngày</span>
                </div>
                <p class="field-hint">Từ 7 đến 30 ngày. Tự động mở khóa vào: <strong id="lock-until" class="text-foreground"></strong></p>
            </div>
            <p class="notice notice-warning">Người dùng sẽ nhận thông báo kèm lý do và thời hạn khóa.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Hủy</button>
            <button type="submit" class="btn btn-primary"><x-icon name="lock" /> Tạm khóa</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('lock-modal');
    const form  = document.getElementById('lock-form');
    const days  = document.getElementById('lock-days');

    const render = () => {
        const n = Math.min(30, Math.max(7, parseInt(days.value, 10) || 7));
        const until = new Date();
        until.setDate(until.getDate() + n);
        document.getElementById('lock-until').textContent = until.toLocaleDateString('vi-VN');
        modal.querySelectorAll('[data-lock-days]').forEach((b) => {
            b.className = 'btn btn-sm ' + (b.dataset.lockDays === days.value ? 'btn-secondary' : 'btn-outline');
        });
    };
    days.addEventListener('input', render);

    document.addEventListener('click', (e) => {
        const preset = e.target.closest('[data-lock-days]');
        if (preset) { days.value = preset.dataset.lockDays; render(); return; }

        const btn = e.target.closest('[data-lock-action]');
        if (!btn) return;
        form.reset();
        form.action = btn.dataset.lockAction;
        document.getElementById('lock-name').textContent = btn.dataset.lockName || '';
        render();
        modal.showModal();
    });
})();
</script>
@endpush
