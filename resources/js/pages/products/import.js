/**
 * Product Import — JS
 * Handles drag-and-drop, client-side CSV parse + preview, AJAX upload
 */

const cfg = window.importConfig ?? {};

// ─── Elements ─────────────────────────────────────────────────────────────────
const dropZone      = document.getElementById('drop-zone');
const fileInput     = document.getElementById('csv-file');
const dropIdle      = document.getElementById('drop-idle');
const dropSelected  = document.getElementById('drop-selected');
const selectedName  = document.getElementById('selected-name');
const selectedMeta  = document.getElementById('selected-meta');
const removeFileBtn = document.getElementById('remove-file-btn');
const previewSection= document.getElementById('preview-section');
const previewBody   = document.getElementById('preview-body');
const previewCount  = document.getElementById('preview-count');
const importBtn     = document.getElementById('import-btn');
const progressBar   = document.getElementById('upload-progress-bar');
const progressWrap  = document.getElementById('upload-progress-wrap');
const resultSection = document.getElementById('result-section');
const guideToggle   = document.getElementById('guide-toggle');
const guideContent  = document.getElementById('guide-content');
const guideIcon     = document.getElementById('guide-icon');

// ─── CSV Guide toggle ─────────────────────────────────────────────────────────
guideToggle?.addEventListener('click', () => {
    const hidden = guideContent.classList.toggle('hidden');
    guideIcon.style.transform = hidden ? 'rotate(0deg)' : 'rotate(180deg)';
});

// ─── Drag & drop ─────────────────────────────────────────────────────────────
dropZone?.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone?.addEventListener('dragleave', e => { if (!dropZone.contains(e.relatedTarget)) dropZone.classList.remove('drag-over'); });
dropZone?.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
});
// No click handler needed — the invisible file input covers the full drop zone
// and opens the picker on its own. Adding dropZone click would open it twice.
fileInput?.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });

// ─── Remove file ─────────────────────────────────────────────────────────────
removeFileBtn?.addEventListener('click', e => { e.stopPropagation(); resetState(); });

// ─── State ────────────────────────────────────────────────────────────────────
let parsedData  = null;
let existingSkus = [];
const PREVIEW_CHUNK = 20;
let previewOffset = 0;

