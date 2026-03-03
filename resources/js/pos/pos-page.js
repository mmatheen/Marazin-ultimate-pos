'use strict';

/**
 * ============================================================
 * POS Page Module (Phase 19)
 * ============================================================
 * Miscellaneous page-level UI wiring that used to live inline
 * in pos.blade.php:
 *   - Calculator widget
 *   - Invoice lookup (sale-return flow)
 *   - Location select sync (mobile ↔ desktop)
 *   - Date/Time clock display
 *   - Bootstrap popover init
 *   - Go Back / Go Home navigation
 *   - Discount type toggle (Fixed / Percentage) + product list toggle
 *   - Mobile payment & action button routing
 *   - Mobile totals periodic sync
 * ============================================================
 */

/* ================================================================
   1. CALCULATOR
   ================================================================ */
window.calcInput = function (value) {
    document.getElementById('calcDisplay').value += value;
};

window.clearCalc = function () {
    document.getElementById('calcDisplay').value = '';
};

window.calculateResult = function () {
    try {
        document.getElementById('calcDisplay').value = eval(document.getElementById('calcDisplay').value);
    } catch (e) {
        document.getElementById('calcDisplay').value = 'Error';
    }
};

(function () {
    var calcDropdown = document.getElementById('calculatorDropdown');
    if (calcDropdown) {
        calcDropdown.addEventListener('click', function (event) { event.stopPropagation(); });
    }
})();

window.handleKeyboardInput = function (event) {
    var allowedKeys = '0123456789+-*/.';
    if (!allowedKeys.includes(event.key) && event.key !== 'Backspace' && event.key !== 'Enter') {
        event.preventDefault();
    }
    if (event.key === 'Enter') window.calculateResult();
};

/* ================================================================
   2. GO BACK / GO HOME (dashboardUrl injected by config block)
   ================================================================ */
window.handleGoBack = function () {
    if (window.history.length > 1 && document.referrer) {
        window.history.back();
    } else {
        window.location.href = window.dashboardUrl || '/';
    }
};

window.handleGoHome = function () {
    window.location.href = window.dashboardUrl || '/';
};

