export const UIHelpers = {
    init() {
        this.initDispatchToggle();
        this.initRequiredChecker();
        this.initInputHighlight();
    },

    initDispatchToggle() {
        const modeSelect = document.getElementById('mode_dispatching');
        const deliveryGroup = document.querySelector('.delivery-group');
        if (!modeSelect || !deliveryGroup) return;

        const toggle = () => {
            const show = modeSelect.value === 'Delivery Direct to Customer';
            deliveryGroup.classList.toggle('hidden', !show);
            if (show) deliveryGroup.classList.add('opacity-100', 'max-h-screen');
        };
        modeSelect.addEventListener('change', toggle);
        toggle();
    },
    initRequiredChecker() {
        const requiredInputs = document.querySelectorAll('.required-input');
        const orderItemsSection = document.querySelector('.order-item-form');
        if (!orderItemsSection) return;

        const check = () => {
            const allFilled = Array.from(requiredInputs).every(input => input.value.trim() !== '');
            orderItemsSection.classList.toggle('hidden', !allFilled);
        };

        requiredInputs.forEach(input => {
            input.addEventListener('change', check);
            input.addEventListener('input', check);  // add this
        });
        check();
    },

    initInputHighlight() {
        const highlight = (el) => {
            if (el.value.trim()) el.classList.add('bg-indigo-50');
            else el.classList.remove('bg-indigo-50');
        };
        document.querySelectorAll('input, select, textarea').forEach(el => {
            highlight(el);
            el.addEventListener('input', () => highlight(el));
        });
    },

    showToast(message, type = 'info') {
        // Simple alert fallback (replace with a proper toast library)
        alert(message);
    }
};