// Fetch existing SKUs on load (for update/insert tagging)
fetch(`${cfg.skusUrl}${cfg.currentStore ? '?store=' + cfg.currentStore : ''}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
})
    .then(r => r.json())
    .then(data => { existingSkus = Array.isArray(data) ? data.map(s => String(s).toUpperCase()) : []; })
    .catch(() => {});

// ─── File handling ────────────────────────────────────────────────────────────
function handleFile(file) {
    if (!file.name.toLowerCase().endsWith('.csv')) {
        Swal.fire({ icon: 'error', title: 'Invalid file', text: 'Please select a .csv file.', confirmButtonColor: '#4f46e5' });
        return;
    }

    // Show selected state
    dropIdle.classList.add('hidden');
    dropSelected.classList.remove('hidden');
    selectedName.textContent = file.name;
    selectedMeta.textContent = `${(file.size / 1024).toFixed(1)} KB`;

    const reader = new FileReader();
    reader.onload = e => parseCSV(e.target.result, file);
    reader.readAsText(file);
}

function parseCSV(text, file) {
    const lines = text.split(/\r\n|\r|\n/).filter(l => l.trim());
    if (lines.length < 2) {
        Swal.fire({ icon: 'warning', title: 'Empty file', text: 'CSV must have a header row plus at least one data row.', confirmButtonColor: '#4f46e5' });
        resetState();
        return;
    }

    const dataLines  = lines.slice(1);
    const seenSkus   = new Set();
    const rows       = [];

    dataLines.forEach((line, idx) => {
        const cols = parseCSVLine(line);
        const rowNum = idx + 2;
        if (cols.length < 9) {
            rows.push({ rowNum, sku: cols[0] ?? '', description: cols[1] ?? '', allocation: '', casePack: '', srp: '', cbc: '', po15: '', discount: '', freebie: '', action: 'invalid', reason: 'Missing columns' });
            return;
        }

        const [sku, description, allocation, casePack, srp, cbc, po15, discount, freebieRaw] = cols;
        const freebie = freebieRaw.replace(/\s+/g, '');
        const upperSku = sku.toUpperCase();
        let isValid = true;
        let reason  = '';

        if (!sku || !/^\d+$/.test(sku))                            { isValid = false; reason = 'SKU must be numeric'; }
        else if (!description)                                       { isValid = false; reason = 'Missing description'; }
        else if (!allocation || isNaN(allocation))                   { isValid = false; reason = 'Invalid allocation'; }
        else if (casePack && !/^\d+( \| \d+)*$/.test(casePack))    { isValid = false; reason = 'Invalid case pack'; }
        else if (!srp || isNaN(srp.replace(/[₱,]/g, '')))          { isValid = false; reason = 'Invalid SRP'; }
        else if (cbc && !/^\d+\+\d+$/.test(cbc))                   { isValid = false; reason = 'Invalid C/BC scheme'; }
        else if (po15 && !/^\d+\+\d+$/.test(po15))                 { isValid = false; reason = 'Invalid PO15 scheme'; }
        else if (discount && !/^\d+%?$/.test(discount.trim()))      { isValid = false; reason = 'Invalid discount'; }
        else if (freebie && !/^\d+(\s*[\/|]\s*\d+)*$/.test(freebie)) { isValid = false; reason = 'Invalid freebie SKU'; }

        let action;
        if (!isValid)                 action = 'invalid';
        else if (seenSkus.has(upperSku)) action = 'duplicate';
        else { seenSkus.add(upperSku); action = existingSkus.includes(upperSku) ? 'update' : 'insert'; }

        rows.push({ rowNum, sku, description, allocation, casePack, srp, cbc, po15, discount, freebie, action, reason });
    });

    parsedData = rows;
    selectedMeta.textContent = `${(file.size / 1024).toFixed(1)} KB · ${rows.length} rows`;

    renderPreview();
    importBtn?.classList.remove('hidden');

    const hasUpdates = rows.some(r => r.action === 'update');
    if (hasUpdates) {
        Swal.fire({
            icon: 'warning', title: 'Heads up!',
            text: 'Some SKUs already exist and will be updated. Please review the preview.',
            confirmButtonColor: '#f59e0b', confirmButtonText: 'Got it',
        });
    }
}

function parseCSVLine(line) {
    const cols = [];
    let current = '', inQuotes = false;
    for (const char of line) {
        if (char === '"')        { inQuotes = !inQuotes; }
        else if (char === ',' && !inQuotes) { cols.push(current.trim()); current = ''; }
        else                     { current += char; }
    }
    cols.push(current.trim());
    return cols;
}

// ─── Preview ──────────────────────────────────────────────────────────────────
function renderPreview() {
    previewOffset = 0;
    previewBody.innerHTML = '';
    previewSection.classList.remove('hidden');

    const counts = { insert: 0, update: 0, invalid: 0, duplicate: 0 };
    parsedData.forEach(r => counts[r.action] = (counts[r.action] || 0) + 1);

    previewCount.innerHTML = [
        counts.insert    ? `<span style="background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:9999px;padding:2px 10px;font-size:11px;font-weight:600">${counts.insert} new</span>` : '',
        counts.update    ? `<span style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:9999px;padding:2px 10px;font-size:11px;font-weight:600">${counts.update} update</span>` : '',
        counts.invalid   ? `<span style="background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:9999px;padding:2px 10px;font-size:11px;font-weight:600">${counts.invalid} invalid</span>` : '',
        counts.duplicate ? `<span style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:9999px;padding:2px 10px;font-size:11px;font-weight:600">${counts.duplicate} duplicate</span>` : '',
    ].join('');

    appendPreviewChunk();
}

