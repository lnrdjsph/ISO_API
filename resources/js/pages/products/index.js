import '../../../css/pages/products.css';

/**
 * Products Index — JS
 *
 * Covers:
 *  - WMS allocation update (submit → polling → completion)
 *  - Product search autocomplete
 *  - Bulk-select, bulk-edit, bulk-archive
 *  - Store / warehouse / per-page selectors
 *  - Scroll-position restore across pagination
 */

// ─── Globals injected by the Blade partial ───────────────────────────────────
// window.productsConfig = { routes, currentStore, currentWarehouse, csrfToken }

const cfg = window.productsConfig ?? {};
const routes = cfg.routes ?? {};

// ─── AJAX Table Navigation ────────────────────────────────────────────────────

(function initAjaxNavigation() {
    const container = document.getElementById('products-table-container');
    const overlay   = document.getElementById('products-loading');
    const indexUrl  = cfg.indexUrl ?? window.location.pathname;

    if (!container) return;

    // Build a URL from current params merged with overrides
    function buildUrl(overrides = {}) {
        const params = new URLSearchParams(window.location.search);
        // Reset page when changing filters
        const resetsPage = Object.keys(overrides).some(k => k !== 'page');
        if (resetsPage) params.delete('page');
        Object.entries(overrides).forEach(([k, v]) => {
            if (v === '' || v === null || v === undefined) params.delete(k);
            else params.set(k, v);
        });
        const qs = params.toString();
        return qs ? `${indexUrl}?${qs}` : indexUrl;
    }

    function showOverlay() {
        overlay?.classList.remove('hidden');
        overlay?.classList.add('flex');
    }
    function hideOverlay() {
        overlay?.classList.add('hidden');
        overlay?.classList.remove('flex');
    }

    // Fetch the table partial and swap it in
    let currentRequest = null;
    function fetchTable(url) {
        showOverlay();
        if (currentRequest) currentRequest.abort();
        const controller = new AbortController();
        currentRequest   = controller;

        fetch(url, {
            signal: controller.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.text())
            .then(html => {
                container.innerHTML = html;
                history.pushState({}, '', url);
                // Re-bind per-page select inside newly swapped content
                bindPerPage();
            })
            .catch(err => { if (err.name !== 'AbortError') console.error(err); })
            .finally(() => { hideOverlay(); currentRequest = null; });
    }

    // ── Per-page select (inside swappable container — needs rebind after swap) ─
    function bindPerPage() {
        document.getElementById('per-page-select')?.addEventListener('change', function () {
            fetchTable(buildUrl({ per_page: this.value }));
        });
    }
    bindPerPage();

    // ── Sort links + pagination links (event delegation on persistent wrapper) ─
    document.getElementById('products-table-wrapper')?.addEventListener('click', e => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const isSort       = !!link.closest('thead');
        const isPagination = !!link.closest('#pagination-links');
        if (!isSort && !isPagination) return;
        e.preventDefault();
        fetchTable(link.href);
    });

    // ── Store selector ────────────────────────────────────────────────────────
    document.getElementById('store-select')?.addEventListener('change', function () {
        fetchTable(buildUrl({ store: this.value, warehouse: '', page: '' }));
    });

    // ── Warehouse selector ────────────────────────────────────────────────────
    document.getElementById('warehouse-select')?.addEventListener('change', function () {
        fetchTable(buildUrl({ warehouse: this.value }));
    });

    // ── Search form (submit + debounced input) ────────────────────────────────
    let searchTimer;
    const searchInput = document.getElementById('product-search');
    const clearBtn    = document.getElementById('clear-search-btn');

    searchInput?.addEventListener('input', function () {
        clearBtn?.classList.toggle('hidden', !this.value);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            fetchTable(buildUrl({ query: this.value }));
        }, 350);
    });

    searchInput?.closest('form')?.addEventListener('submit', e => {
        e.preventDefault();
        clearTimeout(searchTimer);
        fetchTable(buildUrl({ query: searchInput.value }));
    });

    clearBtn?.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        fetchTable(buildUrl({ query: '' }));
    });

    // ── Autocomplete item click — filter table to that SKU ───────────────────
    // (Overrides the full-form-submit in the search module below)
    document.addEventListener('click', e => {
        const item = e.target.closest('.product-item');
        if (!item) return;
        e.stopImmediatePropagation(); // prevent duplicate handlers
        item.classList.add('bg-blue-100/60', 'scale-95');
        const sku = item.dataset.sku;
        if (sku) {
            searchInput.value = sku;
            document.getElementById('product-list').innerHTML = '';
            document.getElementById('product-list').classList.add('hidden');
            clearBtn?.classList.remove('hidden');
            fetchTable(buildUrl({ query: sku }));
        }
    }, true); // capture phase so it fires before the search module's handler

    // ── Browser back/forward ─────────────────────────────────────────────────
    window.addEventListener('popstate', () => {
        fetchTable(window.location.href);
    });
})();

