let debounceTimeout;

export function initProductSearch() {
    document.addEventListener('keyup', handleSearchInput, true);
    document.addEventListener('focus', handleSearchInput, true);
    document.addEventListener('click', handleProductItemClick, true);
}

function handleSearchInput(event) {
    const input = event.target;
    if (!input.classList.contains('product-search') && !input.classList.contains('freebie-search')) {
        return;
    }

    clearTimeout(debounceTimeout);
    if (input.value.trim().length >= 2) {
        debounceTimeout = setTimeout(() => performSearch(input), 300);
    } else {
        const resultsList = input.nextElementSibling;
        if (resultsList?.classList.contains('search-results')) {
            resultsList.innerHTML = '';
            resultsList.classList.add('hidden');
        }
    }
}

function handleProductItemClick(event) {
    const selected = event.target.closest('.product-item');
    if (!selected) return;

    const row = selected.closest('.order-row');
    if (!row) return;

    const sku = selected.dataset.sku;
    const description = selected.dataset.description;
    const pricePerPc = selected.dataset.srp || '';
    const casePack = selected.dataset.case_pack || '';
    const isFreebie = selected.closest('.freebie-search') !== null;

    if (isFreebie) {
        const freebieSearch = row.querySelector('.freebie-search');
        if (freebieSearch) {
            freebieSearch.value = `${sku} - ${description}`;
            const freebieSkuHidden = row.querySelector('.freebie-sku-hidden');
            if (freebieSkuHidden) freebieSkuHidden.value = sku;
            const freebieDescHidden = row.querySelector('.freebie-desc-hidden');
            if (freebieDescHidden) freebieDescHidden.value = description;
            const freebiePrice = row.querySelector('.freebie-price-per-pc');
            if (freebiePrice) freebiePrice.value = pricePerPc;
            const freebieQty = row.querySelector('.freebie-qty-per-pc');
            if (freebieQty) freebieQty.value = casePack;
        }
    } else {
        const productSearch = row.querySelector('.product-search');
        if (productSearch) {
            productSearch.value = `${sku} - ${description}`;
            const skuHidden = row.querySelector('.sku-hidden');
            if (skuHidden) skuHidden.value = sku;
            const descHidden = row.querySelector('.desc-hidden');
            if (descHidden) descHidden.value = description;
            const price = row.querySelector('.price-per-pc');
            if (price) price.value = pricePerPc;
            const qty = row.querySelector('.qty-per-pc');
            if (qty) {
                qty.value = casePack;
                qty.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }

    const resultsList = selected.closest('.search-results');
    if (resultsList) {
        resultsList.innerHTML = '';
        resultsList.classList.add('hidden');
    }
}

function performSearch(input) {
    const query = input.value.trim();
    const resultsList = input.nextElementSibling;
    if (!resultsList?.classList.contains('search-results')) return;

    const storeCodeInput = document.getElementById('requesting_store') || 
                          document.querySelector('input[name="requesting_store"]');
    const storeCode = storeCodeInput?.value || '';

    resultsList.classList.remove('hidden');
    resultsList.innerHTML = '<li class="px-4 py-2 text-gray-500">Searching...</li>';

    fetch(window.routeUrls.searchProducts, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ query, store_code: storeCode })
    })
    .then(res => res.json())
    .then(data => {
        resultsList.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) {
            resultsList.innerHTML = '<li class="px-4 py-2 text-gray-500 text-center">No products found</li>';
        } else {
            data.forEach(product => {
                const li = document.createElement('li');
                li.className = 'product-item px-4 py-2 hover:bg-gray-100 cursor-pointer';
                li.dataset.sku = product.sku;
                li.dataset.description = product.description;
                li.dataset.srp = product.srp;
                li.dataset.case_pack = product.case_pack;
                li.innerHTML = `
                    <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                    ${product.description}
                `;
                resultsList.appendChild(li);
            });
        }
    })
    .catch(err => {
        console.error('Search error:', err);
        resultsList.innerHTML = '<li class="px-4 py-2 text-red-500 text-center">Search failed</li>';
    });
}