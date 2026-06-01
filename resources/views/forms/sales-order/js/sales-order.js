import { initProductSearch } from './product-search.js';
import { RowManager } from './row-manager.js';
import { Calculator } from './calculator.js';
import { UIHelpers } from './ui-helpers.js';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all modules
    UIHelpers.init();
    initProductSearch();

    const rowManager = new RowManager();
    const calculator = new Calculator();

    // MBC card auto-fill using dynamic route
    const mbcInput = document.getElementById('mbc_card_no');
    if (mbcInput) {
        mbcInput.addEventListener('input', async (e) => {
            const cardNo = e.target.value.trim();
            if (cardNo.length === 16) {
                try {
                    const response = await fetch(window.routeUrls.getCardInfo, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken
                        },
                        body: JSON.stringify({ card_no: cardNo })
                    });
                    const data = await response.json();
                    if (response.ok && data.status === "200") {
                        document.getElementById('customer_name').value = data.data.name_on_card ?? '';
                        document.getElementById('contact_number').value = data.data.mobile_1 ?? '';
                        document.getElementById('email').value = data.data.email_1 ?? '';
                        UIHelpers.showToast('Customer information filled!', 'success');
                    } else {
                        UIHelpers.showToast(data.message || 'Card not found', 'error');
                        document.getElementById('customer_name').value = '';
                        document.getElementById('contact_number').value = '';
                        document.getElementById('email').value = '';
                    }
                } catch (err) {
                    UIHelpers.showToast(err.message, 'error');
                }
            }
        });
    }

    // Form submission loader (spinner SVG is now complete)
    const form = document.getElementById('order-form');
    const submitBtn = document.getElementById('submitBtn');
    if (form && submitBtn) {
        form.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Processing...
            `;
        });
    }
});