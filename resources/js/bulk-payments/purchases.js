// Purchases bulk payments — Phase 7: multi-method submit (mirrors sales refactor pattern).
// Depends on Blade globals: `selectedPurchaseReturns`, `availableSupplierPurchases` (use `var` in Blade so they exist on `window` for this module).

document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery) return;

  function submitMultiMethodPayment() {
    const $ = window.jQuery;
    const customerId = $('#customerSelect').val();
    const paymentDate = $('#paidOn').val() || $('[name="payment_date"]').val();
    const paymentType = $('input[name="paymentType"]:checked').val();

    if (!customerId) {
      if (window.toastr) window.toastr.error('Please select a customer');
      return false;
    }

    if (!paymentDate) {
      if (window.toastr) window.toastr.error('Please select payment date');
      return false;
    }

    const selectedPurchaseReturns = window.selectedPurchaseReturns || [];
    const availableSupplierPurchases = window.availableSupplierPurchases || [];

    const paymentGroups = [];
    let hasValidPayments = false;
    let validationFailed = false;
    let groupIndex = 0;

    let billReturnAllocations = {};
    const hasApplyToSalesReturns = selectedPurchaseReturns.some((r) => r.action === 'apply_to_purchases');
    if (hasApplyToSalesReturns && window.billReturnCreditAllocations) {
      billReturnAllocations = window.billReturnCreditAllocations;
    }

    $('.payment-method-item').each(function () {
      const $payment = $(this);
      const paymentId = $payment.data('payment-id');
      const method = $payment.find('.payment-method-select').val();
      const totalAmount = parseFloat($payment.find('.payment-total-amount').val()) || 0;

      if (!method || totalAmount <= 0) return;

      groupIndex++;

      const groupData = {
        method,
        totalAmount,
        bills: [],
        details: {},
      };

      switch (method) {
        case 'cheque': {
          const chequeNumber = $payment.find(`[name="cheque_number_${paymentId}"]`).val() || '';
          const chequeBank = $payment.find(`[name="cheque_bank_${paymentId}"]`).val() || '';
          const chequeDate = $payment.find(`[name="cheque_date_${paymentId}"]`).val() || '';
          const chequeGivenBy = $payment.find(`[name="cheque_given_by_${paymentId}"]`).val() || '';

          if (!chequeNumber || chequeNumber.trim() === '') {
            if (window.toastr) window.toastr.error(`Payment ${groupIndex}: Cheque Number is required`);
            $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
            validationFailed = true;
            return false;
          }
          if (!chequeBank || chequeBank.trim() === '') {
            if (window.toastr) window.toastr.error(`Payment ${groupIndex}: Bank & Branch is required for cheque payments`);
            $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
            validationFailed = true;
            return false;
          }
          if (!chequeDate || chequeDate.trim() === '') {
            if (window.toastr) window.toastr.error(`Payment ${groupIndex}: Cheque Date is required`);
            $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
            validationFailed = true;
            return false;
          }

          groupData.cheque_number = chequeNumber;
          groupData.cheque_bank_branch = chequeBank;
          groupData.cheque_valid_date = chequeDate;
          groupData.cheque_given_by = chequeGivenBy;
          break;
        }
        case 'card':
          groupData.card_number = $payment.find(`[name="card_number_${paymentId}"]`).val();
          groupData.card_holder = $payment.find(`[name="card_holder_${paymentId}"]`).val();
          break;
        case 'bank_transfer':
          groupData.bank_account_number = $payment.find(`[name="account_number_${paymentId}"]`).val();
          break;
      }

      $payment.find('.bill-allocation-row').each(function () {
        const $allocation = $(this);
        const billId = $allocation.find('.bill-select').val();
        const amount = parseFloat($allocation.find('.allocation-amount').val()) || 0;

        if (billId && amount > 0) {
          groupData.bills.push({
            purchase_id: parseInt(billId, 10),
            amount,
          });
        }
      });

      let totalBillsAllocated = 0;
      groupData.bills.forEach((bill) => {
        totalBillsAllocated += bill.amount;
      });
      const advancePaymentAmount = totalAmount - totalBillsAllocated;
      const selectedAdvanceOption = $payment.find(`input[name="excess_${paymentId}"]:checked`).val();
      if (advancePaymentAmount > 0.01 && selectedAdvanceOption === 'advance') {
        groupData.advance_amount = advancePaymentAmount;
      }

      if (paymentType === 'both') {
        const obPortionText = $payment.find('.ob-portion').text();
        const obPortion = parseFloat(obPortionText) || 0;
        if (obPortion > 0) {
          groupData.ob_amount = obPortion;
        }
      }

      if (paymentType === 'opening_balance' && totalAmount > 0) {
        groupData.totalAmount = totalAmount;
        paymentGroups.push(groupData);
        hasValidPayments = true;
      } else if (paymentType === 'both' && (groupData.bills.length > 0 || (groupData.ob_amount || 0) > 0)) {
        groupData.totalAmount = totalAmount;
        paymentGroups.push(groupData);
        hasValidPayments = true;
      } else if (groupData.bills.length > 0) {
        groupData.totalAmount = totalAmount;
        paymentGroups.push(groupData);
        hasValidPayments = true;
      }
    });

    if (validationFailed) return false;

    if (!hasValidPayments) {
      if (window.toastr) window.toastr.error('Please add at least one payment method with bill allocations');
      return false;
    }

    const billTotals = {};
    paymentGroups.forEach((group) => {
      group.bills.forEach((bill) => {
        billTotals[bill.purchase_id] = (billTotals[bill.purchase_id] || 0) + bill.amount;
      });
    });

    for (const [billId, totalAllocated] of Object.entries(billTotals)) {
      const bill = availableSupplierPurchases.find((s) => s.id == billId);
      if (bill && totalAllocated > bill.total_due) {
        if (window.toastr) window.toastr.error(`Total allocation for ${bill.reference_no} exceeds bill amount`);
        return false;
      }
    }

    $('#submitBulkPayment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
      url: '/submit-flexible-bulk-purchase-payment',
      method: 'POST',
      data: {
        supplier_id: customerId,
        payment_date: paymentDate,
        payment_type: paymentType,
        payment_groups: paymentGroups,
        notes: $('#notes').val() || '',
        _token: csrfToken,
      },
      success: function (response) {
        if (response.status === 200) {
          $('#receiptReferenceNo').text(response.bulk_reference || 'N/A');
          $('#receiptTotalAmount').text(parseFloat(response.total_amount || 0).toFixed(2));
          $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
          $('#paymentReceiptModal').modal('show');
        }
      },
      error: function (xhr) {
        const error = xhr.responseJSON;
        if (window.toastr) window.toastr.error(error?.message || 'Flexible payment submission failed');
      },
      complete: function () {
        $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
      },
    });

    return false;
  }

  window.submitMultiMethodPayment = submitMultiMethodPayment;
});
