import axios from 'axios';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

/*
 | Axios: keep the original CSRF + XHR header behaviour so existing
 | AJAX endpoints (DataTables lists, role/permission fetches) keep working.
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

/*
 | Alpine drives all interactivity that Bootstrap's jQuery JS used to
 | handle: sidebar toggle, dropdowns, modals, collapsible menus, tabs.
 */
Alpine.plugin(collapse);
Alpine.plugin(focus);

/*
 | Global toast store — fire from anywhere:
 |   window.toast('Saved!', 'success')   or   $store.toast.push(...)
 |   $dispatch('toast', { message, type })   (handled by the <x-toast> container)
 */
Alpine.store('toast', {
    items: [],
    _id: 0,
    push(message, type = 'success', timeout = 4000) {
        const id = ++this._id;
        this.items.push({ id, message, type });
        if (timeout) setTimeout(() => this.dismiss(id), timeout);
    },
    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});
window.toast = (message, type = 'success', timeout = 4000) =>
    Alpine.store('toast').push(message, type, timeout);

/*
 | Demo mode: a 423 "Locked" response means the write was blocked server-side.
 | Surface it as a toast so AJAX actions (deletes, inline edits) give feedback.
 */
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 423) {
            window.toast(error.response.data?.message || 'This action is restricted in the live demo.', 'warning');
        }
        return Promise.reject(error);
    },
);

/*
 | Global confirm store — used by the <x-confirm-modal> singleton.
 | Submit a form through a confirmation dialog instead of window.confirm():
 |   <button @click.prevent="$store.confirm.open({ ...opts, onConfirm: () => $el.closest('form').submit() })">
 */
Alpine.store('confirm', {
    show: false,
    title: 'Are you sure?',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    tone: 'danger',
    icon: 'ik ik-alert-triangle',
    _onConfirm: null,
    open(opts = {}) {
        this.title = opts.title ?? 'Are you sure?';
        this.message = opts.message ?? '';
        this.confirmText = opts.confirmText ?? 'Confirm';
        this.cancelText = opts.cancelText ?? 'Cancel';
        this.tone = opts.tone ?? 'danger';
        this.icon = opts.icon ?? 'ik ik-alert-triangle';
        this._onConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
        this.show = true;
    },
    confirm() {
        const cb = this._onConfirm;
        this.show = false;
        if (cb) cb();
    },
    cancel() {
        this.show = false;
        this._onConfirm = null;
    },
});

window.Alpine = Alpine;
Alpine.start();
