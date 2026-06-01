export class Calculator {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('input', (e) => {
            const row = e.target.closest('.order-row');
            if (row) this.calculateRow(row);
        });
    }

    calculateRow(row) {
        const saleType = row.querySelector('.sale-type')?.value || '';
        const discountVal = row.querySelector('.discount')?.value || '';
        const qtyCs = parseFloat(row.querySelector('.qty-cs')?.value) || 0;
        const qtyPcs = parseFloat(row.querySelector('.qty-per-pc')?.value) || 0;
        const pricePerPc = parseFloat(row.querySelector('.price-per-pc')?.value) || 0;
        const scheme = row.querySelector('.scheme-input')?.value || '1+0';
        const freebiePricePc = parseFloat(row.querySelector('.freebie-price-per-pc')?.value) || 0;
        const freebieQtyPc = parseFloat(row.querySelector('.freebie-qty-per-pc')?.value) || 0;

        // Parse scheme
        let [base, free] = scheme.replace(/[^0-9+]/g, '').split('+').map(n => parseInt(n) || 0);
        if (base === 0) base = 1;
        const fullSets = Math.floor(qtyCs / base);
        const freebies = fullSets * free;

        let pricePerCase = pricePerPc * qtyPcs;
        let originalPrice = pricePerCase;
        let totalAmount = pricePerCase * qtyCs;
        let freebieAmount = 0;
        let totalCases = qtyCs;

        if (saleType === 'Freebie') {
            totalCases = qtyCs + freebies;
            freebieAmount = freebiePricePc * freebieQtyPc * freebies;
        } else if (saleType === 'Discount' && discountVal) {
            if (discountVal.includes('%')) {
                const percent = parseFloat(discountVal) / 100;
                pricePerCase = originalPrice * (1 - percent);
            } else {
                pricePerCase = Math.max(0, originalPrice - parseFloat(discountVal));
            }
            totalAmount = pricePerCase * qtyCs;
        }

        // Update displays
        row.querySelector('.price-display').textContent = pricePerCase.toFixed(2);
        row.querySelector('.amount-display').textContent = totalAmount.toFixed(2);
        row.querySelector('.total-qty-display').textContent = totalCases;
        row.querySelector('.freebies-cs-display').textContent = freebies;
        row.querySelector('.freebie-amount-display').textContent = freebieAmount.toFixed(2);

        // Update hidden fields
        row.querySelector('.computed-price').value = pricePerCase.toFixed(2);
        row.querySelector('.computed-amount').value = totalAmount.toFixed(2);
        row.querySelector('.computed-total-qty').value = totalCases;
        row.querySelector('.computed-freebies').value = freebies;
        row.querySelector('.computed-freebie-amount').value = freebieAmount.toFixed(2);

        // Toggle freebie amount visibility
        const freebieBlock = row.querySelector('.freebie-block');
        if (freebieBlock) freebieBlock.classList.toggle('hidden', freebieAmount === 0);
    }
}