/* ================================================================
   3. DOMContentLoaded — one-time wiring
   ================================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ── Invoice Lookup (sale-return flow) ────────────────── */
    function handleInvoiceSubmission() {
        var invoiceInput = document.getElementById('invoiceNo');
        if (!invoiceInput) return;

        var invoiceNo = invoiceInput.value.trim();
        if (!invoiceNo) { toastr.warning('Please enter an invoice number'); return; }

        $.ajax({
            url: '/sales',
            method: 'GET',
            success: function (data) {
                if (!data.sales || !Array.isArray(data.sales)) {
                    toastr.error('Invalid sales data received.');
                    return;
                }
                var sale = data.sales.find(function (s) {
                    return s.invoice_no.toLowerCase() === invoiceNo.toLowerCase();
                });
                if (sale) {
                    window.location.href = '/sale-return/add?invoiceNo=' + encodeURIComponent(invoiceNo);
                } else {
                    toastr.error('Sale not found. Please enter a valid invoice number.');
                }
            },
            error: function () {
                toastr.error('An error occurred while fetching sales data.');
            }
        });
    }

    window.submitInvoiceNo = function () {
        var mobileInvoiceInput = document.getElementById('invoiceNoMobile');
        if (mobileInvoiceInput && mobileInvoiceInput.value.trim() !== '') {
            document.getElementById('invoiceNo').value = mobileInvoiceInput.value;
            handleInvoiceSubmission();
            var modal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
            if (modal) modal.hide();
            var collapse = bootstrap.Collapse.getInstance(document.getElementById('invoiceCollapse'));
            if (collapse) collapse.hide();
        }
    };

    var submitButton = document.getElementById('invoiceSubmitBtn');
    if (submitButton) {
        submitButton.addEventListener('click', function (e) { e.preventDefault(); handleInvoiceSubmission(); });
    }

    var invoiceInput = document.getElementById('invoiceNo');
    if (invoiceInput) {
        invoiceInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); handleInvoiceSubmission(); }
        });
    }

    var mobileInvoiceInput = document.getElementById('invoiceNoMobile');
    if (mobileInvoiceInput) {
        mobileInvoiceInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); window.submitInvoiceNo(); }
        });
    }

    /* ── Location Select Sync (mobile ↔ desktop) ─────────── */
    var mobileSelect  = document.getElementById('locationSelect');
    var desktopSelect = document.getElementById('locationSelectDesktop');

    if (mobileSelect && desktopSelect) {
        mobileSelect.addEventListener('change', function () {
            desktopSelect.value = this.value;
            $(desktopSelect).trigger('change');
        });
        desktopSelect.addEventListener('change', function () {
            mobileSelect.value = this.value;
            $(mobileSelect).trigger('change');
        });
    }

    /* ── Date / Time Clock ────────────────────────────────── */
    function updateDateTime() {
        var now     = new Date();
        var dateStr = now.getFullYear() + '-' +
            ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
            ('0' + now.getDate()).slice(-2);
        var timeStr = ('0' + now.getHours()).slice(-2) + ':' +
            ('0' + now.getMinutes()).slice(-2) + ':' +
            ('0' + now.getSeconds()).slice(-2);
        var dateBtn  = document.getElementById('currentDateButton');
        var timeText = document.getElementById('currentTimeText');
        if (dateBtn)  dateBtn.innerText  = dateStr;
        if (timeText) timeText.innerText = timeStr;
    }
    setInterval(updateDateTime, 1000);
    updateDateTime();

    /* ── Bootstrap Popovers ──────────────────────────────── */
    [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]')).forEach(function (el) {
        new bootstrap.Popover(el);
    });

    /* ── Discount Type Toggle (Fixed / Percentage) ───────── */
    var fixedDiscountBtn      = document.getElementById('fixed-discount-btn');
    var percentageDiscountBtn = document.getElementById('percentage-discount-btn');
    var discountInput         = document.getElementById('global-discount');
    var discountIcon          = document.getElementById('discount-icon');
    var discountTypeInput     = document.getElementById('discount-type');
    var toggleProductListBtn  = document.getElementById('toggleProductList');
    var productListArea       = document.getElementById('productListArea');
    var mainContent           = document.getElementById('mainContent');

    if (fixedDiscountBtn) fixedDiscountBtn.classList.add('active');
    if (discountIcon)     discountIcon.textContent = 'Rs';

    if (fixedDiscountBtn) {
        fixedDiscountBtn.addEventListener('click', function () {
            fixedDiscountBtn.classList.add('active');
            percentageDiscountBtn.classList.remove('active');
            discountIcon.textContent   = 'Rs';
            discountTypeInput.value    = 'fixed';
            discountInput.value        = '0';
            discountInput.dispatchEvent(new Event('input',  { bubbles: true }));
            discountInput.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof updateTotals === 'function') updateTotals();
        });
    }

    if (percentageDiscountBtn) {
        percentageDiscountBtn.addEventListener('click', function () {
            percentageDiscountBtn.classList.add('active');
            fixedDiscountBtn.classList.remove('active');
            discountIcon.textContent   = '%';
            discountTypeInput.value    = 'percentage';
            discountInput.value        = '0';
            discountInput.dispatchEvent(new Event('input',  { bubbles: true }));
            discountInput.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof updateTotals === 'function') updateTotals();
        });
    }

    if (toggleProductListBtn) {
        toggleProductListBtn.addEventListener('click', function () {
            if (productListArea.classList.contains('show')) {
                productListArea.classList.remove('show');
                productListArea.classList.add('d-none');
                mainContent.classList.remove('col-md-7');
                mainContent.classList.add('col-md-12');
            } else {
                productListArea.classList.remove('d-none');
                productListArea.classList.add('show');
                mainContent.classList.remove('col-md-12');
                mainContent.classList.add('col-md-7');
            }
        });
    }

    /* ── Mobile Payment & Action Button Routing ──────────── */
    function updateMobileTotals() {
        var finalTotal = (document.getElementById('final-total-amount') || {}).textContent || '0.00';
        var itemsCount = (document.getElementById('total-items-count') || {}).textContent || '0';

        var mobileFinalTotalEl = document.getElementById('mobile-final-total');
        if (mobileFinalTotalEl) mobileFinalTotalEl.textContent = 'Rs ' + finalTotal;

        var modalFinalTotalEl = document.getElementById('modal-final-total');
        if (modalFinalTotalEl) modalFinalTotalEl.textContent = 'Rs ' + finalTotal;

        var mobileItemsCountEl = document.getElementById('mobile-items-count');
        if (mobileItemsCountEl) mobileItemsCountEl.textContent = itemsCount;
    }
    setInterval(updateMobileTotals, 500);

    document.querySelectorAll('.mobile-payment-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var paymentType = this.getAttribute('data-payment');
            switch (paymentType) {
                case 'cash':   document.getElementById('cashButton')       && document.getElementById('cashButton').click();       break;
                case 'card':   document.getElementById('cardButton')       && document.getElementById('cardButton').click();       break;
                case 'cheque': document.getElementById('chequeButton')     && document.getElementById('chequeButton').click();     break;
                case 'credit': document.getElementById('creditSaleButton') && document.getElementById('creditSaleButton').click(); break;
            }
        });
    });

    document.querySelectorAll('.mobile-action-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var actionType = this.getAttribute('data-action');
            switch (actionType) {
                case 'draft':      document.getElementById('draftButton')     && document.getElementById('draftButton').click();     break;
                case 'sale-order': document.getElementById('saleOrderButton') && document.getElementById('saleOrderButton').click(); break;
                case 'quotation':  document.getElementById('quotationButton') && document.getElementById('quotationButton').click(); break;
                case 'job-ticket': document.getElementById('jobTicketButton') && document.getElementById('jobTicketButton').click(); break;
            }
        });
    });

    var mobileCancelButton = document.getElementById('mobile-cancel-button');
    if (mobileCancelButton) {
        mobileCancelButton.addEventListener('click', function () {
            var cancelBtn = document.getElementById('cancelButton');
            if (cancelBtn) cancelBtn.click();
        });
    }

}); // end DOMContentLoaded
