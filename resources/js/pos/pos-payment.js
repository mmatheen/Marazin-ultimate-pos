'use strict';

/**
 * ============================================================
 * POS Payment Module (Phase 19)
 * ============================================================
 * Payment-modal logic previously inline in pos.blade.php:
 *   - Add / remove payment rows
 *   - Toggle conditional fields (card / cheque / bank transfer)
 *   - Amount validation
 *   - Payment summary calculation
 *   - Fetch totals helpers
 *   - Date/time picker init
 *   - Payment modal show handler (default cash row)
 *   - Format helpers (bridge to pos-utils if loaded)
 * ============================================================
 */

/* ── Amount Formatting (local fallbacks — pos-utils may load first) ── */
if (typeof window.formatAmountWithSeparators !== 'function') {
    window.formatAmountWithSeparators = function (amount) {
        return new Intl.NumberFormat().format(amount);
    };
}
if (typeof window.parseFormattedAmount !== 'function') {
    window.parseFormattedAmount = function (formattedAmount) {
        return parseFloat(String(formattedAmount).replace(/,/g, ''));
    };
}

/* Convenience alias used by inline oninput="formatAmount(this)" in markup */
window.formatAmount = function (input) {
    var value = input.value.replace(/,/g, '');
    if (value && !isNaN(value)) {
        input.value = window.formatAmountWithSeparators(parseFloat(value));
    }
};

/* ── State ──────────────────────────────────────────────────── */
var totalPayable = 0;

/* ── Toggle Conditional Fields ──────────────────────────────── */
window.togglePaymentFields = function (selectElement) {
    var paymentMethod     = selectElement.value;
    var conditionalFields = selectElement.closest('.payment-row').querySelector('.conditional-fields');
    conditionalFields.innerHTML = '';

    if (paymentMethod === 'card') {
        conditionalFields.innerHTML =
            '<div class="col-md-6"><label class="form-label">Card Number</label><input class="form-control" name="card_number" required></div>' +
            '<div class="col-md-6"><label class="form-label">Card Holder Name</label><input class="form-control" name="card_holder_name"></div>' +
            '<div class="col-md-6"><label class="form-label">Card Type</label><select class="form-select" name="card_type">' +
            '<option value="visa">Visa</option><option value="mastercard">MasterCard</option><option value="amex">American Express</option></select></div>' +
            '<div class="col-md-6"><label class="form-label">Expiry Month</label><input class="form-control" name="card_expiry_month"></div>' +
            '<div class="col-md-6"><label class="form-label">Expiry Year</label><input class="form-control" name="card_expiry_year"></div>' +
            '<div class="col-md-6"><label class="form-label">Security Code</label><input class="form-control" name="card_security_code"></div>';
    } else if (paymentMethod === 'cheque') {
        conditionalFields.innerHTML =
            '<div class="col-md-6"><label class="form-label">Cheque Number</label><input class="form-control" name="cheque_number" required></div>' +
            '<div class="col-md-6"><label class="form-label">Bank Branch</label><input class="form-control" name="cheque_bank_branch"></div>' +
            '<div class="col-md-6"><label class="form-label">Received Date</label><input class="form-control datetimepicker cheque-received-date" name="cheque_received_date"></div>' +
            '<div class="col-md-6"><label class="form-label">Valid Date</label><input class="form-control datetimepicker cheque-valid-date" name="cheque_valid_date"></div>' +
            '<div class="col-md-6"><label class="form-label">Given By</label><input class="form-control" name="cheque_given_by"></div>';
        initializeDateTimePickers();
    } else if (paymentMethod === 'bank_transfer') {
        conditionalFields.innerHTML =
            '<div class="col-md-12"><label class="form-label">Bank Account Number</label><input class="form-control" name="bank_account_number"></div>';
    }
};

/* ── Validate Amount ────────────────────────────────────────── */
window.validateAmount = function () {
    document.querySelectorAll('.payment-amount').forEach(function (input) {
        var amountError = input.nextElementSibling;
        var val = parseFloat(input.value);
        if (isNaN(val) || val <= 0) {
            amountError.style.display = 'block';
        } else {
            amountError.style.display = 'none';
        }
    });
    updatePaymentSummary();
};

/* ── Payment Summary ────────────────────────────────────────── */
function updatePaymentSummary() {
    var totalItems  = fetchTotalItems();
    var totalAmount = fetchTotalAmount();

    var discount     = parseFloat((document.getElementById('global-discount') || {}).value || 0);
    var discountType = (document.getElementById('discount-type') || {}).value || 'fixed';

    totalPayable = discountType === 'percentage'
        ? totalAmount - (totalAmount * discount / 100)
        : totalAmount - discount;

    var totalPaying = 0;
    document.querySelectorAll('.payment-amount').forEach(function (input) {
        totalPaying += parseFloat(input.value) || 0;
    });

    var changeReturn = Math.max(totalPaying - totalPayable, 0);
    var balance      = Math.max(totalPayable - totalPaying, 0);

    var fmt = window.formatAmountWithSeparators;
    document.getElementById('modal-total-items').textContent    = totalItems.toFixed(2);
    document.getElementById('modal-total-payable').textContent  = fmt(totalPayable.toFixed(2));
    document.getElementById('modal-total-paying').textContent   = fmt(totalPaying.toFixed(2));
    document.getElementById('modal-change-return').textContent  = fmt(changeReturn.toFixed(2));
    document.getElementById('modal-balance').textContent        = fmt(balance.toFixed(2));

    document.getElementById('addPaymentRow').disabled = (balance === 0 && totalPaying >= totalPayable);
}
window.updatePaymentSummary = updatePaymentSummary;

