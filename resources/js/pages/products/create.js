/**
 * Add Products — JS
 * Handles dynamic row add/remove, duplicate SKU validation, AJAX submit
 */

const cfg      = window.createConfig ?? {};
const form     = document.getElementById('product-form');
const rowsList = document.getElementById('product-rows');
const rowTpl   = document.getElementById('product-row-template');
const counter  = document.getElementById('product-counter');
const addBtn   = document.getElementById('add-row-btn');
const clearBtn = document.getElementById('clear-all-btn');
const resultBox= document.getElementById('form-result');

let rowIndex = 0; // monotonically increasing — never reused, avoids duplicate IDs

// ─── Init first row ────────────────────────────────────────────────────────────
// The first row is rendered server-side with data-row="0"
rowIndex = 1;
updateCounter();
updateRemoveBtns();

// ─── Add row ───────────────────────────────────────────────────────────────────
addBtn?.addEventListener('click', () => {
    const clone = rowTpl.content.cloneNode(true);
    const row   = clone.querySelector('.product-row');

    // Replace placeholder index in name/id attributes
    row.querySelectorAll('[data-row-idx]').forEach(el => {
        ['id', 'for', 'name'].forEach(attr => {
            if (el.hasAttribute(attr)) {
                el.setAttribute(attr, el.getAttribute(attr).replace('__IDX__', rowIndex));
            }
        });
        el.removeAttribute('data-row-idx');
    });

    row.dataset.row = rowIndex;
    rowIndex++;

    rowsList.appendChild(row);
    renumberRows();
    updateRemoveBtns();
    updateCounter();

    // Focus SKU input of new row
    setTimeout(() => row.querySelector('input[name="sku[]"]')?.focus(), 60);
});

// ─── Remove row (event delegation) ────────────────────────────────────────────
rowsList.addEventListener('click', e => {
    const btn = e.target.closest('.btn-remove-row');
    if (!btn) return;
    const row = btn.closest('.product-row');
    row.style.transition = 'opacity .2s, transform .2s';
    row.style.opacity    = '0';
    row.style.transform  = 'translateX(12px)';
    setTimeout(() => {
        row.remove();
        renumberRows();
        updateRemoveBtns();
        updateCounter();
    }, 200);
});

// ─── Clear all ────────────────────────────────────────────────────────────────
clearBtn?.addEventListener('click', () => {
    Swal.fire({
        title: 'Clear all rows?',
        text: 'All entered data will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    }).then(r => {
        if (!r.isConfirmed) return;
        // Remove all but the first row; reset first row's fields
        const rows = [...rowsList.querySelectorAll('.product-row')];
        rows.slice(1).forEach(r => r.remove());
        rows[0]?.querySelectorAll('input').forEach(i => i.value = '');
        renumberRows();
        updateRemoveBtns();
        updateCounter();
        resultBox.classList.add('hidden');
        resultBox.innerHTML = '';
    });
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
function renumberRows() {
    rowsList.querySelectorAll('.product-row').forEach((row, i) => {
        const badge = row.querySelector('[data-row-badge]');
        const title = row.querySelector('[data-row-title]');
        if (badge) badge.textContent = i + 1;
        if (title) title.textContent = `Product #${i + 1}`;
    });
}

function updateRemoveBtns() {
    const rows = rowsList.querySelectorAll('.product-row');
    rows.forEach(row => {
        const btn = row.querySelector('.btn-remove-row');
        if (btn) btn.style.display = rows.length === 1 ? 'none' : '';
    });
}

function updateCounter() {
    const n = rowsList.querySelectorAll('.product-row').length;
    if (counter) counter.textContent = n;
}

// ─── Client-side duplicate SKU check ─────────────────────────────────────────
function findDuplicateSkus() {
    const skus   = [...document.querySelectorAll('input[name="sku[]"]')]
        .map(el => el.value.trim().toUpperCase())
        .filter(Boolean);
    const seen   = new Set();
    const dupes  = new Set();
    skus.forEach(s => { if (seen.has(s)) dupes.add(s); else seen.add(s); });
    return [...dupes];
}

// ─── AJAX submit ──────────────────────────────────────────────────────────────
form?.addEventListener('submit', async e => {
    e.preventDefault();

    // Client-side dupe check
    const dupes = findDuplicateSkus();
    if (dupes.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Duplicate SKUs',
            html: `<p>The following SKUs appear more than once:</p><p class="mt-2 font-mono text-sm font-semibold text-red-600">${dupes.join(', ')}</p>`,
            confirmButtonColor: '#4f46e5',
        });
        return;
    }

    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled   = true;
    submitBtn.textContent = 'Saving…';
    resultBox.classList.add('hidden');
    resultBox.innerHTML = '';

    try {
        const res  = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });
        const data = await res.json();

        if (res.ok && data.success) {
            showResult('success', data.message);

            Swal.fire({
                icon: 'success',
                title: 'Products added!',
                html: `<p>${data.message}</p><p class="mt-1 text-sm text-gray-500">Would you like to add more products or go to the list?</p>`,
                showCancelButton: true,
                confirmButtonText: 'Go to Products',
                cancelButtonText: 'Add More',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
            }).then(r => {
                if (r.isConfirmed) {
                    window.location.href = data.redirectUrl ?? cfg.indexUrl;
                } else {
                    // Reset form for another batch
                    const rows = [...rowsList.querySelectorAll('.product-row')];
                    rows.slice(1).forEach(r => r.remove());
                    rows[0]?.querySelectorAll('input').forEach(i => i.value = '');
                    renumberRows(); updateRemoveBtns(); updateCounter();
                    resultBox.classList.add('hidden');
                }
            });
        } else {
            // Validation errors or server error
            const errors = data.errors
                ? Object.values(data.errors).flat()
                : [data.message ?? 'An error occurred.'];
            showResult('error', errors.join('<br>'));
        }
    } catch {
        showResult('error', 'Network error. Please try again.');
    } finally {
        submitBtn.disabled   = false;
        submitBtn.textContent = 'Enroll Products';
    }
});

function showResult(type, html) {
    const isOk = type === 'success';
    resultBox.classList.remove('hidden');
    resultBox.innerHTML = `
        <div style="border-radius:10px;border:1px solid ${isOk ? '#86efac' : '#fca5a5'};background:${isOk ? '#f0fdf4' : '#fef2f2'};padding:12px 16px;font-size:13px;color:${isOk ? '#166534' : '#991b1b'}">
            ${html}
        </div>`;
    resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
