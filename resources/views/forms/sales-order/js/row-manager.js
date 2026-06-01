export class RowManager {
    constructor() {
        this.rowIndex = document.querySelectorAll('.order-row').length;
        this.init();
    }

    init() {
        document.getElementById('add-row-btn')?.addEventListener('click', () => this.addRow());
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row')) this.removeRow(e.target.closest('.order-row'));
        });
        this.attachCollapseListeners();
        this.updateCounter();
    }

    addRow() {
        const container = document.getElementById('order-items');
        const template = container.children[0].cloneNode(true);
        const newIndex = this.rowIndex++;

        // Reset inputs
        template.querySelectorAll('input, select').forEach(el => {
            if (el.type !== 'hidden') el.value = '';
            el.name = el.name.replace(/\[\d+\]/g, `[${newIndex}]`);
        });

        // Reset displays
        template.querySelector('.price-display').textContent = '0.00';
        template.querySelector('.amount-display').textContent = '0.00';
        template.querySelector('.total-qty-display').textContent = '0';
        template.querySelector('.freebies-cs-display').textContent = '0';

        // Update row number display
        template.querySelector('.item-number').textContent = newIndex + 1;
        template.querySelector('h3').textContent = `Item No. ${newIndex + 1}`;

        container.appendChild(template);
        this.attachCollapseListeners();
        this.updateCounter();

        // Animate in
        setTimeout(() => template.classList.add('opacity-100'), 10);
    }

    removeRow(row) {
        if (document.querySelectorAll('.order-row').length === 1) return;
        row.remove();
        this.updateRowNumbers();
        this.updateCounter();
    }

    updateRowNumbers() {
        document.querySelectorAll('.order-row').forEach((row, idx) => {
            row.querySelector('.item-number').textContent = idx + 1;
            row.querySelector('h3').textContent = `Item No. ${idx + 1}`;
        });
    }

    updateCounter() {
        const count = document.querySelectorAll('.order-row').length;
        document.getElementById('product-counter').textContent = count;
    }

    attachCollapseListeners() {
        document.querySelectorAll('[data-toggle-row]').forEach(header => {
            header.removeEventListener('click', this.toggleRow);
            header.addEventListener('click', (e) => this.toggleRow(e));
        });
    }

    toggleRow(e) {
        if (e.target.closest('.toggle-collapse')) return;
        const row = e.currentTarget.closest('.order-row');
        const editable = row.querySelector('.editable-side');
        const readonly = row.querySelector('.readonly-side');
        const icon = row.querySelector('.collapse-icon');

        editable.classList.toggle('hidden');
        readonly.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }
}