/**
 * User Management — JS
 * AJAX table filtering, CRUD via fetch, toast notifications
 */

const cfg      = window.userMgmtConfig ?? {};
const indexUrl = cfg.indexUrl ?? window.location.pathname;

// ─── Toast ────────────────────────────────────────────────────────────────────

function toast(message, type = 'success') {
    const colors = {
        success: { bg: '#f0fdf4', border: '#86efac', text: '#166534' },
        error:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b' },
        info:    { bg: '#eff6ff', border: '#bfdbfe', text: '#1e40af' },
    };
    const c = colors[type] ?? colors.info;
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:10px;border:1px solid ${c.border};background:${c.bg};color:${c.text};font-size:13px;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,.1);transform:translateX(120%);transition:transform .25s ease`;
    el.innerHTML = message;
    document.body.appendChild(el);
    requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; });
    setTimeout(() => {
        el.style.transform = 'translateX(120%)';
        setTimeout(() => el.remove(), 300);
    }, 4000);
}

// ─── AJAX table fetch ────────────────────────────────────────────────────────

const container = document.getElementById('users-table-container');
const overlay   = document.getElementById('users-loading');
let   currentReq = null;

function buildUrl(overrides = {}) {
    const params = new URLSearchParams(window.location.search);
    const resetsPage = Object.keys(overrides).some(k => k !== 'page');
    if (resetsPage) params.delete('page');
    Object.entries(overrides).forEach(([k, v]) => {
        if (v === '' || v == null) params.delete(k);
        else params.set(k, v);
    });
    const qs = params.toString();
    return qs ? `${indexUrl}?${qs}` : indexUrl;
}

function fetchTable(url) {
    overlay?.classList.remove('hidden');
    overlay?.classList.add('flex');
    if (currentReq) currentReq.abort();
    const ctrl = new AbortController();
    currentReq = ctrl;

    fetch(url, { signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
            history.pushState({}, '', url);
            bindTableControls();
        })
        .catch(err => { if (err.name !== 'AbortError') console.error(err); })
        .finally(() => { overlay?.classList.add('hidden'); overlay?.classList.remove('flex'); currentReq = null; });
}

function bindTableControls() {
    // Per-page select
    document.getElementById('per-page-select')?.addEventListener('change', function () {
        fetchTable(buildUrl({ per_page: this.value }));
    });
}
bindTableControls();

window.addEventListener('popstate', () => fetchTable(window.location.href));

// Pagination links via event delegation (links are inside swapped container)
document.getElementById('users-table-wrapper')?.addEventListener('click', e => {
    const link = e.target.closest('a[href]');
    if (link && link.closest('nav')) { e.preventDefault(); fetchTable(link.href); }
});

// ─── Filter controls ─────────────────────────────────────────────────────────

let searchTimer;
const searchInput = document.getElementById('user-search');
const clearBtn    = document.getElementById('clear-search-btn');
const roleFilter  = document.getElementById('role-filter');
const locFilter   = document.getElementById('location-filter');

searchInput?.addEventListener('input', function () {
    clearBtn?.classList.toggle('hidden', !this.value);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchTable(buildUrl({ search: this.value })), 350);
});

clearBtn?.addEventListener('click', () => {
    searchInput.value = '';
    clearBtn.classList.add('hidden');
    fetchTable(buildUrl({ search: '' }));
});

roleFilter?.addEventListener('change', () => fetchTable(buildUrl({ role: roleFilter.value })));
locFilter?.addEventListener('change',  () => fetchTable(buildUrl({ user_location: locFilter.value })));

document.getElementById('clear-filters-btn')?.addEventListener('click', () => {
    searchInput.value = '';
    roleFilter.value  = '';
    locFilter.value   = '';
    clearBtn?.classList.add('hidden');
    fetchTable(indexUrl);
});

// ─── Modal helpers ────────────────────────────────────────────────────────────

function openModal(id) {
    const m = document.getElementById(id);
    m?.classList.remove('hidden');
    m?.classList.add('flex');
}

function closeModal(id) {
    const m = document.getElementById(id);
    m?.classList.add('hidden');
    m?.classList.remove('flex');
}

// Close on backdrop click or .modal-close buttons
document.addEventListener('click', e => {
    if (e.target.classList.contains('user-modal')) {
        e.target.classList.add('hidden');
        e.target.classList.remove('flex');
    }
    if (e.target.closest('.modal-close')) {
        e.target.closest('.user-modal')?.classList.add('hidden');
        e.target.closest('.user-modal')?.classList.remove('flex');
    }
});

document.getElementById('openAddUserModal')?.addEventListener('click', () => {
    document.getElementById('addUserForm')?.reset();
    document.getElementById('add-form-errors')?.classList.add('hidden');
    openModal('addUserModal');
});

// ─── Add User ────────────────────────────────────────────────────────────────

document.getElementById('addUserForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn      = document.getElementById('add-submit-btn');
    const errBox   = document.getElementById('add-form-errors');
    const formData = new FormData(e.target);
    btn.disabled   = true;
    btn.textContent = 'Creating…';
    errBox.classList.add('hidden');

    try {
        const res  = await fetch(cfg.storeUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        const data = await res.json();

        if (res.ok && data.success) {
            closeModal('addUserModal');
            e.target.reset();
            toast(data.message, 'success');
            fetchTable(buildUrl());
        } else {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join('<br>')
                : (data.message ?? 'An error occurred.');
            errBox.innerHTML = msgs;
            errBox.classList.remove('hidden');
        }
    } catch {
        errBox.innerHTML = 'Network error. Please try again.';
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled   = false;
        btn.textContent = 'Create User';
    }
});

// ─── Edit User ────────────────────────────────────────────────────────────────

document.addEventListener('click', e => {
    const btn = e.target.closest('.edit-user-btn');
    if (!btn) return;

    document.getElementById('edit_user_id').value         = btn.dataset.id;
    document.getElementById('edit_name').value            = btn.dataset.name;
    document.getElementById('edit_email').value           = btn.dataset.email;
    document.getElementById('edit_role').value            = btn.dataset.role;
    document.getElementById('edit_location').value        = btn.dataset.location;
    document.getElementById('edit_password').value        = '';
    document.getElementById('edit_password_confirmation').value = '';
    document.getElementById('edit-form-errors')?.classList.add('hidden');

    openModal('editUserModal');
});

document.getElementById('editUserForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const userId   = document.getElementById('edit_user_id').value;
    const btn      = document.getElementById('edit-submit-btn');
    const errBox   = document.getElementById('edit-form-errors');
    const formData = new FormData(e.target);
    btn.disabled   = true;
    btn.textContent = 'Saving…';
    errBox.classList.add('hidden');

    try {
        const res  = await fetch(`${cfg.usersBase}/${userId}`, {
            method: 'POST', // Laravel tunnel via _method=PUT in FormData
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        const data = await res.json();

        if (res.ok && data.success) {
            closeModal('editUserModal');
            toast(data.message, 'success');
            fetchTable(buildUrl());
        } else {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join('<br>')
                : (data.message ?? 'An error occurred.');
            errBox.innerHTML = msgs;
            errBox.classList.remove('hidden');
        }
    } catch {
        errBox.innerHTML = 'Network error. Please try again.';
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled   = false;
        btn.textContent = 'Save Changes';
    }
});

// ─── Delete User ─────────────────────────────────────────────────────────────

document.addEventListener('click', e => {
    const btn = e.target.closest('.delete-user-btn');
    if (!btn) return;

    const { name, deleteUrl } = btn.dataset;
    let countdown = 5;
    let timerInterval;

    Swal.fire({
        title: 'Delete User',
        html: `<p style="color:#374151">Delete <strong>${name}</strong>? This cannot be undone.</p>
               <div style="margin-top:12px;padding:10px;background:#fef2f2;border-radius:8px;font-size:13px;color:#dc2626">
                   Wait <strong id="swal-countdown">${countdown}</strong>s to confirm…
               </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Delete (${countdown}s)`,
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            const confirmBtn = Swal.getConfirmButton();
            confirmBtn.disabled = true;
            timerInterval = setInterval(() => {
                countdown--;
                const el = document.getElementById('swal-countdown');
                if (el) el.textContent = countdown;
                confirmBtn.textContent = countdown > 0 ? `Delete (${countdown}s)` : 'Confirm Delete';
                if (countdown <= 0) {
                    clearInterval(timerInterval);
                    confirmBtn.disabled = false;
                }
            }, 1000);
        },
        willClose: () => clearInterval(timerInterval),
    }).then(async result => {
        if (!result.isConfirmed) return;

        Swal.fire({ title: 'Deleting…', showConfirmButton: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const res  = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            Swal.close();
            if (data.success) {
                toast(data.message, 'success');
                fetchTable(buildUrl());
            } else {
                toast(data.message ?? 'Delete failed.', 'error');
            }
        } catch {
            Swal.close();
            toast('Network error. Please try again.', 'error');
        }
    });
});
