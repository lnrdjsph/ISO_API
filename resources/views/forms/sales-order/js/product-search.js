let debounceTimeout;

export function initProductSearch() {
    $(document).on('keyup focus', '.product-search, .freebie-search', function () {
        clearTimeout(debounceTimeout);
        const $input = $(this);
        if ($input.val().trim().length >= 2) {
            debounceTimeout = setTimeout(() => performSearch($input), 300);
        } else {
            $input.siblings('.search-results').empty().addClass('hidden');
        }
    });

    $(document).on('click', '.product-item', function () {
        const selected = $(this);
        const row = selected.closest('.order-row');
        const sku = selected.data('sku');
        const description = selected.data('description');
        const pricePerPc = selected.data('srp') || '';
        const casePack = selected.data('case_pack') || '';
        const isFreebie = selected.closest('.freebie-search').length > 0;

        if (isFreebie) {
            row.find('.freebie-search').val(`${sku} - ${description}`);
            row.find('.freebie-sku-hidden').val(sku);
            row.find('.freebie-desc-hidden').val(description);
            row.find('.freebie-price-per-pc').val(pricePerPc);
            row.find('.freebie-qty-per-pc').val(casePack);
        } else {
            row.find('.product-search').val(`${sku} - ${description}`);
            row.find('.sku-hidden').val(sku);
            row.find('.desc-hidden').val(description);
            row.find('.price-per-pc').val(pricePerPc);
            row.find('.qty-per-pc').val(casePack);
            row.find('.qty-per-pc').trigger('input');
        }
        selected.closest('.search-results').empty().addClass('hidden');
    });
}

function performSearch($input) {
    const query = $input.val().trim();
    const resultList = $input.siblings('.search-results');
    const storeCode = $('#requesting_store').val() || $('input[name="requesting_store"]').val();

    resultList.removeClass('hidden').html('<li class="px-4 py-2 text-gray-500">Searching...</li>');

    $.ajax({
        url: window.routeUrls.searchProducts,  // ✅ dynamic route
        data: { query, store_code: storeCode },
        success: (data) => {
            resultList.empty();
            if (data.length === 0) {
                resultList.append('<li class="px-4 py-2 text-gray-500 text-center">No products found</li>');
            } else {
                data.forEach(product => {
                    resultList.append(`
                        <li class="product-item px-4 py-2 hover:bg-gray-100 cursor-pointer"
                            data-sku="${product.sku}"
                            data-description="${product.description}"
                            data-srp="${product.srp}"
                            data-case_pack="${product.case_pack}">
                            <span class="font-mono text-xs bg-gray-200 px-2 py-1 rounded mr-2">${product.sku}</span>
                            ${product.description}
                        </li>
                    `);
                });
            }
        },
        error: () => resultList.html('<li class="px-4 py-2 text-red-500 text-center">Search failed</li>')
    });
}