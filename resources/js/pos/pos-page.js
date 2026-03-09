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
 *
 * Public API:
 *   window.Pos.Page.calcInput,
 *   window.Pos.Page.clearCalc,
 *   window.Pos.Page.calculateResult,
 *   window.Pos.Page.handleKeyboardInput,
 *   window.Pos.Page.handleGoBack,
 *   window.Pos.Page.handleGoHome
 * ============================================================
 */

// POS namespace for page-level helpers
window.Pos = window.Pos || {};
window.Pos.Page = window.Pos.Page || {};

/* ================================================================
   1. CALCULATOR (safe — no eval; only arithmetic)
   ================================================================ */
/**
 * Safe calculator: only digits, + - * / . and spaces. No eval, no code injection.
 * Supports: 2+3*4, 10/2, 1.5*2. Returns null if invalid.
 */
function safeCalculateResult(expr) {
    if (typeof expr !== 'string' || !expr.trim()) return null;
    var s = expr.replace(/\s/g, '');
    if (!/^[\d+\-*/.]+$/.test(s)) return null;
    var tokens = s.match(/\d+\.?\d*|[\+\-\*\/]/g);
    if (!tokens || tokens.length < 2) {
        var single = parseFloat(s);
        return isFinite(single) ? single : null;
    }
    try {
        var i = 0;
        function num() {
            if (i >= tokens.length) return NaN;
            var t = tokens[i++];
            if (/^[\d.]+$/.test(t)) return parseFloat(t);
            i--;
            return NaN;
        }
        function term() {
            var left = num();
            while (i < tokens.length && (tokens[i] === '*' || tokens[i] === '/')) {
                var op = tokens[i++];
                var right = num();
                left = op === '*' ? left * right : left / right;
            }
            return left;
        }
        var result = term();
        while (i < tokens.length && (tokens[i] === '+' || tokens[i] === '-')) {
            var op = tokens[i++];
            result = op === '+' ? result + term() : result - term();
        }
        return i === tokens.length && isFinite(result) ? result : null;
    } catch (e) {
        return null;
    }
}

window.Pos.Page.calcInput = function (value) {
    document.getElementById('calcDisplay').value += value;
};

window.Pos.Page.clearCalc = function () {
    document.getElementById('calcDisplay').value = '';
};

window.Pos.Page.calculateResult = function () {
    var el = document.getElementById('calcDisplay');
    if (!el) return;
    var result = safeCalculateResult(el.value);
    if (result === null) {
        el.value = 'Error';
    } else {
        el.value = Number.isInteger(result) ? result : parseFloat(result.toFixed(10));
    }
};

(function () {
    var calcDropdown = document.getElementById('calculatorDropdown');
    if (calcDropdown) {
        calcDropdown.addEventListener('click', function (event) { event.stopPropagation(); });
    }
})();

window.Pos.Page.handleKeyboardInput = function (event) {
    var allowedKeys = '0123456789+-*/.';
    if (!allowedKeys.includes(event.key) && event.key !== 'Backspace' && event.key !== 'Enter') {
        event.preventDefault();
    }
    if (event.key === 'Enter') {
        event.preventDefault();
        window.Pos.Page.calculateResult();
    }
};

/* Global aliases for inline HTML (onkeydown/onclick in pos.blade.php) */
window.handleKeyboardInput = window.Pos.Page.handleKeyboardInput;
window.calculateResult   = window.Pos.Page.calculateResult;
window.calcInput         = window.Pos.Page.calcInput;
window.clearCalc         = window.Pos.Page.clearCalc;

/* ================================================================
   2. GO BACK / GO HOME (dashboardUrl injected by config block)
   ================================================================ */
window.Pos.Page.handleGoBack = function () {
    if (window.history.length > 1 && document.referrer) {
        window.history.back();
    } else {
        window.location.href = window.dashboardUrl || '/';
    }
};

window.Pos.Page.handleGoHome = function () {
    window.location.href = window.dashboardUrl || '/';
};

