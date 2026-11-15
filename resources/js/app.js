
const $  = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ================= TOAST =================
   JS:     toast('Thêm yêu cầu thành công');  toast('Có lỗi', 'error');
   Server: tự hiện khi redirect()->with('success' | 'error' | 'info', '...') */
window.toast = function (message, type = 'success') {
    const root = $('#toast-root');
    if (!root) return;
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const text = document.createElement('span');
    text.className = 'flex-1';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'cursor-pointer text-muted-foreground hover:text-foreground';
    close.setAttribute('aria-label', 'Đóng thông báo');
    close.textContent = '×';

    const remove = () => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); };
    close.addEventListener('click', remove);
    el.append(text, close);
    root.append(el);
    setTimeout(remove, 5000);
};

$$('[data-flash]').forEach((el) => window.toast(el.textContent.trim(), el.dataset.flash));

/* ================= POPUP XÁC NHẬN =================
   <button type="button" class="btn btn-danger"
       data-confirm
       data-confirm-title="Xóa bài viết"
       data-confirm-message="Bài viết sẽ bị xóa vĩnh viễn."
       data-confirm-action="{{ route('admin.articles.destroy', $a) }}"
       data-confirm-method="DELETE"
       data-confirm-label="Xóa"
       data-confirm-tone="danger"
       data-confirm-reason="Lý do xóa">
   - data-confirm-reason: có thuộc tính này thì hiện ô nhập lý do (gửi lên với name="reason").
   - Không bắt buộc lý do: thêm data-confirm-reason-required="false". */
function openConfirm(btn) {
    const d = btn.dataset;
    const form = $('#confirm-form');
    $('#confirm-title').textContent = d.confirmTitle || 'Xác nhận';
    $('#confirm-message').textContent = d.confirmMessage || '';
    form.action = d.confirmAction || '#';
    $('input[name="_method"]', form).value = (d.confirmMethod || 'POST').toUpperCase();

    const submit = $('#confirm-submit');
    submit.textContent = d.confirmLabel || 'Xác nhận';
    submit.className = 'btn ' + (d.confirmTone === 'danger' ? 'btn-danger' : 'btn-primary');

    const reason = $('#confirm-reason');
    $('#confirm-reason-box').hidden = d.confirmReason === undefined;
    $('#confirm-reason-label').textContent = d.confirmReason || 'Lý do';
    reason.required = d.confirmReasonRequired !== 'false';
    reason.value = '';
    reason.removeAttribute('aria-invalid');
    $('#confirm-reason-error').hidden = true;
    $('#confirm-modal').showModal();
}

$('#confirm-form')?.addEventListener('submit', (e) => {
    const reason = $('#confirm-reason');
    if (!$('#confirm-reason-box').hidden && reason.required && reason.value.trim().length < 5) {
        e.preventDefault();
        $('#confirm-reason-error').hidden = false;
        reason.setAttribute('aria-invalid', 'true');
        reason.focus();
    }
});

/* ================= CLICK TOÀN TRANG ================= */
document.addEventListener('click', (e) => {
    // Mở popup bất kỳ: <button data-modal-open="id-popup">
    const open = e.target.closest('[data-modal-open]');
    if (open) { document.getElementById(open.dataset.modalOpen)?.showModal(); return; }

    // Đóng popup: <button data-modal-close>
    if (e.target.closest('[data-modal-close]')) { e.target.closest('dialog')?.close(); return; }

    // Bấm ra vùng nền tối để đóng
    if (e.target instanceof HTMLDialogElement) { e.target.close(); return; }

    const confirmBtn = e.target.closest('[data-confirm]');
    if (confirmBtn) { e.preventDefault(); openConfirm(confirmBtn); return; }

    // Menu mobile (header trang khách): <button data-toggle="#mobile-nav">
    const tg = e.target.closest('[data-toggle]');
    if (tg) {
        const target = $(tg.dataset.toggle);
        target.hidden = !target.hidden;
        tg.setAttribute('aria-expanded', String(!target.hidden));
        return;
    }

    // Sidebar mobile (layout panel)
    if (e.target.closest('[data-sidebar-toggle]')) {
        const on = $('.sidebar').classList.toggle('is-open');
        $('#sidebar-backdrop').hidden = !on;
        return;
    }
    if (e.target.closest('#sidebar-backdrop')) {
        $('.sidebar').classList.remove('is-open');
        $('#sidebar-backdrop').hidden = true;
    }
});