/* ── Fetch Totals ───────────────────────────────────────────── */
function fetchTotalAmount() {
    var total = 0;
    document.querySelectorAll('#billing-body tr .subtotal').forEach(function (cell) {
        total += parseFloat(cell.textContent.replace(/,/g, ''));
    });
    return total;
}
window.fetchTotalAmount = fetchTotalAmount;

function fetchTotalItems() {
    var count = 0;
    document.querySelectorAll('#billing-body tr .quantity-input').forEach(function (input) {
        count += parseInt(input.value) || 0;
    });
    return count;
}
window.fetchTotalItems = fetchTotalItems;

/* ── Date/Time Picker Initialization ────────────────────────── */
function initializeDateTimePickers() {
    $('.datetimepicker').datetimepicker({ format: 'DD-MM-YYYY', useCurrent: false, minDate: moment().startOf('day') });
    $('.cheque-received-date').datetimepicker({ format: 'DD-MM-YYYY' });
    $('.cheque-valid-date').datetimepicker({ format: 'DD-MM-YYYY', minDate: moment().add(1, 'days') });
}
window.initializeDateTimePickers = initializeDateTimePickers;

/* ── Add Payment Row Button ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    var addPaymentRowBtn = document.getElementById('addPaymentRow');
    if (addPaymentRowBtn) {
        addPaymentRowBtn.addEventListener('click', function () {
            var paymentRows  = document.getElementById('paymentRows');
            var usedMethods  = Array.from(document.querySelectorAll('.payment-method')).map(function (el) { return el.value; });
            var newPaymentRow = document.createElement('div');
            newPaymentRow.className = 'card mb-3 payment-row position-relative';
            newPaymentRow.innerHTML =
                '<div class="card-body">' +
                '  <div class="row">' +
                '    <div class="col-md-4">' +
                '      <label for="paymentMethod" class="form-label">Payment Method</label>' +
                '      <select class="form-select payment-method" name="payment_method" onchange="togglePaymentFields(this)">' +
                '        <option value="cash"' + (usedMethods.includes('cash') ? ' disabled' : '') + '>Cash</option>' +
                '        <option value="card"' + (usedMethods.includes('card') ? ' disabled' : '') + '>Credit Card</option>' +
                '        <option value="cheque"' + (usedMethods.includes('cheque') ? ' disabled' : '') + '>Cheque</option>' +
                '        <option value="bank_transfer"' + (usedMethods.includes('bank_transfer') ? ' disabled' : '') + '>Bank Transfer</option>' +
                '      </select>' +
                '    </div>' +
                '    <div class="col-md-4">' +
                '      <label for="paidOn" class="form-label">Paid On</label>' +
                '      <input class="form-control datetimepicker payment-date" type="text" name="payment_date" placeholder="DD-MM-YYYY" value="' + moment().format('DD-MM-YYYY') + '">' +
                '    </div>' +
                '    <div class="col-md-4">' +
                '      <label for="payAmount" class="form-label">Amount</label>' +
                '      <input type="text" class="form-control payment-amount" name="amount" oninput="validateAmount()">' +
                '      <div class="text-danger amount-error" style="display:none;">Enter valid amount</div>' +
                '    </div>' +
                '  </div>' +
                '  <div class="conditional-fields row mt-3"></div>' +
                '</div>' +
                '<button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-payment-row" aria-label="Close"></button>';

            paymentRows.appendChild(newPaymentRow);
            initializeDateTimePickers();

            newPaymentRow.querySelector('.remove-payment-row').addEventListener('click', function () {
                this.closest('.payment-row').remove();
                updatePaymentSummary();
            });

            togglePaymentFields(newPaymentRow.querySelector('.payment-method'));
            updatePaymentSummary();
        });
    }

    /* ── Payment Modal Show — default cash row ──────────────── */
    var paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('show.bs.modal', function () {
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentRows').innerHTML = '';

            var totalAmount   = fetchTotalAmount();
            var discount      = parseFloat((document.getElementById('global-discount') || {}).value || 0);
            var discountType  = (document.getElementById('discount-type') || {}).value || 'fixed';
            var defaultAmount = discountType === 'percentage'
                ? totalAmount - (totalAmount * discount / 100)
                : totalAmount - discount;

            var defaultRow = document.createElement('div');
            defaultRow.className = 'card mb-3 payment-row position-relative';
            defaultRow.innerHTML =
                '<div class="card-body">' +
                '  <div class="row">' +
                '    <div class="col-md-4">' +
                '      <label class="form-label">Payment Method</label>' +
                '      <select class="form-select payment-method" name="payment_method" disabled onchange="togglePaymentFields(this)">' +
                '        <option value="cash" selected>Cash</option>' +
                '      </select>' +
                '    </div>' +
                '    <div class="col-md-4">' +
                '      <label class="form-label">Paid On</label>' +
                '      <input class="form-control datetimepicker payment-date" type="text" name="payment_date" placeholder="DD-MM-YYYY" value="' + moment().format('DD-MM-YYYY') + '">' +
                '    </div>' +
                '    <div class="col-md-4">' +
                '      <label class="form-label">Amount</label>' +
                '      <input type="text" class="form-control payment-amount" name="amount" value="' + defaultAmount.toFixed(2) + '" oninput="validateAmount()">' +
                '      <div class="text-danger amount-error" style="display:none;"></div>' +
                '    </div>' +
                '  </div>' +
                '  <div class="conditional-fields row mt-3"></div>' +
                '</div>';

            document.getElementById('paymentRows').appendChild(defaultRow);
            initializeDateTimePickers();
            updatePaymentSummary();
        });
    }
}); // end DOMContentLoaded
