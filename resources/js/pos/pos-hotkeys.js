'use strict';

/**
 * ============================================================
 * POS Hotkeys Module (Phase 18)
 * ============================================================
 * Keyboard shortcuts: Ctrl+Q (quick entry), F2 (qty), F4 (search),
 * F5 (refresh), F6 (cash), F7 (amount), F8 (cancel), F9 (customer select2)
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    var currentRowIndex = 0;

    function focusQuantityInput() {
        var quantityInputs = document.querySelectorAll('.quantity-input');
        if (quantityInputs.length > 0) {
            quantityInputs[currentRowIndex].focus();
            quantityInputs[currentRowIndex].select();
            currentRowIndex = (currentRowIndex + 1) % quantityInputs.length;
        }
    }

    document.addEventListener('keydown', function (event) {
        // Ctrl+Q: open quick entry bar and focus Price field
        if (event.ctrlKey && (event.key === 'q' || event.key === 'Q')) {
            event.preventDefault();
            var bar = document.getElementById('cashEntryBar');
            var toggle = document.getElementById('cashEntryToggle');
            var priceInput = document.getElementById('cashPriceInput');
            if (bar && priceInput) {
                bar.classList.add('show');
                if (toggle) {
                    toggle.classList.remove('btn-outline-secondary');
                    toggle.classList.add('btn-warning');
                }
                priceInput.focus();
                priceInput.select();
            }
            return;
        }
        if (event.ctrlKey || event.altKey || event.shiftKey) return;

        switch (event.key) {
            case 'F2':
                event.preventDefault();
                focusQuantityInput();
                break;

            case 'F4':
                event.preventDefault();
                var searchInput = document.getElementById('productSearchInput');
                if (searchInput) { searchInput.focus(); searchInput.select(); }
                break;

            case 'F5':
                event.preventDefault();
                if (confirm('Are you sure you want to refresh the page?')) location.reload();
                break;

            case 'F6':
                event.preventDefault();
                var cashBtn = document.querySelector('#cashButton');
                if (cashBtn) cashBtn.click();
                break;

            case 'F7':
                event.preventDefault();
                var amountInput = document.querySelector('#amount-given');
                if (amountInput) { amountInput.focus(); amountInput.select(); }
                break;

            case 'F8':
                event.preventDefault();
                var cancelBtn = document.querySelector('#cancelButton');
                if (cancelBtn) cancelBtn.click();
                break;

            case 'F9':
                event.preventDefault();
                var customerSelect = $('#customer-id');
                if (customerSelect.length) {
                    customerSelect.select2('open');
                    setTimeout(function () { $('.select2-search__field').focus(); }, 100);
                }
                break;
        }
    });

    focusQuantityInput();
});