// ─── Helpers ─────────────────────────────────────────────────────────────────

function fetchWithTimeout(url, options = {}, timeout = 300_000) {
    return Promise.race([
        fetch(url, options),
        new Promise((_, reject) =>
            setTimeout(() => reject(new Error('Request timeout')), timeout)
        ),
    ]);
}

// ─── WMS Allocation Update ────────────────────────────────────────────────────

(function initWmsUpdate() {
    const form          = document.getElementById('updateAllocationsForm');
    const button        = document.getElementById('updateButton');
    const loader        = document.getElementById('pageLoader');
    const loaderTitle   = document.getElementById('loaderTitle');
    const loaderMessage = document.getElementById('loaderMessage');
    const progressDetails = document.getElementById('progressDetails');
    const progressText  = document.getElementById('progressText');
    const progressBar   = document.getElementById('progressBar');

    if (!form) return;

    const getSelectedWarehouse = () =>
        document.querySelector('select[name="warehouse"]')?.value ?? '';
    const getSelectedStore = () =>
        document.querySelector('select[name="store"]')?.value ?? cfg.currentStore ?? '';

    sessionStorage.removeItem('allocUpdateDone');

    const showLoader = () => { loader.classList.remove('hidden'); loader.classList.add('flex'); };
    const hideLoader = () => { loader.classList.add('hidden');    loader.classList.remove('flex'); };

    let checkStatusInterval  = null;
    let progressPercentage   = 0;
    let consecutiveErrorCount = 0;
    let pollingBusy          = false;

    // ── Connection check ──────────────────────────────────────────────────

    async function checkConnection() {
        try {
            const res = await fetchWithTimeout(
                `${routes.wmsStatus}?warehouse=${getSelectedWarehouse()}&store=${getSelectedStore()}&t=${Date.now()}`,
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' },
                10_000
            );
            if (!res.ok) return false;
            consecutiveErrorCount = 0;
            return true;
        } catch {
            return false;
        }
    }

    // ── Submit ────────────────────────────────────────────────────────────

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Show a non-blocking centered checking indicator
        Swal.fire({
            title: 'Checking connection…',
            text: 'Please wait.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });

        const alive = await checkConnection();
        Swal.close();

        if (!alive) {
            Swal.fire({ title: 'Connection Error', text: 'Unable to reach the server. Please check your network and try again.', icon: 'error', confirmButtonColor: '#ef4444' });
            return;
        }

        const warehouse = getSelectedWarehouse();
        let statusData  = null;

        try {
            const statusRes = await fetchWithTimeout(
                `${routes.wmsStatus}?warehouse=${warehouse}&store=${getSelectedStore()}&t=${Date.now()}`,
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' },
                10_000
            );
            if (statusRes.ok) statusData = await statusRes.json();
        } catch { /* ignore */ }

        if (statusData?.status === 'running') {
            showLoader();
            loaderTitle.textContent  = `Update in Progress (${statusData.progress?.percentage ?? 0}%)`;
            loaderMessage.textContent = statusData.message ?? 'Processing...';
            if (statusData.progress) {
                progressDetails.classList.remove('hidden');
                progressText.textContent = `Processing: ${statusData.progress.processed ?? 0} / ${statusData.progress.total ?? 0} SKUs`;
                progressBar.style.width  = `${statusData.progress.percentage ?? 0}%`;
            }
            startPolling();
            return;
        }

        Swal.fire({
            title: 'Warning',
            html: '<p>This will update WMS Actual Allocation, WMS Virtual Allocation, and Case Pack data. Continue?</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
        }).then((result) => { if (result.isConfirmed) startUpdate(); });
    });

    // ── Start update ──────────────────────────────────────────────────────

    function startUpdate() {
        button.disabled          = true;
        consecutiveErrorCount    = 0;
        progressPercentage       = 10;
        showLoader();
        loaderTitle.textContent  = 'Initializing Update...';
        loaderMessage.textContent = 'Starting allocation update process...';
        progressBar.style.width  = '10%';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': cfg.csrfToken,
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ warehouse: getSelectedWarehouse(), store: getSelectedStore() }),
        })
            .then((res) => {
                if (!res.ok) return res.json().then((d) => { throw new Error(d.message ?? `Server error: ${res.status}`); });
                return res.json();
            })
            .then((data) => {
                if (data.status === 'started' || data.status === 'running') {
                    loaderTitle.textContent  = 'Update in Progress';
                    loaderMessage.textContent = data.message ?? 'Processing...';
                    progressBar.style.width  = '25%';
                    progressPercentage       = 25;
                    setTimeout(startPolling, 2000);
                } else {
                    showError(data.message ?? 'Unexpected response.');
                }
            })
            .catch((err) => showError(err.message ?? 'Failed to start update.'));
    }

    // ── Polling ───────────────────────────────────────────────────────────

    function startPolling() {
        checkStatusInterval = setInterval(checkStatus, 3000);
    }

    function stopPolling() {
        if (checkStatusInterval) {
            clearInterval(checkStatusInterval);
            checkStatusInterval = null;
        }
    }

    function checkStatus() {
        if (pollingBusy) return;
        pollingBusy = true;

        fetchWithTimeout(
            `${routes.wmsStatus}?warehouse=${getSelectedWarehouse()}&store=${getSelectedStore()}&t=${Date.now()}`,
            { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' },
            15_000
        )
            .then((res) => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.json(); })
            .then((data) => {
                consecutiveErrorCount = 0;
                if (data.status === 'done')    { stopPolling(); handleCompletion(data); return; }
                if (data.status === 'running') { updateProgress(data); return; }
                if (data.status === 'idle')    { loaderMessage.textContent = 'Waiting for jobs to start...'; return; }
                if (data.status === 'error')   { stopPolling(); showError(data.message); }
            })
            .catch((err) => {
                consecutiveErrorCount++;
                loaderMessage.textContent = err.message === 'Request timeout'
                    ? '⚠️ Server slow, retrying...'
                    : '⚠️ Connection issue, retrying...';
                if (consecutiveErrorCount > 5) { stopPolling(); showError('Lost connection to server. Please refresh.'); }
            })
            .finally(() => { pollingBusy = false; });
    }

    // ── Progress ──────────────────────────────────────────────────────────

    function updateProgress(data) {
        loaderMessage.textContent = data.message ?? 'Processing...';
        if (!data.progress) return;

        if (data.progress.current_step) {
            progressDetails.classList.remove('hidden');
            progressText.textContent = data.progress.current_step;
        }
        if (data.progress.percentage !== undefined) {
            progressPercentage = Math.max(progressPercentage, data.progress.percentage);
            progressBar.style.width = `${progressPercentage}%`;
            loaderTitle.textContent = `Update in Progress (${Math.round(progressPercentage)}%)`;
            if (data.progress.processed !== undefined && data.progress.total !== undefined) {
                progressText.textContent = `Processing: ${data.progress.processed} / ${data.progress.total} SKUs`;
                if (data.progress.failed > 0) progressText.textContent += ` (${data.progress.failed} failed)`;
                if (data.progress.elapsed_time) progressText.textContent += ` • ${data.progress.elapsed_time} min elapsed`;
            }
        } else if (progressPercentage < 90) {
            progressPercentage += Math.random() * 2;
            progressBar.style.width = `${Math.min(progressPercentage, 90)}%`;
        }
    }

    // ── Completion ────────────────────────────────────────────────────────

    function handleCompletion(data) {
        progressBar.style.width = '100%';
        loaderTitle.textContent = 'Update in Progress (100%)';

        setTimeout(() => {
            hideLoader();
            button.disabled = false;

            if (sessionStorage.getItem('allocUpdateDone')) { stopPolling(); return; }

            let summaryHtml = '<p class="mb-2">Update completed successfully!</p>';
            if (data.summary) {
                summaryHtml += '<div class="text-left bg-green-50 p-3 rounded-lg mt-3"><p class="text-sm font-semibold mb-2">Summary:</p><ul class="text-sm space-y-1">';
                if (data.summary.processed_skus !== undefined) summaryHtml += `<li>✓ SKUs Processed: <strong>${data.summary.processed_skus}</strong></li>`;
                if (data.summary.failed_skus > 0)              summaryHtml += `<li>⚠ SKUs Failed: <strong>${data.summary.failed_skus}</strong></li>`;
                if (data.summary.warehouse_name)               summaryHtml += `<li>📦 Warehouse: <strong>${data.summary.warehouse_name}</strong></li>`;
                if (data.summary.started_at && data.summary.completed_at) {
                    const duration = Math.floor((new Date(data.summary.completed_at) - new Date(data.summary.started_at)) / 1000);
                    summaryHtml += `<li>⏱ Duration: <strong>${Math.floor(duration / 60)}m ${duration % 60}s</strong></li>`;
                }
                summaryHtml += '</ul></div>';
            }

            sessionStorage.setItem('allocUpdateDone', '1');
            Swal.fire({ title: 'Completed!', html: summaryHtml, icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#10b981', allowOutsideClick: false, allowEscapeKey: false })
                .then(() => { stopPolling(); window.location.reload(); sessionStorage.removeItem('allocUpdateDone'); });

            progressPercentage = 0;
            progressBar.style.width = '0%';
            progressDetails.classList.add('hidden');
        }, 500);
    }

    // ── Error ─────────────────────────────────────────────────────────────

    function showError(message) {
        stopPolling();
        hideLoader();
        button.disabled = false;
        Swal.fire({ title: 'Error', text: message ?? 'Failed to update allocations.', icon: 'error', confirmButtonColor: '#ef4444' });
        progressPercentage = 0;
        progressBar.style.width = '0%';
        progressDetails.classList.add('hidden');
    }

    // Auto-check on load (resume in-progress update)
    setTimeout(() => {
        checkConnection().then((connected) => {
            if (connected && !sessionStorage.getItem('allocUpdateDone')) checkStatus();
        });
    }, 500);
})();

