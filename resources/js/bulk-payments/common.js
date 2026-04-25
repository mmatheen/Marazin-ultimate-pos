// Bulk payments shared helpers.
// Exposes a small API on window for legacy Blade scripts.

function parseAmountValue(value) {
  if (value === null || value === undefined) return 0;
  const clean = String(value).replace(/,/g, '').replace(/[^\d.-]/g, '');
  const num = parseFloat(clean);
  return Number.isFinite(num) ? num : 0;
}

function formatAmountValue(value) {
  const num = Number(value || 0);
  return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatRs(value) {
  return 'Rs. ' + formatAmountValue(value);
}

/** Escape text for safe insertion into HTML (shared by sales & purchases bulk UIs). */
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Rebuild `window.billPaymentAllocations` from all `.bill-allocation-row` inputs.
 * Shared by sales and purchases bulk payment UIs.
 */
function recalcBillPaymentAllocationsFromUI() {
  if (!window.jQuery) return;
  const $ = window.jQuery;
  const newAllocations = {};
  $('.bill-allocation-row').each(function () {
    const billId = $(this).find('.bill-select').val();
    const amount = parseAmountValue($(this).find('.allocation-amount').val());
    if (billId && amount > 0) {
      newAllocations[billId] = (newAllocations[billId] || 0) + amount;
    }
  });
  window.billPaymentAllocations = newAllocations;
}

/**
 * Safely reset flexible bulk payment UI state (sales/purchases).
 * Keeps behavior conservative: clears tracking + UI containers if present, then refreshes totals.
 */
function resetFlexiblePaymentSystem() {
  try {
    if (!window.jQuery) return;
    const $ = window.jQuery;

    window.billPaymentAllocations = {};
    window.paymentMethodAllocations = {};

    // Return-credit allocations (if that module is present on page)
    if (window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};

    // Clear allocations UI if present
    const $paymentsList = $('#flexiblePaymentsList');
    if ($paymentsList.length) {
      if (window.FLEXIBLE_PAYMENTS_EMPTY_HTML) $paymentsList.html(window.FLEXIBLE_PAYMENTS_EMPTY_HTML);
      else $paymentsList.empty();
      if (typeof window.syncPaymentMethodsEmptyState === 'function') window.syncPaymentMethodsEmptyState();
    }

    const $simpleTable = $('#billsPaymentTableBody');
    if ($simpleTable.length) $simpleTable.empty();

    // Uncheck returns if present
    $('.return-checkbox').prop('checked', false);

    // Refresh lists/totals if APIs exist
    if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList('');
    if (typeof window.updateSummaryTotals === 'function') window.updateSummaryTotals();
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error('resetFlexiblePaymentSystem failed', e);
  }
}

/**
 * When user applies credit, auto-reduce existing cash allocations so the payment rows match
 * the reduced "cash to collect" (similar to return-credit behavior).
 *
 * This only REDUCES allocations (never increases them) to avoid surprising the user.
 */
function syncCreditDeductionToAllocations() {
  try {
    if (!window.jQuery) return;
    const $ = window.jQuery;
    if (!$('#applyAdvanceCreditCheckbox').is(':checked')) return;

    // Sales page stores `netCustomerDue`; purchases page stores `netSupplierDue`.
    // Use a safe fallback so credit-deduction works consistently on both pages.
    const desiredCash = Number(window.netCustomerDue ?? window.netSupplierDue);
    if (!Number.isFinite(desiredCash) || desiredCash < 0) return;

    let allocatedCash = 0;
    $('.bill-allocation-row .allocation-amount').each(function () {
      allocatedCash += parseAmountValue($(this).val());
    });

    let toReduce = allocatedCash - desiredCash;
    if (toReduce <= 0.01) return;

    const rows = $('.bill-allocation-row').get().reverse();
    rows.forEach((rowEl) => {
      if (toReduce <= 0.01) return;
      const $row = $(rowEl);
      const $amountInput = $row.find('.allocation-amount');
      const billId = $row.find('.bill-select').val();
      const cur = parseAmountValue($amountInput.val());
      if (!billId || cur <= 0) return;

      const reduceBy = Math.min(cur, toReduce);
      const nextVal = Math.max(0, cur - reduceBy);

      $amountInput.data('system-update', true);
      $amountInput.val(formatAmountValue(nextVal));
      $amountInput.data('prev-amount', nextVal);
      setTimeout(() => $amountInput.data('system-update', false), 0);

      if (typeof window.billPaymentAllocations === 'object' && window.billPaymentAllocations !== null) {
        const before = parseFloat(window.billPaymentAllocations[billId] || 0) || 0;
        const after = Math.max(0, before - reduceBy);
        if (after <= 0.01) {
          delete window.billPaymentAllocations[billId];
        } else {
          window.billPaymentAllocations[billId] = after;
        }
      }

      toReduce -= reduceBy;
    });

    // Refresh totals per payment method & UI (if functions exist)
    $('.payment-method-item').each(function () {
      const pid = $(this).data('payment-id');
      if (pid && typeof window.updatePaymentMethodTotal === 'function') {
        window.updatePaymentMethodTotal(pid);
      }
    });
    if (typeof window.populateFlexibleBillsList === 'function') {
      window.populateFlexibleBillsList($('#billSearchInput').val() || '');
    }
    if (typeof window.updateSummaryTotals === 'function') {
      window.updateSummaryTotals();
    }
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error('syncCreditDeductionToAllocations failed', e);
  }
}

// Expose for legacy scripts
window.parseAmountValue = parseAmountValue;
window.formatAmountValue = formatAmountValue;
window.formatRs = formatRs;
window.escapeHtml = escapeHtml;
window.recalcBillPaymentAllocationsFromUI = recalcBillPaymentAllocationsFromUI;
window.resetFlexiblePaymentSystem = resetFlexiblePaymentSystem;
window.syncCreditDeductionToAllocations = syncCreditDeductionToAllocations;

// Wire credit UI handlers once.
document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery) return;
  const $ = window.jQuery;

  const callUpdateNetDue = () => {
    if (typeof window.updateNetCustomerDue === 'function') return window.updateNetCustomerDue();
    if (typeof window.updateNetSupplierDue === 'function') return window.updateNetSupplierDue();
  };

  $(document).on('change', '#applyAdvanceCreditCheckbox', function () {
    if ($(this).is(':checked')) {
      $('#advanceCreditAmountSection').slideDown();
      const maxAdvance = window.customerAdvanceCredit || window.supplierAdvanceCredit || 0;
      const totalDue = window.totalCustomerDue || window.totalSupplierDue || maxAdvance;
      const suggestedAmount = Math.min(maxAdvance, totalDue);
      $('#advanceCreditAmountInput').val(suggestedAmount.toFixed(2));
      $('#advanceCreditAmountInput').attr('max', maxAdvance);
      callUpdateNetDue();
      if (typeof window.syncCreditDeductionToAllocations === 'function') syncCreditDeductionToAllocations();
    } else {
      $('#advanceCreditAmountSection').slideUp();
      $('#advanceCreditAmountInput').val('');
      callUpdateNetDue();
    }
  });

  $(document).on('input', '#advanceCreditAmountInput', function () {
    const maxAdvance = window.customerAdvanceCredit || window.supplierAdvanceCredit || 0;
    const inputAmount = parseFloat($(this).val()) || 0;
    if (inputAmount > maxAdvance) {
      $(this).val(maxAdvance.toFixed(2));
      if (window.toastr) {
        window.toastr.warning('Amount cannot exceed available credit of Rs. ' + maxAdvance.toFixed(2));
      }
    }
    callUpdateNetDue();
    if (typeof window.syncCreditDeductionToAllocations === 'function') syncCreditDeductionToAllocations();
  });
});