// Global shims so inline onclick="handleGoBack()" / "handleGoHome()" continue to work
// while main implementation lives on window.Pos.Page.*
window.handleGoBack = function () {
    if (window.Pos && window.Pos.Page && typeof window.Pos.Page.handleGoBack === 'function') {
        window.Pos.Page.handleGoBack();
    }
};
window.handleGoHome = function () {
    if (window.Pos && window.Pos.Page && typeof window.Pos.Page.handleGoHome === 'function') {
        window.Pos.Page.handleGoHome();
    }
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
        var dateBtn   = document.getElementById('currentDateButton');
        var timeText  = document.getElementById('currentTimeText');
        var timeMobile = document.getElementById('currentTimeTextMobile');
        if (dateBtn)    dateBtn.innerText   = dateStr;
        if (timeText)   timeText.innerText  = timeStr;
        if (timeMobile) timeMobile.innerText = timeStr;
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
            if (window.Pos && window.Pos.Cart && typeof window.Pos.Cart.updateTotals === 'function') {
                window.Pos.Cart.updateTotals();
            }
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
            if (window.Pos && window.Pos.Cart && typeof window.Pos.Cart.updateTotals === 'function') {
                window.Pos.Cart.updateTotals();
            }
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

    /* ── Mobile Payment & Action Button Routing ────────────
     * Footer total is updated by writeTotalsToDom (single source). This only syncs
     * modal and item count as backup when DOM might not have been updated yet. */
    function updateMobileTotals() {
        var finalTotal = (document.getElementById('final-total-amount') || {}).textContent || '0.00';
        var itemsCount = (document.getElementById('total-items-count') || {}).textContent || '0';

        var modalFinalTotalEl = document.getElementById('modal-final-total');
        if (modalFinalTotalEl) modalFinalTotalEl.textContent = 'Rs ' + finalTotal;

        var mobileItemsCountEl = document.getElementById('mobile-items-count');
        if (mobileItemsCountEl) mobileItemsCountEl.textContent = itemsCount;
        var mobileSummaryItemsEl = document.getElementById('mobile-summary-items-count');
        if (mobileSummaryItemsEl) mobileSummaryItemsEl.textContent = itemsCount;

        var mobileFooterTotalEl = document.getElementById('mobile-final-total');
        if (mobileFooterTotalEl && !mobileFooterTotalEl.textContent) mobileFooterTotalEl.textContent = 'Rs. ' + finalTotal;
    }
    setInterval(updateMobileTotals, 800);

    /* ── Mobile Order Summary: sync discount type and global discount with main inputs ───────── */
    var mobileFixedBtn = document.getElementById('mobile-fixed-discount-btn');
    var mobilePctBtn = document.getElementById('mobile-percentage-discount-btn');
    var mobileDiscountInput = document.getElementById('mobile-global-discount');
    if (mobileFixedBtn && mobilePctBtn && discountTypeInput) {
        function syncMainDiscountFromMobile() {
            discountInput.value = (mobileDiscountInput && mobileDiscountInput.value) ? mobileDiscountInput.value : (discountInput && discountInput.value) || '0';
            if (discountInput) {
                discountInput.dispatchEvent(new Event('input', { bubbles: true }));
                discountInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (window.Pos && window.Pos.Cart && typeof window.Pos.Cart.updateTotals === 'function') {
                window.Pos.Cart.updateTotals();
            }
        }
        function setMobileDiscountType(isFixed) {
            if (isFixed) {
                mobileFixedBtn.classList.add('active');
                mobilePctBtn.classList.remove('active');
                discountTypeInput.value = 'fixed';
                if (fixedDiscountBtn) { fixedDiscountBtn.classList.add('active'); if (percentageDiscountBtn) percentageDiscountBtn.classList.remove('active'); }
                if (discountIcon) discountIcon.textContent = 'Rs';
            } else {
                mobilePctBtn.classList.add('active');
                mobileFixedBtn.classList.remove('active');
                discountTypeInput.value = 'percentage';
                if (percentageDiscountBtn) { percentageDiscountBtn.classList.add('active'); if (fixedDiscountBtn) fixedDiscountBtn.classList.remove('active'); }
                if (discountIcon) discountIcon.textContent = '%';
            }
            syncMainDiscountFromMobile();
        }
        mobileFixedBtn.addEventListener('click', function () { setMobileDiscountType(true); });
        mobilePctBtn.addEventListener('click', function () { setMobileDiscountType(false); });
        if (mobileDiscountInput) {
            mobileDiscountInput.addEventListener('input', function () {
                if (discountInput) discountInput.value = mobileDiscountInput.value;
                syncMainDiscountFromMobile();
            });
            mobileDiscountInput.addEventListener('change', syncMainDiscountFromMobile);
        }
        /* Sync from main to mobile on load and keep in sync when main changes */
        function syncMobileDiscountFromMain() {
            var dt = (discountTypeInput && discountTypeInput.value) || 'fixed';
            if (dt === 'percentage') {
                mobilePctBtn.classList.add('active');
                mobileFixedBtn.classList.remove('active');
            } else {
                mobileFixedBtn.classList.add('active');
                mobilePctBtn.classList.remove('active');
            }
            if (discountInput && mobileDiscountInput) mobileDiscountInput.value = discountInput.value || '0';
        }
        syncMobileDiscountFromMain();
        if (discountInput) {
            discountInput.addEventListener('input', syncMobileDiscountFromMain);
            discountInput.addEventListener('change', syncMobileDiscountFromMain);
        }
    }

    /* Sync sale notes: desktop and mobile textareas stay in sync */
    var saleNotesDesktop = document.getElementById('sale-notes-textarea');
    var saleNotesMobile = document.getElementById('sale-notes-textarea-mobile');
    if (saleNotesDesktop && saleNotesMobile) {
        function syncNotesToMobile() { saleNotesMobile.value = saleNotesDesktop.value; }
        function syncNotesToDesktop() { saleNotesDesktop.value = saleNotesMobile.value; }
        saleNotesDesktop.addEventListener('input', syncNotesToMobile);
        saleNotesMobile.addEventListener('input', syncNotesToDesktop);
    }

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

    var mobileClearCartBtn = document.getElementById('mobileClearCartBtn');
    if (mobileClearCartBtn) {
        mobileClearCartBtn.addEventListener('click', function () {
            var cancelBtn = document.getElementById('cancelButton');
            if (cancelBtn) cancelBtn.click();
        });
    }

}); // end DOMContentLoaded