function appendPreviewChunk() {
    const chunk = parsedData.slice(previewOffset, previewOffset + PREVIEW_CHUNK);
    const actionStyles = {
        insert:    { bg: '#f0fdf4', border: '#86efac', color: '#166534', label: 'New' },
        update:    { bg: '#eff6ff', border: '#bfdbfe', color: '#1e40af', label: 'Update' },
        invalid:   { bg: '#fef2f2', border: '#fca5a5', color: '#991b1b', label: 'Invalid' },
        duplicate: { bg: '#fff7ed', border: '#fed7aa', color: '#9a3412', label: 'Duplicate' },
    };

    chunk.forEach(row => {
        const s = actionStyles[row.action] ?? actionStyles.invalid;
        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #f1f5f9';
        tr.innerHTML = `
            <td style="padding:8px 10px;font-size:11px;color:#9ca3af">${row.rowNum}</td>
            <td style="padding:8px 10px;font-size:11px;font-family:monospace;font-weight:600;color:#1e40af">${esc(row.sku)}</td>
            <td style="padding:8px 10px;font-size:11px;color:#374151;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(row.description)}</td>
            <td style="padding:8px 10px;font-size:11px;color:#374151">${esc(row.allocation)}</td>
            <td style="padding:8px 10px;font-size:11px;color:#374151">${esc(row.casePack)}</td>
            <td style="padding:8px 10px;font-size:11px;color:#374151">${esc(row.srp)}</td>
            <td style="padding:8px 10px;font-size:11px;color:#6d28d9">${esc(row.cbc) || '–'}</td>
            <td style="padding:8px 10px;font-size:11px;color:#6d28d9">${esc(row.po15) || '–'}</td>
            <td style="padding:8px 10px;font-size:11px;color:#6d28d9">${esc(row.discount) || '–'}</td>
            <td style="padding:8px 10px;font-size:11px;color:#7e22ce">${esc(row.freebie) || '–'}</td>
            <td style="padding:8px 10px">
                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 8px;font-size:10px;font-weight:600;background:${s.bg};border:1px solid ${s.border};color:${s.color}"
                    title="${esc(row.reason ?? '')}">
                    ${s.label}
                </span>
            </td>`;
        previewBody.appendChild(tr);
    });

    previewOffset += PREVIEW_CHUNK;

    // Load-more row
    document.getElementById('load-more-row')?.remove();
    if (previewOffset < parsedData.length) {
        const tr = document.createElement('tr');
        tr.id = 'load-more-row';
        tr.innerHTML = `<td colspan="11" style="padding:12px;text-align:center">
            <button id="load-more-btn" style="font-size:12px;color:#4f46e5;font-weight:500;cursor:pointer;border:none;background:none">
                Load ${Math.min(PREVIEW_CHUNK, parsedData.length - previewOffset)} more rows…
            </button></td>`;
        previewBody.appendChild(tr);
        document.getElementById('load-more-btn').addEventListener('click', appendPreviewChunk);
    }
}

// ─── AJAX Import ──────────────────────────────────────────────────────────────
importBtn?.addEventListener('click', async () => {
    if (!fileInput.files[0]) return;

    const validCount = parsedData?.filter(r => r.action === 'insert' || r.action === 'update').length ?? 0;
    if (validCount === 0) {
        Swal.fire({ icon: 'warning', title: 'Nothing to import', text: 'No valid rows found in the file.', confirmButtonColor: '#4f46e5' });
        return;
    }

    const { isConfirmed } = await Swal.fire({
        icon: 'question',
        title: 'Confirm import',
        html: `<p style="color:#374151">Import <strong>${validCount}</strong> valid row(s) into the selected store?</p>`,
        showCancelButton: true,
        confirmButtonText: 'Yes, import',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
    });
    if (!isConfirmed) return;

    // Show progress
    importBtn.disabled = true;
    progressWrap.classList.remove('hidden');
    resultSection.classList.add('hidden');

    // Animate progress bar (XHR for actual upload progress)
    const formData = new FormData();
    formData.append('_token', cfg.csrfToken);
    formData.append('csv_file', fileInput.files[0]);
    formData.append('store', document.querySelector('[name="store"]')?.value ?? cfg.currentStore ?? '');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.uploadUrl);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.upload.addEventListener('progress', e => {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 80); // upload = 80%, server = 20%
            progressBar.style.width = pct + '%';
        }
    });

    xhr.addEventListener('load', () => {
        progressBar.style.width = '100%';
        setTimeout(() => {
            progressWrap.classList.add('hidden');
            progressBar.style.width = '0%';
            importBtn.disabled = false;

            let data;
            try { data = JSON.parse(xhr.responseText); } catch { data = { success: false, message: 'Unexpected server response.' }; }

            showResult(data);
        }, 400);
    });

    xhr.addEventListener('error', () => {
        progressWrap.classList.add('hidden');
        importBtn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Upload failed', text: 'Network error. Please try again.', confirmButtonColor: '#ef4444' });
    });

    xhr.send(formData);
});