// ─── Search Autocomplete ──────────────────────────────────────────────────────

(function initSearch() {
    const searchInput = document.getElementById('product-search');
    const productList = document.getElementById('product-list');
    const clearBtn    = document.getElementById('clear-search-btn');

    if (!searchInput || !productList) return;

    let debounceTimeout;

    function highlightMatch(text, query) {
        if (!query) return text;
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark class="bg-yellow-200">$1</mark>');
    }

    function getStore() {
        return document.querySelector('select[name="store"]')?.value ?? cfg.currentStore ?? '';
    }

    function getWarehouse() {
        return document.querySelector('select[name="warehouse"]')?.value ?? cfg.currentWarehouse ?? '';
    }

    function performSearch(query) {
        searchInput.classList.add('animate-pulse');
        productList.classList.remove('hidden');
        productList.innerHTML = `<li class="px-2 py-2 text-gray-600 flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg> Searching products...</li>`;

        debounceTimeout = setTimeout(() => {
            const url = new URL(routes.search);
            url.searchParams.set('query', query);
            url.searchParams.set('store', getStore());
            url.searchParams.set('warehouse', getWarehouse());

            fetch(url)
                .then((res) => res.json())
                .then((data) => {
                    searchInput.classList.remove('animate-pulse');
                    productList.innerHTML = '';

                    const cleanQuery = query.replace(/[^a-z0-9]/gi, '').toLowerCase();
                    const filtered   = data.filter((item) => {
                        const clean = (s) => (s ?? '').replace(/[^a-z0-9]/gi, '').toLowerCase();
                        return clean(item.sku).includes(cleanQuery)
                            || clean(item.description).includes(cleanQuery)
                            || clean(item.department).includes(cleanQuery)
                            || clean(item.department_code).includes(cleanQuery);
                    });

                    if (filtered.length === 0) {
                        productList.innerHTML = `<li class="px-4 py-5 text-center text-gray-500">
                            <svg class="mx-auto mb-2 h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-sm font-medium text-gray-600">No products found</p>
                            <p class="mt-0.5 text-xs text-gray-400">Try a different SKU or keyword</p>
                        </li>`;
                        return;
                    }

                    // Result count header
                    const countEl = document.createElement('li');
                    countEl.className = 'px-3 py-1.5 border-b border-gray-100 flex items-center justify-between';
                    countEl.innerHTML = `<span class="text-[10px] font-medium uppercase tracking-wider text-gray-400">Results</span>
                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">${filtered.length}</span>`;
                    productList.appendChild(countEl);

                    filtered.forEach((item, index) => {
                        const li = document.createElement('li');
                        li.className = 'px-2 py-2 hover:bg-gray-50/80 cursor-pointer product-item transition-all duration-200 border-b border-gray-100/60 last:border-b-0 opacity-0 translate-y-2';
                        li.dataset.sku = item.sku;
                        li.innerHTML   = `<div class="flex items-center max-h-10">
                            <div class="flex-1 flex items-center">
                                <span class="text-[10px] font-mono font-medium text-gray-500 px-2 py-1 bg-gray-100/60 rounded mr-3">${item.sku}</span>
                                <span class="font-medium text-gray-800">${highlightMatch(item.description, query)}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>`;
                        setTimeout(() => li.classList.replace('opacity-0', 'opacity-100'), index * 50);
                        productList.appendChild(li);
                    });
                })
                .catch(() => {
                    searchInput.classList.remove('animate-pulse');
                    productList.innerHTML = `<li class="px-2 py-2 text-red-600">Search failed. Please try again.</li>`;
                });
        }, 300);
    }

    searchInput.addEventListener('keyup', () => {
        clearTimeout(debounceTimeout);
        const q = searchInput.value.toLowerCase();
        if (q.length >= 2) performSearch(q);
        else productList.innerHTML = '', productList.classList.add('hidden');
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.length >= 2) performSearch(searchInput.value.toLowerCase());
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#product-search') && !e.target.closest('#product-list')) {
            productList.innerHTML = '';
            productList.classList.add('hidden');
        }
    });

    // Product item click is handled by the AJAX navigation module (capture phase)

    // clear-btn and form submit handled by AJAX navigation module
})();