function showResult(data) {
    resultSection.classList.remove('hidden');
    const isSuccess = data.success;

    resultSection.innerHTML = `
        <div style="border-radius:12px;border:1px solid ${isSuccess ? '#86efac' : '#fca5a5'};background:${isSuccess ? '#f0fdf4' : '#fef2f2'};padding:16px 20px">
            <div style="display:flex;align-items:flex-start;gap:12px">
                <div style="flex-shrink:0;width:36px;height:36px;border-radius:50%;background:${isSuccess ? '#dcfce7' : '#fee2e2'};display:flex;align-items:center;justify-content:center">
                    <svg width="18" height="18" fill="none" stroke="${isSuccess ? '#16a34a' : '#dc2626'}" viewBox="0 0 24 24">
                        ${isSuccess
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                    </svg>
                </div>
                <div style="flex:1">
                    <p style="font-size:14px;font-weight:600;color:${isSuccess ? '#166534' : '#991b1b'}">${esc(data.message ?? (isSuccess ? 'Import complete.' : 'Import failed.'))}</p>
                    ${isSuccess && (data.inserted || data.updated) ? `
                        <div style="display:flex;gap:12px;margin-top:8px">
                            ${data.inserted ? `<span style="font-size:12px;color:#166534">✓ ${data.inserted} inserted</span>` : ''}
                            ${data.updated  ? `<span style="font-size:12px;color:#1e40af">↺ ${data.updated} updated</span>` : ''}
                        </div>` : ''}
                    ${data.errors?.length ? `
                        <div style="margin-top:10px">
                            <p style="font-size:12px;font-weight:600;color:#92400e;margin-bottom:4px">${data.errors.length} row(s) skipped:</p>
                            <ul style="max-height:140px;overflow-y:auto;font-size:11px;color:#92400e;padding-left:16px;list-style:disc">
                                ${data.errors.map(e => `<li>${esc(e)}</li>`).join('')}
                            </ul>
                        </div>` : ''}
                </div>
                ${isSuccess ? `
                    <a href="${cfg.productsUrl}" style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;height:32px;padding:0 12px;border-radius:8px;background:#4f46e5;color:#fff;font-size:12px;font-weight:500;text-decoration:none">
                        View Products
                    </a>` : ''}
            </div>
        </div>`;

    resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ─── Reset ────────────────────────────────────────────────────────────────────
function resetState() {
    fileInput.value       = '';
    parsedData            = null;
    previewOffset         = 0;
    dropIdle.classList.remove('hidden');
    dropSelected.classList.add('hidden');
    previewSection.classList.add('hidden');
    importBtn?.classList.add('hidden');
    previewBody.innerHTML = '';
    resultSection.classList.add('hidden');
    resultSection.innerHTML = '';
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function esc(str) {
    if (str == null) return '';
    return String(str).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
}

// ─── Download template ────────────────────────────────────────────────────────
document.getElementById('download-template-btn')?.addEventListener('click', e => {
    e.preventDefault();
    const csv = 'SKU,Product Description,Store Allocation,Case Pack,SRP,Cash Bank Card Scheme,PO15 Scheme,Discount Scheme,Freebie SKU\n102806178,Bearbrand Pwdr Mlk 128-192/33G,500,192,11.20,15+1,15+2,10%,9413022 | 8404794\n8404794,Lucky Me Pc Xtra Hot Chi72/60G,600,72,11.50,10+1,8+1,66,8404794';
    const a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download = 'product_import_template.csv';
    a.click();
});