// ─── Bulk Select / Edit / Archive ─────────────────────────────────────────────

(function initBulkActions() {
    const selectedProducts = new Set();

    function updateBar() {
        const bar   = document.getElementById('bulk-actions-bar');
        const count = selectedProducts.size;
        if (!bar) return;
        if (count > 0) {
            bar.classList.remove('hidden');
            document.getElementById('selected-count').textContent           = count;
            document.getElementById('bulk-edit-selected-count').textContent = count;
            document.getElementById('archive-selected-count').textContent   = count;
        } else {
            bar.classList.add('hidden');
        }
    }

    function showNotification(message, type = 'info') {
        const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
        const el = document.createElement('div');
        el.className = `fixed top-4 right-4 z-50 ${colors[type] ?? colors.info} text-white px-4 py-2 rounded-2xl shadow-lg translate-x-full transition-transform duration-300`;
        el.innerHTML = `<div class="flex items-center gap-3"><span>${message}</span>
            <button class="ml-auto text-white hover:text-gray-200" onclick="this.closest('div').parentElement.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button></div>`;
        document.body.appendChild(el);
        setTimeout(() => el.classList.remove('translate-x-full'), 100);
        setTimeout(() => { el.classList.add('translate-x-full'); setTimeout(() => el.remove(), 300); }, 5000);
    }

    // Select all
    document.getElementById('select-all')?.addEventListener('change', function () {
        document.querySelectorAll('.product-checkbox').forEach((cb) => {
            cb.checked = this.checked;
            this.checked ? selectedProducts.add(cb.value) : selectedProducts.delete(cb.value);
        });
        updateBar();
    });

    // Individual checkbox
    document.addEventListener('change', (e) => {
        if (!e.target.classList.contains('product-checkbox')) return;
        e.target.checked ? selectedProducts.add(e.target.value) : selectedProducts.delete(e.target.value);
        const all     = document.querySelectorAll('.product-checkbox');
        const checked = document.querySelectorAll('.product-checkbox:checked');
        const selectAll = document.getElementById('select-all');
        if (selectAll) selectAll.checked = all.length > 0 && checked.length === all.length;
        updateBar();
    });

    // Clear selection
    document.getElementById('clear-selection-btn')?.addEventListener('click', () => {
        selectedProducts.clear();
        document.querySelectorAll('.product-checkbox, #select-all').forEach((cb) => (cb.checked = false));
        updateBar();
    });

    // Bulk edit modal
    document.getElementById('bulk-edit-btn')?.addEventListener('click', () => {
        if (!selectedProducts.size) { alert('Please select products first'); return; }
        document.getElementById('bulk-edit-modal').classList.remove('hidden');
    });

    ['close-bulk-edit', 'cancel-bulk-edit'].forEach((id) => {
        document.getElementById(id)?.addEventListener('click', () =>
            document.getElementById('bulk-edit-modal').classList.add('hidden')
        );
    });

    document.getElementById('bulk-edit-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form     = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        selectedProducts.forEach((id) => formData.append('product_ids[]', id));

        submitBtn.disabled   = true;
        submitBtn.textContent = 'Updating...';

        try {
            const res  = await fetch(routes.bulkUpdate, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            const data = await res.json();
            if (res.ok) {
                showNotification('Products updated successfully!', 'success');
                document.getElementById('bulk-edit-modal').classList.add('hidden');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification(data.message ?? 'An error occurred.', 'error');
            }
        } catch {
            showNotification('An error occurred while updating products.', 'error');
        } finally {
            submitBtn.disabled   = false;
            submitBtn.textContent = 'Update Products';
        }
    });

    // Bulk archive modal
    document.getElementById('bulk-archive-btn')?.addEventListener('click', () => {
        if (!selectedProducts.size) { alert('Please select products first'); return; }
        document.getElementById('bulk-archive-modal').classList.remove('hidden');
    });

    document.getElementById('cancel-bulk-archive')?.addEventListener('click', () =>
        document.getElementById('bulk-archive-modal').classList.add('hidden')
    );

    document.getElementById('confirm-bulk-archive')?.addEventListener('click', async function () {
        const btn           = this;
        const archiveReason = document.getElementById('archive-reason-input').value;
        btn.disabled        = true;
        btn.textContent     = 'Archiving...';

        try {
            const res  = await fetch(routes.bulkArchive, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ product_ids: Array.from(selectedProducts), archive_reason: archiveReason }),
            });
            const data = await res.json();
            showNotification(data.message ?? 'Done.', data.success ? 'success' : 'error');
            if (data.success) {
                document.getElementById('bulk-archive-modal').classList.add('hidden');
                setTimeout(() => location.reload(), 1000);
            }
        } catch {
            showNotification('An error occurred while archiving.', 'error');
        } finally {
            btn.disabled   = false;
            btn.textContent = 'Archive Products';
        }
    });

    // Close modals on backdrop click
    ['bulk-edit-modal', 'bulk-archive-modal'].forEach((id) => {
        document.getElementById(id)?.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
        });
    });
})();

// ─── Misc ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    // "Add Products" overlay select — still needs full navigation
    ['no-products-action', 'no-products-action-alt'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', function () {
            if (this.value) window.location.href = this.value;
        });
    });
});
