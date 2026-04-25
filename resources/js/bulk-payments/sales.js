// Sales bulk payments page bootstrap (safe Step 1 refactor).
// Only moves initial page setup (select2/date/default mode) out of Blade.

document.addEventListener('DOMContentLoaded', () => {
  if (!window.jQuery) return;
  const $ = window.jQuery;

  function loadCustomersForBulkPayment() {
    $.ajax({
      url: '/customer-get-all',
      method: 'GET',
      dataType: 'json',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest',
      },
      success: function (response) {
        const customerSelect = $('#customerSelect');
        customerSelect.empty();
        customerSelect.append('<option value="" selected disabled>Select Customer</option>');

        if (response.status === 200 && response.message && response.message.length > 0) {
          window.bulkPaymentShowRepDue = !!response.show_rep_invoice_due;
          response.message.forEach(function (customer) {
            if (customer.id === 1) return; // skip walk-in

            const openingBalance = parseFloat(customer.opening_balance) || 0;
            const saleDue = parseFloat(customer.total_sale_due) || 0;
            const currentDue = parseFloat(customer.current_due) || 0;
            const advanceCredit = parseFloat(customer.total_advance_credit) || 0;

            if (currentDue > 0) {
              const lastName = customer.last_name ? customer.last_name : '';
              const fullName = customer.first_name + (lastName ? ' ' + lastName : '');
              const mobileRaw = customer.mobile_no != null ? String(customer.mobile_no).trim() : '';
              const mobileSeg = mobileRaw ? ' · ' + mobileRaw : '';

              let displayText = fullName + mobileSeg + ' [Account Due: Rs. ' + currentDue.toFixed(2) + ']';
              if (openingBalance > 0 && saleDue > 0) {
                displayText += ' (Opening: Rs. ' + openingBalance.toFixed(2) + ', Sales: Rs. ' + saleDue.toFixed(2) + ')';
              } else if (openingBalance > 0) {
                displayText += ' (Opening Balance)';
              } else if (saleDue > 0) {
                displayText += ' (Sales Due)';
              }

              const myInv = parseFloat(customer.my_invoice_due) || 0;
              customerSelect.append(
                '<option value="' +
                  customer.id +
                  '" data-opening-balance="' +
                  openingBalance +
                  '" data-sale-due="' +
                  saleDue +
                  '" data-total-due="' +
                  currentDue +
                  '" data-advance-credit="' +
                  advanceCredit +
                  '" data-my-invoice-due="' +
                  myInv +
                  '">' +
                  displayText +
                  '</option>'
              );
            }
          });
        }
      },
      error: function (xhr, status, error) {
        // eslint-disable-next-line no-console
        console.error('AJAX error loading customers:', { status, error, responseText: xhr.responseText, statusCode: xhr.status });
        let errorMessage = 'Failed to load customers.';
        if (xhr.status === 401) errorMessage = 'Authentication required. Please refresh the page and login again.';
        else if (xhr.status === 403) errorMessage = 'Permission denied to access customer data.';
        $('#customerSelect').append('<option value="" disabled>Error: ' + errorMessage + '</option>');
      },
    });
  }

  // Expose for any remaining inline code that calls it
  window.loadCustomersForBulkPayment = loadCustomersForBulkPayment;

  // Initialize select2 with proper settings for standalone page
  if (typeof $.fn.select2 !== 'undefined') {
    $('#customerSelect').select2({
      placeholder: 'Select Customer',
      allowClear: true,
      width: '100%',
    });

    // Ensure search input gets focus when dropdown opens
    $('#customerSelect').on('select2:open', function () {
      setTimeout(() => {
        const searchField = document.querySelector('.select2-search__field');
        if (searchField) searchField.focus();
      }, 100);
    });
  }

  // Set today's date as default for "Paid On" field in YYYY-MM-DD format
  const today = new Date();
  const todayFormatted =
    today.getFullYear() +
    '-' +
    String(today.getMonth() + 1).padStart(2, '0') +
    '-' +
    String(today.getDate()).padStart(2, '0');

  $('#paidOn').val(todayFormatted);
  $('input[name="payment_date"]').val(todayFormatted);

  // Load customers immediately (function defined in Blade for now)
  setTimeout(() => {
    loadCustomersForBulkPayment();
  }, 1000);

  // Initialize Multiple Methods mode by default
  setTimeout(() => {
    $('#paymentMethod').val('multiple').trigger('change');
    if (typeof window.togglePaymentFields === 'function') {
      window.togglePaymentFields();
    }
    $('#paymentMethodSection').show();
    $('#notesSection').show();
    $('#submitButtonSection').show();
    $('input[name="paymentType"]:checked').trigger('change');
  }, 1500);

  // Customer selection change handler (kept identical to Blade logic)
  $(document).on('change', '#customerSelect', function () {
    const selectedOption = $(this).find(':selected');
    const customerId = $(this).val();

    if (!customerId) {
      $('#customerSummarySection').hide();
      $('#paymentMethodSection').hide();
      $('#notesSection').hide();
      $('#submitButtonSection').hide();
      $('#workflowStepsBar').hide();
      window.lastReturnSelectionSignature = null;
      window.lastLoadedSalesCustomerId = null;
      return;
    }

    $('#customerSummarySection').show();
    $('#paymentMethodSection').show();
    $('#notesSection').show();
    $('#submitButtonSection').show();
    $('#workflowStepsBar').show();
    $('#customerBalanceDetails').prop('open', true);

    const customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
    const saleDue = parseFloat(selectedOption.data('sale-due')) || 0;
    const totalDue = parseFloat(selectedOption.data('total-due')) || 0;
    const advanceCredit = parseFloat(selectedOption.data('advance-credit')) || 0;

    const ledgerCreditAvailable = Math.max(0, saleDue - totalDue);
    const combinedCredit = advanceCredit + ledgerCreditAvailable;

    $('input[name="paymentType"]:checked').trigger('change');

    $('#openingBalance').text(customerOpeningBalance.toFixed(2));
    $('#totalDueAmount').text(saleDue.toFixed(2));
    $('#accountDueAmount').text(totalDue.toFixed(2));

    $('#ledgerVsSalesHint').text('').hide().removeData('why-filled');
    window.lastReturnSelectionSignature = null;

    window.originalOpeningBalance = customerOpeningBalance;
    window.saleDueAmount = saleDue;
    window.totalCustomerDue = totalDue;
    window.customerAdvanceCredit = combinedCredit;

    if (combinedCredit > 0) {
      $('#advanceAmount').text(combinedCredit.toFixed(2));
      $('#advanceCount').show();
      $('#advanceToApplyToBills').text(combinedCredit.toFixed(2));
      $('#maxAdvanceCredit').text(combinedCredit.toFixed(2));
      $('#customerAdvanceSection').show();
    } else {
      $('#advanceCount').hide();
      $('#customerAdvanceSection').hide();
      $('#applyAdvanceCreditCheckbox').prop('checked', false);
      $('#advanceCreditAmountSection').hide();
    }

    if (typeof window.updateNetCustomerDue === 'function') {
      window.updateNetCustomerDue();
    }

    const myInvDueBulk = parseFloat(selectedOption.data('my-invoice-due')) || 0;
    if (window.bulkPaymentShowRepDue) {
      $('#bulkRepMyInvoicesAmount').text(myInvDueBulk.toFixed(2));
    }

    $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
    $('#globalPaymentAmount').val('');

    if (typeof window.loadCustomerReturns === 'function') {
      window.loadCustomerReturns(customerId);
    }
    if (typeof window.updateWorkflowProgress === 'function') {
      setTimeout(window.updateWorkflowProgress, 0);
    }
  });

  // ── Small UI handlers moved from Blade (safe Step: reduce Blade JS) ─────────
  $(document).on('click', '#toggleReturnsTable', function (e) {
    e.preventDefault();
    const $w = $('#returnsTableWrapper');
    const isVisible = $w.is(':visible');
    $w.slideToggle(150);
    $(this).text(isVisible ? '▼ show' : '▲ hide');
  });

  $(document).on('click', '#hideAdvanceBtn', function () {
    $('#customerAdvanceSection').slideUp();
  });

  $(document).on('click', '#customizePaymentLink', function (e) {
    e.preventDefault();
    const $section = $('#paymentTypeSection');
    if ($section.is(':visible')) {
      $section.slideUp();
      $(this).text('Payment options');
    } else {
      $section.slideDown();
      $(this).text('Hide payment options');
    }
  });

  $(document).on('click', '#showAdvancedOptions', function (e) {
    e.preventDefault();
    $('#advancedOptionsContainer').slideDown();
    $(this).html('<i class="fas fa-chevron-up"></i> Hide outstanding bills & payment allocation');
    $(this).attr('id', 'hideAdvancedOptions');
  });

  $(document).on('click', '#hideAdvancedOptions', function (e) {
    e.preventDefault();
    $('#advancedOptionsContainer').slideUp();
    $(this).html('<i class="fas fa-chevron-down"></i> Show outstanding bills & payment allocation');
    $(this).attr('id', 'showAdvancedOptions');
  });

  $(document).on('click', '#whyAmountLink', function (e) {
    e.preventDefault();
    const $h = $('#ledgerVsSalesHint');
    if (!$h.data('why-filled')) {
      $h.html(
        '<i class="fas fa-info-circle me-1"></i>' + 'Account Due (ledger) and Sales Due (invoices) can differ. The sales invoice due is shown above.'
      );
      $h.data('why-filled', true);
    }
    $h.toggle();
  });

  function togglePaymentFields() {
    const paymentMethod = $('#paymentMethod').val();
    const isMultiMethod = paymentMethod === 'multiple';

    // eslint-disable-next-line no-console
    console.log('Payment method changed to:', paymentMethod);

    $('#cardFields, #chequeFields, #bankTransferFields').addClass('d-none');
    $('#multiMethodContainer').toggleClass('d-none', !isMultiMethod);

    const modeIndicator = $('#methodModeIndicator');
    if (isMultiMethod) {
      modeIndicator.text('Multi Mode').removeClass('bg-info').addClass('bg-success');
      $('#globalPaymentAmount').addClass('d-none').prop('disabled', true).val('');
      $('label[for="globalPaymentAmount"]').addClass('d-none');
      $('#calculatedAmountMultipleWrap').removeClass('d-none').show();

      if (typeof window.syncPaymentMethodsEmptyState === 'function') window.syncPaymentMethodsEmptyState();
      if (typeof window.updateCalculatedAmountDisplay === 'function') window.updateCalculatedAmountDisplay();

      $('#advancedOptionsContainer').show();
      $('#showAdvancedOptions, #hideAdvancedOptions')
        .first()
        .html('<i class="fas fa-chevron-up"></i> Hide outstanding bills & payment allocation')
        .attr('id', 'hideAdvancedOptions');

      const customerId = $('#customerSelect').val();
      if (customerId) {
        const hasLoadedForSameCustomer = window.lastLoadedSalesCustomerId == customerId;
        const hasSalesCache = Array.isArray(window.availableCustomerSales) && window.availableCustomerSales.length > 0;
        if (!hasLoadedForSameCustomer || !hasSalesCache) {
          if (typeof window.loadCustomerSalesForMultiMethod === 'function') window.loadCustomerSalesForMultiMethod(customerId);
        }
      } else {
        $('#billsPaymentTableBody').html('<tr><td colspan="6" class="text-center text-muted">Please select a customer first</td></tr>');
      }
    } else {
      modeIndicator.text('Single Mode').removeClass('bg-success').addClass('bg-info');
      $('#globalPaymentAmount').removeClass('d-none').prop('disabled', false).attr('placeholder', '0.00');
      $('label[for="globalPaymentAmount"]').removeClass('d-none');
      $('#calculatedAmountMultipleWrap').addClass('d-none').hide();

      switch (paymentMethod) {
        case 'card':
          $('#cardFields').removeClass('d-none');
          break;
        case 'cheque':
          $('#chequeFields').removeClass('d-none');
          break;
        case 'bank_transfer':
          $('#bankTransferFields').removeClass('d-none');
          break;
      }
    }

    if ($('#customerSelect').val() && paymentMethod !== '') {
      $('#notesSection').show();
      $('#submitButtonSection').show();
    } else {
      $('#notesSection').hide();
      $('#submitButtonSection').hide();
    }
  }

  window.togglePaymentFields = togglePaymentFields;

  $(document).on('click', '#toggleMultiMode', function () {
    const currentMethod = $('#paymentMethod').val();
    if (currentMethod === 'multiple') {
      $('#paymentMethod').val('cash');
    } else {
      $('#paymentMethod').val('multiple');
    }
    togglePaymentFields();
  });

  $(document).on('change', 'input[name="paymentType"]', function () {
    const selectedType = $(this).val();
    const $paymentMethod = $('#paymentMethod');
    const $helpText = $('#paymentTypeHelp');

    const helpTexts = {
      sale_dues: '<i class="fas fa-info-circle"></i> Pay sale bills (invoices) for this customer',
      opening_balance: '<i class="fas fa-info-circle"></i> Pay only the opening balance amount',
      both: '<i class="fas fa-info-circle"></i> Pay both opening balance and sale bills together',
    };
    $helpText.html(helpTexts[selectedType] || '');

    if (selectedType === 'opening_balance') {
      $paymentMethod.find('option').prop('disabled', false);
      $paymentMethod.find('option[value="multiple"]').prop('disabled', true);

      if ($paymentMethod.val() === 'multiple' || $paymentMethod.val() === null) {
        $paymentMethod.val('cash');
      }

      if (typeof window.togglePaymentFields === 'function') window.togglePaymentFields();

      $('#bothPaymentTypeInfo').hide();
      $('.both-payment-hint').hide();
      $('.both-payment-breakdown').hide();
      updateGlobalPaymentMaxAndPlaceholder(selectedType);
      return;
    }

    // For both "both" and "sale_dues" we default to multiple-method mode
    $paymentMethod.find('option').prop('disabled', true);
    $paymentMethod.find('option[value="multiple"]').prop('disabled', false);
    $paymentMethod.val('multiple');

    if (typeof window.togglePaymentFields === 'function') window.togglePaymentFields();

    if (selectedType === 'both') {
      const selectedOption = $('#customerSelect').find(':selected');
      const customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
      $('#obInfoAmount').text(customerOpeningBalance.toFixed(2));
      $('#bothPaymentTypeInfo').show();
      $('.both-payment-hint').show();
      updateGlobalPaymentMaxAndPlaceholder(selectedType);
      return;
    }

    $('#bothPaymentTypeInfo').hide();
    $('.both-payment-hint').hide();
    $('.both-payment-breakdown').hide();
    updateGlobalPaymentMaxAndPlaceholder(selectedType);
  });

  function updateGlobalPaymentMaxAndPlaceholder(paymentType) {
    const customerId = $('#customerSelect').val();
    if (!customerId) return;

    // Show/hide sales list based on payment type
    if (paymentType === 'opening_balance') $('#salesListContainer').hide();
    else $('#salesListContainer').show();

    const customerOpeningBalance = window.originalOpeningBalance || 0;
    const saleDueAmount = window.saleDueAmount || 0;
    const totalDueAmount = parseFloat(String($('#totalCustomerDue').text()).replace('Rs. ', '')) || 0;

    if (paymentType === 'opening_balance') {
      $('#globalPaymentAmount').attr('max', customerOpeningBalance);
      $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + customerOpeningBalance.toFixed(2));
    } else if (paymentType === 'sale_dues') {
      $('#globalPaymentAmount').attr('max', saleDueAmount);
      $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + saleDueAmount.toFixed(2));
    } else if (paymentType === 'both') {
      $('#globalPaymentAmount').attr('max', totalDueAmount);
      $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));
    }

    const globalAmount = typeof window.parseAmountValue === 'function' ? window.parseAmountValue($('#globalPaymentAmount').val()) : 0;
    if (globalAmount > 0) $('#globalPaymentAmount').trigger('input');
  }

  window.updateGlobalPaymentMaxAndPlaceholder = updateGlobalPaymentMaxAndPlaceholder;

  $(document).on('input', '#globalPaymentAmount', function () {
    const globalAmount = typeof window.parseAmountValue === 'function' ? window.parseAmountValue($(this).val()) : parseFloat($(this).val() || 0) || 0;
    const customerOpeningBalance = window.originalOpeningBalance || 0;
    let remainingAmount = globalAmount;
    const paymentType = $('input[name="paymentType"]:checked').val();

    // eslint-disable-next-line no-console
    console.log('Global amount changed:', globalAmount, 'Payment type:', paymentType);

    const totalCustomerDue = parseFloat(String($('#totalCustomerDue').text()).replace('Rs. ', '')) || 0;
    let maxAmount = 0;
    if (paymentType === 'opening_balance') maxAmount = customerOpeningBalance;
    else if (paymentType === 'sale_dues') maxAmount = totalCustomerDue;
    else if (paymentType === 'both') maxAmount = totalCustomerDue;

    $(this).removeClass('is-invalid');
    $(this).next('.invalid-feedback').remove();

    if (globalAmount > maxAmount) {
      $(this).addClass('is-invalid').after('<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>');
      return;
    }

    if (paymentType === 'opening_balance') {
      const newOpeningBalance = Math.max(0, customerOpeningBalance - remainingAmount);
      $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));
      $('.reference-amount').val(0);
    } else if (paymentType === 'sale_dues') {
      $('.reference-amount').each(function () {
        const referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text()) || 0;
        if (remainingAmount > 0 && referenceDue > 0) {
          const paymentAmount = Math.min(remainingAmount, referenceDue);
          $(this).val(paymentAmount.toFixed(2));
          remainingAmount -= paymentAmount;
        } else {
          $(this).val(0);
        }
      });
      $('#openingBalance').text('Rs. ' + customerOpeningBalance.toFixed(2));
    } else if (paymentType === 'both') {
      let newOpeningBalance = customerOpeningBalance;
      if (newOpeningBalance > 0 && remainingAmount > 0) {
        if (remainingAmount <= newOpeningBalance) {
          newOpeningBalance -= remainingAmount;
          remainingAmount = 0;
        } else {
          remainingAmount -= newOpeningBalance;
          newOpeningBalance = 0;
        }
      }
      $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));

      $('.reference-amount').each(function () {
        const referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text()) || 0;
        if (remainingAmount > 0 && referenceDue > 0) {
          const paymentAmount = Math.min(remainingAmount, referenceDue);
          $(this).val(paymentAmount.toFixed(2));
          remainingAmount -= paymentAmount;
        } else {
          $(this).val(0);
        }
      });
    }

    if (typeof window.updateIndividualPaymentTotal === 'function') window.updateIndividualPaymentTotal();
  });

  // Amount formatting UX: edit plain number, display with separators
  $(document).on('focus', '#globalPaymentAmount, .payment-total-amount, .allocation-amount', function () {
    const raw = typeof window.parseAmountValue === 'function' ? window.parseAmountValue($(this).val()) : parseFloat($(this).val() || 0) || 0;
    if ($(this).val() !== '') $(this).val(raw ? String(raw) : '');
  });

  $(document).on('blur', '#globalPaymentAmount, .payment-total-amount, .allocation-amount', function () {
    const val = $(this).val();
    if (val === '') return;
    const num = typeof window.parseAmountValue === 'function' ? window.parseAmountValue(val) : parseFloat(val || 0) || 0;
    if (typeof window.formatAmountValue === 'function') $(this).val(window.formatAmountValue(num));
    else $(this).val(String(num.toFixed(2)));
  });

  $(document).on('change', '.return-action', function () {
    const returnId = $(this).data('return-id');
    const action = $(this).val();
    const $checkbox = $('.return-checkbox[data-return-id="' + returnId + '"]');
    const isChecked = $checkbox.prop('checked');

    if (isChecked && window.toastr) {
      if (action === 'apply_to_sales') {
        window.toastr.info('Return credit will reduce your payable amount on invoices.', 'Apply to invoice', { timeOut: 2500 });
      } else if (action === 'cash_refund') {
        window.toastr.info('Cash refund will be processed for this return', 'Action Changed', { timeOut: 2000 });
      }
    }

    if (typeof window.updateSelectedReturns === 'function') window.updateSelectedReturns();
  });

  // ── Returns module (safe Step 3 refactor) ───────────────────────────────────
  window.availableCustomerReturns = window.availableCustomerReturns || [];
  window.selectedReturns = window.selectedReturns || [];

  function getNetOutstandingSalesDueFromAllocations() {
    if (!window.availableCustomerSales || !window.availableCustomerSales.length) return null;
    const alloc = window.billReturnCreditAllocations || {};
    return window.availableCustomerSales.reduce((sum, sale) => {
      const ret = parseFloat(alloc[sale.id]) || 0;
      return sum + Math.max(0, parseFloat(sale.total_due || 0) - ret);
    }, 0);
  }

  window.getNetOutstandingSalesDueFromAllocations = getNetOutstandingSalesDueFromAllocations;

  function updateSelectedReturns() {
    const currentSelectionSignature = $('.return-checkbox:checked')
      .map(function () {
        const returnId = $(this).data('return-id');
        const action = $('.return-action[data-return-id="' + returnId + '"]').val() || '';
        const amount = parseFloat($(this).data('amount')) || 0;
        return returnId + ':' + action + ':' + amount.toFixed(2);
      })
      .get()
      .sort()
      .join('|');

    if (window.lastReturnSelectionSignature === currentSelectionSignature) return;
    window.lastReturnSelectionSignature = currentSelectionSignature;

    window.selectedReturns = [];
    let totalToApply = 0;
    let totalCashRefund = 0;

    $('.return-checkbox:checked').each(function () {
      const returnId = $(this).data('return-id');
      const amount = parseFloat($(this).data('amount'));
      const action = $('.return-action[data-return-id="' + returnId + '"]').val();

      window.selectedReturns.push({
        return_id: returnId,
        amount: amount,
        action: action,
      });

      if (action === 'apply_to_sales') totalToApply += amount;
      else if (action === 'cash_refund') totalCashRefund += amount;
    });

    $('#selectedReturnsCount').text(window.selectedReturns.length + ' selected');
    $('#selectedReturnsTotal').text('Rs. ' + (totalToApply + totalCashRefund).toFixed(2));
    $('#returnsToApplyToSales').text(totalToApply.toFixed(2));
    $('#returnsCashRefund').text('Rs. ' + totalCashRefund.toFixed(2));

    if (totalToApply > 0) {
      $('#returnCreditAppliedSummary')
        .html('Return credit used: <strong class="text-dark">Rs. ' + totalToApply.toFixed(2) + '</strong>')
        .show();
      $('#returnCount').hide();
    } else {
      $('#returnCreditAppliedSummary').empty().hide();
      if (window.availableCustomerReturns && window.availableCustomerReturns.length > 0) $('#returnCount').show();
    }

    if (totalToApply > 0 && window.availableCustomerSales && window.availableCustomerSales.length > 0) {
      if (typeof window.autoAllocateReturnCreditsToSales === 'function') window.autoAllocateReturnCreditsToSales(totalToApply);
    } else {
      if (!window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};
      window.billReturnCreditAllocations = {};
      window.lastReturnSelectionSignature = currentSelectionSignature;

      if (typeof window.updateReturnAppliedToHintsFromAllocations === 'function') window.updateReturnAppliedToHintsFromAllocations();
      if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
      if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();
    }

    if (typeof window.updateNetCustomerDue === 'function') window.updateNetCustomerDue();

    // eslint-disable-next-line no-console
    console.log('Selected returns updated:', window.selectedReturns);
    if (typeof window.updateSummaryTotals === 'function') window.updateSummaryTotals();
  }

  window.updateSelectedReturns = updateSelectedReturns;

  function updateNetCustomerDue() {
    const openingBalance = window.originalOpeningBalance || 0;
    const saleDue = window.saleDueAmount || 0;
    const totalDue = window.totalCustomerDue || 0; // Use actual total from backend (ledger)

    let returnsToApply = 0;
    $('.return-checkbox:checked').each(function () {
      const returnId = $(this).data('return-id');
      const action = $('.return-action[data-return-id="' + returnId + '"]').val();
      if (action === 'apply_to_sales') {
        returnsToApply += parseFloat($(this).data('amount')) || 0;
      }
    });

    let advanceCreditToApply = 0;
    if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
      advanceCreditToApply = parseFloat($('#advanceCreditAmountInput').val()) || 0;
    }

    const netFromBills =
      typeof window.getNetOutstandingSalesDueFromAllocations === 'function' ? window.getNetOutstandingSalesDueFromAllocations() : null;
    const displaySalesUnpaid = netFromBills !== null ? netFromBills : Math.max(0, saleDue - returnsToApply);

    if (typeof window.formatAmountValue === 'function') {
      $('#totalDueAmount').text(window.formatAmountValue(displaySalesUnpaid));
      if (saleDue > displaySalesUnpaid + 0.02) {
        $('#salesDueGrossAmount').text(window.formatAmountValue(saleDue));
      }
    } else {
      $('#totalDueAmount').text(displaySalesUnpaid.toFixed(2));
      if (saleDue > displaySalesUnpaid + 0.02) $('#salesDueGrossAmount').text(saleDue.toFixed(2));
    }

    // Cash to pay: gross sales invoices + opening balance owed − return credits you select − advance applied.
    // Do NOT use totalCustomerDue (ledger current_due) here — it often already nets return credit; subtracting returnsToApply again double-deducts.
    let netDue = openingBalance + saleDue - returnsToApply - advanceCreditToApply;
    if (netDue < 0) netDue = 0;

    if (typeof window.formatRs === 'function') $('#netCustomerDue').text(window.formatRs(netDue));
    else $('#netCustomerDue').text('Rs. ' + netDue.toFixed(2));

    $('#returnCreditBreakdownLine').empty().hide();
    $('#totalSettledBreakdownLine').empty().hide();

    window.netCustomerDue = netDue;

    // eslint-disable-next-line no-console
    console.log('Net customer due updated:', {
      openingBalance,
      saleDue,
      totalDueLedger: totalDue,
      returnsToApply,
      advanceCreditToApply,
      netDue,
    });

    if (typeof window.updateReturnApplyHint === 'function') window.updateReturnApplyHint();
  }

  window.updateNetCustomerDue = updateNetCustomerDue;

  function normalizeAllBillAllocationRows() {
    $('.payment-method-item').each(function () {
      const $paymentContainer = $(this);
      const rowsByBill = {};

      $paymentContainer.find('.bill-allocation-row').each(function () {
        const $row = $(this);
        const billId = $row.find('.bill-select').val();
        const amount = typeof window.parseAmountValue === 'function' ? window.parseAmountValue($row.find('.allocation-amount').val()) : 0;

        // Remove only truly empty rows. Keep selected rows even if amount is not typed yet.
        if (!billId) {
          $row.remove();
          return;
        }

        if (amount <= 0.001) {
          $row.find('.allocation-amount').data('prev-amount', 0);
          return;
        }

        if (!rowsByBill[billId]) rowsByBill[billId] = [];
        rowsByBill[billId].push($row);
      });

      Object.keys(rowsByBill).forEach((billId) => {
        const rows = rowsByBill[billId];
        if (!rows.length) return;

        const bill = (window.availableCustomerSales || []).find((s) => String(s.id) === String(billId));
        const returnCreditApplied = window.billReturnCreditAllocations ? window.billReturnCreditAllocations[billId] || 0 : 0;
        const billDue =
          bill && typeof window.parseAmountValue === 'function' ? window.parseAmountValue(bill.total_due) : parseFloat(bill?.total_due || 0) || 0;
        const maxForBill = Math.max(0, billDue - returnCreditApplied);

        let mergedAmount = rows.reduce((sum, $r) => {
          const v = typeof window.parseAmountValue === 'function' ? window.parseAmountValue($r.find('.allocation-amount').val()) : 0;
          return sum + v;
        }, 0);
        mergedAmount = Math.max(0, Math.min(mergedAmount, maxForBill));

        const $keep = rows[0];
        const $keepInput = $keep.find('.allocation-amount');
        const $keepHint = $keep.find('.bill-amount-hint');
        const $keepSelect = $keep.find('.bill-select');

        if (typeof window.formatAmountValue === 'function') {
          $keepInput.val(window.formatAmountValue(mergedAmount));
        } else {
          $keepInput.val(String(mergedAmount.toFixed(2)));
        }
        $keepInput.data('prev-amount', mergedAmount);

        if (bill && typeof window.formatAmountValue === 'function') {
          const selectedText = `${bill.invoice_no} · Pay now Rs.${window.formatAmountValue(maxForBill)}`;
          $keepSelect.find('option:selected').text(selectedText);
        }

        const after = Math.max(0, maxForBill - mergedAmount);
        if (after <= 0.01) {
          $keepHint
            .text(returnCreditApplied > 0 ? 'Settled (credit applied)' : 'Settled')
            .removeClass('text-muted')
            .addClass('text-success')
            .show();
        } else {
          const afterTxt = typeof window.formatAmountValue === 'function' ? window.formatAmountValue(after) : after.toFixed(2);
          $keepHint.text(`Pay now after this: Rs. ${afterTxt}`).removeClass('text-success').addClass('text-muted').show();
        }

        for (let i = 1; i < rows.length; i++) rows[i].remove();
      });

      const paymentId = $paymentContainer.data('payment-id');
      if (paymentId && typeof window.updatePaymentMethodTotal === 'function') {
        window.updatePaymentMethodTotal(paymentId);
      }
    });

    if (typeof window.recalcBillPaymentAllocationsFromUI === 'function') window.recalcBillPaymentAllocationsFromUI();
  }

  window.normalizeAllBillAllocationRows = normalizeAllBillAllocationRows;

  function updateExistingBillAllocationsForReturnCredits() {
    $('.bill-allocation-row').each(function () {
      const $row = $(this);
      const $billSelect = $row.find('.bill-select');
      const $amountInput = $row.find('.allocation-amount');
      const billId = $billSelect.val();

      if (!billId) return;

      const bill = (window.availableCustomerSales || []).find((s) => s.id == billId);
      if (!bill) return;

      const currentAllocationAmount =
        typeof window.parseAmountValue === 'function' ? window.parseAmountValue($amountInput.val()) : parseFloat($amountInput.val() || 0) || 0;
      const returnCreditApplied = window.billReturnCreditAllocations ? window.billReturnCreditAllocations[billId] || 0 : 0;

      const prevAmount = $amountInput.data('prev-amount') || 0;
      const otherPaymentAllocations = (window.billPaymentAllocations?.[billId] || 0) - prevAmount;

      const billTotalDue = typeof window.parseAmountValue === 'function' ? window.parseAmountValue(bill.total_due) : parseFloat(bill.total_due || 0) || 0;
      const billRemainingDue = billTotalDue - returnCreditApplied - otherPaymentAllocations;

      let needsUpdate = false;
      let newAmount = currentAllocationAmount;

      if (currentAllocationAmount > billRemainingDue) {
        newAmount = Math.max(0, billRemainingDue);
        needsUpdate = true;
      } else if (returnCreditApplied === 0 && prevAmount > currentAllocationAmount && billRemainingDue > currentAllocationAmount) {
        newAmount = Math.min(prevAmount, billRemainingDue);
        needsUpdate = true;
      }

      if (!needsUpdate) return;

      $amountInput.data('system-update', true);
      if (typeof window.formatAmountValue === 'function') $amountInput.val(window.formatAmountValue(newAmount));
      else $amountInput.val(String(newAmount.toFixed(2)));

      if (!window.billPaymentAllocations) window.billPaymentAllocations = {};
      window.billPaymentAllocations[billId] = otherPaymentAllocations + newAmount;
      $amountInput.data('prev-amount', newAmount);

      const $hint = $row.find('.bill-amount-hint');
      const remainingAfterPayment = billRemainingDue - newAmount;

      if (returnCreditApplied > 0) {
        const payNowTxt = typeof window.formatAmountValue === 'function' ? window.formatAmountValue(billRemainingDue) : billRemainingDue.toFixed(2);
        $hint.text(`Pay now: Rs. ${payNowTxt}`).removeClass('text-success').addClass('text-muted');
      } else if (remainingAfterPayment <= 0.01) {
        $hint.text('Settled').removeClass('text-muted').addClass('text-success');
      } else {
        const afterTxt = typeof window.formatAmountValue === 'function' ? window.formatAmountValue(remainingAfterPayment) : remainingAfterPayment.toFixed(2);
        $hint.text(`Pay now after this: Rs. ${afterTxt}`).removeClass('text-success').addClass('text-muted');
      }

      const paymentId = $row.closest('.payment-method-item').data('payment-id');
      if (paymentId && window.paymentMethodAllocations?.[paymentId] && typeof window.updatePaymentMethodTotal === 'function') {
        window.updatePaymentMethodTotal(paymentId);
      }

      setTimeout(() => {
        $amountInput.data('system-update', false);
      }, 200);

      // eslint-disable-next-line no-console
      console.log(
        `Updated bill ${billId} allocation from ${currentAllocationAmount.toFixed(2)} to ${newAmount.toFixed(2)} due to return credit change`
      );
    });

    normalizeAllBillAllocationRows();
    if (typeof window.updateNetCustomerDue === 'function') window.updateNetCustomerDue();
    if (typeof window.updateSummaryTotals === 'function') window.updateSummaryTotals();
  }

  window.updateExistingBillAllocationsForReturnCredits = updateExistingBillAllocationsForReturnCredits;

  function updateIndividualPaymentTotal() {
    let total = 0;
    $('.reference-amount').each(function () {
      const amount = parseFloat($(this).val()) || 0;
      total += amount;
    });

    $('#individualPaymentTotal').text('Rs. ' + total.toFixed(2));
    return total;
  }

  window.updateIndividualPaymentTotal = updateIndividualPaymentTotal;

  $(document).on('input', '.reference-amount', function () {
    const referenceDue = parseFloat($(this).attr('max'));
    const paymentAmount = parseFloat($(this).val()) || 0;

    $(this).removeClass('is-invalid');
    $(this).next('.invalid-feedback').remove();

    if (paymentAmount > referenceDue) {
      $(this).addClass('is-invalid').after('<span class="invalid-feedback d-block">Amount exceeds total due.</span>');
    }

    updateIndividualPaymentTotal();
  });

  function hideReturnsUI() {
    $('#customerReturnsSection').hide();
    $('#totalReturnCredits').text('0.00');
    $('#returnCount').hide();
    window.lastReturnSelectionSignature = null;
    if (typeof window.updateNetCustomerDue === 'function') window.updateNetCustomerDue();
  }

  function populateReturnsTable() {
    const tableBody = $('#customerReturnsTableBody');
    tableBody.empty();

    (window.availableCustomerReturns || []).forEach(function (returnBill) {
      const totalDue = parseFloat(returnBill.total_due) || 0;
      const returnDate = returnBill.return_date ? new Date(returnBill.return_date).toLocaleDateString('en-GB') : 'N/A';

      const row =
        '<tr class="return-row" data-return-id="' +
        returnBill.id +
        '" style="cursor: pointer;">' +
        '<td class="return-checkbox-cell" onclick="event.stopPropagation();">' +
        '<input type="checkbox" class="return-checkbox" data-return-id="' +
        returnBill.id +
        '" data-amount="' +
        totalDue +
        '">' +
        '</td>' +
        '<td><strong>' +
        returnBill.invoice_number +
        '</strong></td>' +
        '<td>' +
        returnDate +
        '</td>' +
        '<td class="text-danger fw-bold">Rs. ' +
        totalDue.toFixed(2) +
        '</td>' +
        '<td onclick="event.stopPropagation();">' +
        '<select class="form-select form-select-sm return-action" data-return-id="' +
        returnBill.id +
        '" style="font-size: 0.8rem;">' +
        '<option value="apply_to_sales" selected title="Reduces your payable amount">Apply to invoice</option>' +
        '<option value="cash_refund" title="Refund cash to customer">Cash refund</option>' +
        '</select>' +
        '<span class="text-muted small ms-2 return-applied-to-hint" id="returnAppliedTo_' +
        returnBill.id +
        '" style="display: none;"></span>' +
        '</td>' +
        '</tr>';

      tableBody.append(row);
    });
  }

  function loadCustomerReturns(customerId) {
    $.ajax({
      url: '/customer-returns/' + customerId,
      method: 'GET',
      dataType: 'json',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'X-Requested-With': 'XMLHttpRequest',
      },
      success: function (response) {
        if (response.returns && response.returns.length > 0) {
          window.availableCustomerReturns = response.returns.filter((ret) => {
            return parseFloat(ret.total_due) > 0 && ret.payment_status !== 'Paid';
          });

          if (window.availableCustomerReturns.length > 0) {
            populateReturnsTable();
            $('#returnsTableWrapper').show();
            $('#toggleReturnsTable').text('▲ hide');

            const totalReturnCredits = window.availableCustomerReturns.reduce((sum, ret) => sum + parseFloat(ret.total_due), 0);
            $('#totalReturnCredits').text(totalReturnCredits.toFixed(2));
            $('#returnsToApplyToSales').text(totalReturnCredits.toFixed(2));
            $('#returnCountNumber').text(window.availableCustomerReturns.length);
            $('#returnCount').show();
            $('#customerReturnsSection').show();

            if (typeof window.updateNetCustomerDue === 'function') window.updateNetCustomerDue();
          } else {
            hideReturnsUI();
          }
        } else {
          hideReturnsUI();
        }
      },
      error: function (xhr, status, error) {
        // eslint-disable-next-line no-console
        console.error('Error loading customer returns:', error);
        hideReturnsUI();
        if (xhr.status === 404) {
          // eslint-disable-next-line no-console
          console.log('Returns endpoint not found');
        }
      },
    });
  }

  window.loadCustomerReturns = loadCustomerReturns;

  $(document).on('change', '#selectAllReturns', function () {
    const isChecked = $(this).prop('checked');
    $('.return-checkbox').prop('checked', isChecked).trigger('change');
  });

  $(document).on('click', '.return-row', function (e) {
    if ($(e.target).hasClass('return-checkbox') || $(e.target).hasClass('return-action')) return;
    const $checkbox = $(this).find('.return-checkbox');
    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
    $(this).toggleClass('table-active', $checkbox.prop('checked'));
  });

  $(document).on('change', '.return-checkbox', function () {
    const $row = $(this).closest('.return-row');
    $row.toggleClass('table-active', $(this).prop('checked'));
    if (typeof window.updateSelectedReturns === 'function') window.updateSelectedReturns();
  });

  // ── Phase 4: Summary + workflow helpers ────────────────────────────────────
  function updatePaymentMethodHints(cashDueAmount) {
    const due = Math.max(0, window.parseAmountValue ? window.parseAmountValue(cashDueAmount) : 0);
    const $addBtn = $('#addFlexiblePayment');
    const $title = $('#emptyPaymentTitle');
    const $hint = $('#emptyPaymentHint');

    $addBtn.html('<i class="fas fa-plus"></i> Add payment method');

    if (due <= 0.01) {
      $title.text('No cash collection needed');
      $hint.text('Return credit already covers this selection. Add method only if you collect additional amount.');
    } else {
      $title.text('Collect remaining cash: Rs. ' + (window.formatAmountValue ? window.formatAmountValue(due) : String(due)));
      $hint.text('Add cash, card, or other payment and allocate it to bills on the left.');
    }
  }

  function updateWorkflowProgress() {
    const $steps = $('.order-flow-step');
    if (!$('#customerSelect').val()) {
      $steps.removeClass('text-primary fw-bold text-success').addClass('text-muted');
      return;
    }
    $steps.removeClass('text-primary fw-bold text-success');
    $steps.eq(0).addClass('text-primary fw-bold');
    const hasPm = $('.payment-method-item').length > 0;
    let allocSum = 0;
    if (typeof window.billPaymentAllocations === 'object' && window.billPaymentAllocations !== null) {
      allocSum = Object.values(window.billPaymentAllocations).reduce((s, v) => s + (parseFloat(v) || 0), 0);
    }
    if (hasPm) $steps.eq(1).addClass('text-primary fw-bold');
    else $steps.eq(1).addClass('text-muted');

    if (allocSum > 0.01) $steps.eq(2).addClass('text-primary fw-bold');
    else $steps.eq(2).addClass('text-muted');

    const balTxt = $('#balanceAmount').text().replace(/[^\d.-]/g, '');
    const bal = parseFloat(balTxt) || 0;
    if (hasPm && Math.abs(bal) < 0.02 && allocSum > 0.01) $steps.eq(3).addClass('text-success fw-bold');
    else $steps.eq(3).addClass('text-muted');
  }

  function updateSummaryTotals() {
    try {
      const sales = window.availableCustomerSales || [];
      const retAlloc = window.billReturnCreditAllocations || {};

      const totalBills = sales.length || 0;
      const totalDueAmount = sales.reduce((sum, sale) => {
        const ret = parseFloat(retAlloc[sale.id]) || 0;
        return sum + Math.max(0, parseFloat(sale.total_due || 0) - ret);
      }, 0);

      let totalPaymentAmount = 0;
      const pma = window.paymentMethodAllocations || {};
      if (pma && Object.keys(pma).length > 0) {
        Object.values(pma).forEach((payment) => {
          totalPaymentAmount += payment.totalAmount || 0;
        });
      }

      let totalCashAllocatedToBills = 0;
      if (typeof window.billPaymentAllocations === 'object' && window.billPaymentAllocations !== null) {
        Object.values(window.billPaymentAllocations).forEach((v) => {
          totalCashAllocatedToBills += parseFloat(v) || 0;
        });
      }

      let expectedSettlement = totalCashAllocatedToBills;
      if (expectedSettlement < 0.01) {
        expectedSettlement = sales.reduce((sum, sale) => sum + parseFloat(sale.total_due || 0), 0);
        let returnsChecked = 0;
        $('.return-checkbox:checked').each(function () {
          const rid = $(this).data('return-id');
          const action = $('.return-action[data-return-id="' + rid + '"]').val();
          if (action === 'apply_to_sales') {
            returnsChecked += parseFloat($(this).data('amount')) || 0;
          }
        });
        let advanceApply = 0;
        if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
          advanceApply = parseFloat($('#advanceCreditAmountInput').val()) || 0;
        }
        expectedSettlement = Math.max(0, expectedSettlement - returnsChecked - advanceApply);
      }

      let balanceAmount = totalDueAmount - totalPaymentAmount;

      const selected = window.selectedReturns || [];
      const hasAppliedReturnCredits = selected.some((r) => r.action === 'apply_to_sales');
      const hasPaymentMethods = $('.payment-method-item').length > 0;
      const showReturnAdjustedState = hasAppliedReturnCredits && !hasPaymentMethods && totalPaymentAmount < 0.01;
      if (showReturnAdjustedState) balanceAmount = 0;

      const $totalBillsCount = $('#totalBillsCount');
      const $summaryDueAmount = $('#summaryDueAmount');
      const $totalPaymentAmount = $('#totalPaymentAmount');
      const $balanceAmount = $('#balanceAmount');

      if ($totalBillsCount.length) $totalBillsCount.text(totalBills);
      if ($summaryDueAmount.length) $summaryDueAmount.text(`Rs. ${window.formatAmountValue ? window.formatAmountValue(totalDueAmount) : totalDueAmount}`);

      if ($totalPaymentAmount.length) {
        $totalPaymentAmount.text(`Rs. ${window.formatAmountValue ? window.formatAmountValue(totalPaymentAmount) : totalPaymentAmount}`);
        if (totalPaymentAmount > 0) $totalPaymentAmount.removeClass('text-muted').addClass('text-success');
        else $totalPaymentAmount.removeClass('text-success').addClass('text-muted');
      }

      const $balanceLabel = $('#balanceLabel');
      if ($balanceLabel.length) $balanceLabel.text(showReturnAdjustedState ? 'Return credit adjusted' : 'Balance due');

      if ($balanceAmount.length) {
        if (balanceAmount > 0) {
          $balanceAmount
            .text(`Rs. ${window.formatAmountValue ? window.formatAmountValue(balanceAmount) : balanceAmount}`)
            .removeClass('text-success text-danger text-warning')
            .addClass('text-primary');
        } else if (balanceAmount < 0) {
          $balanceAmount
            .text(`Rs. ${window.formatAmountValue ? window.formatAmountValue(balanceAmount) : balanceAmount}`)
            .removeClass('text-warning text-success text-primary')
            .addClass('text-danger');
        } else {
          $balanceAmount
            .text(`Rs. ${window.formatAmountValue ? window.formatAmountValue(balanceAmount) : balanceAmount}`)
            .removeClass('text-warning text-danger text-primary')
            .addClass('text-success');
        }
      }

      updatePaymentMethodHints(balanceAmount);
      updateWorkflowProgress();

      const $submitSection = $('#submitButtonSection');
      if ($submitSection.length) {
        const customerId = $('#customerSelect').val();
        if (customerId) $submitSection.fadeIn();
        else $submitSection.fadeOut();
      }

      if (typeof window.updateCalculatedAmountDisplay === 'function') window.updateCalculatedAmountDisplay();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Error in updateSummaryTotals:', error);
    }
  }

  window.updatePaymentMethodHints = updatePaymentMethodHints;
  window.updateWorkflowProgress = updateWorkflowProgress;
  window.updateSummaryTotals = updateSummaryTotals;

  // ── Phase 8 (safe): empty payment-methods panel helpers (`escapeHtml` → common.js) ──
  const FLEXIBLE_PAYMENTS_EMPTY_HTML = `
        <div id="flexiblePaymentsEmptyState" class="p-3 text-center text-muted small h-100 d-flex flex-column justify-content-center align-items-center">
            <i class="fas fa-wallet fa-2x mb-2 text-secondary opacity-75"></i>
            <div class="fw-semibold text-dark mb-1" id="emptyPaymentTitle">Add payment method</div>
            <div class="mb-3" id="emptyPaymentHint">Cash, card, or other — then allocate to bills on the left.</div>
        </div>`;

  function syncPaymentMethodsEmptyState() {
    const $list = $('#flexiblePaymentsList');
    if (!$list.length) return;
    const count = $list.find('.payment-method-item').length;
    let $empty = $('#flexiblePaymentsEmptyState');
    if (count === 0) {
      if (!$empty.length) {
        $list.html(FLEXIBLE_PAYMENTS_EMPTY_HTML);
        $empty = $('#flexiblePaymentsEmptyState');
      }
      $empty.removeClass('d-none').addClass('d-flex');
      $list.addClass('payment-methods-container--empty');
    } else {
      $empty.removeClass('d-flex').addClass('d-none');
      $list.removeClass('payment-methods-container--empty');
    }
  }

  window.FLEXIBLE_PAYMENTS_EMPTY_HTML = FLEXIBLE_PAYMENTS_EMPTY_HTML;
  window.syncPaymentMethodsEmptyState = syncPaymentMethodsEmptyState;

  /** Hint under Amount to Pay when returns exist but none set to apply to sales */
  function updateReturnApplyHint() {
    const $hint = $('#amountToPayReturnHint');
    if (!$hint.length) return;
    let hasApply = false;
    $('.return-checkbox:checked').each(function () {
      const rid = $(this).data('return-id');
      if ($('.return-action[data-return-id="' + rid + '"]').val() === 'apply_to_sales') {
        hasApply = true;
      }
    });
    const returns = window.availableCustomerReturns || [];
    const show = $('#customerReturnsSection').is(':visible') && returns.length > 0 && !hasApply;
    $hint.toggle(!!show);
  }
  window.updateReturnApplyHint = updateReturnApplyHint;

  // ── Phase 5: Return-credit allocation UI (Swal) ────────────────────────────
  function updateReturnAppliedToHintsFromAllocations() {
    $('[id^="returnAppliedTo_"]').text('').hide();

    if (!window.billReturnCreditAllocations || Object.keys(window.billReturnCreditAllocations).length === 0) {
      return;
    }

    const sales = window.availableCustomerSales || [];
    const returns = window.availableCustomerReturns || [];

    const sortedSales = [...sales].sort((a, b) => new Date(a.sales_date) - new Date(b.sales_date));

    const remainingAlloc = {};
    Object.keys(window.billReturnCreditAllocations).forEach((k) => {
      remainingAlloc[String(k)] = parseFloat(window.billReturnCreditAllocations[k]) || 0;
    });

    const returnsOrdered = [];
    $('.return-checkbox:checked').each(function () {
      const rid = $(this).data('return-id');
      if ($('.return-action[data-return-id="' + rid + '"]').val() === 'apply_to_sales') {
        returnsOrdered.push({ id: rid, amount: parseFloat($(this).data('amount')) || 0 });
      }
    });

    returnsOrdered.sort((a, b) => {
      const ra = returns.find((r) => String(r.id) === String(a.id));
      const rb = returns.find((r) => String(r.id) === String(b.id));
      if (!ra || !rb) return 0;
      return new Date(ra.return_date) - new Date(rb.return_date);
    });

    returnsOrdered.forEach((ret) => {
      let credit = ret.amount;
      const applied = [];
      for (const sale of sortedSales) {
        if (credit <= 0.001) break;
        const bid = String(sale.id);
        const room = remainingAlloc[bid] || 0;
        if (room <= 0.001) continue;
        const take = Math.min(credit, room);
        if (take > 0.001) applied.push(sale.invoice_no);
        remainingAlloc[bid] = room - take;
        credit -= take;
      }
      const $span = $('#returnAppliedTo_' + ret.id);
      if (applied.length) {
        $span.text('→ Applied Rs. ' + ret.amount.toFixed(2) + ' to: ' + [...new Set(applied)].join(', ')).show();
      }
    });

    $('.return-row').each(function () {
      const rid = $(this).data('return-id');
      const checked = $(this).find('.return-checkbox').prop('checked');
      const action = $('.return-action[data-return-id="' + rid + '"]').val();
      if (!checked || action !== 'apply_to_sales') {
        $('#returnAppliedTo_' + rid).text('').hide();
      }
    });
  }

  function autoAllocateReturnCreditsToSales(returnCreditAmount) {
    if (!window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};
    window.billReturnCreditAllocations = {};

    let remainingCredit = returnCreditAmount;
    const sales = window.availableCustomerSales || [];
    const sortedSales = [...sales].sort((a, b) => new Date(a.sales_date) - new Date(b.sales_date));

    for (const sale of sortedSales) {
      if (remainingCredit <= 0) break;
      const saleDue = parseFloat(sale.total_due) || 0;
      const allocatedAmount = Math.min(remainingCredit, saleDue);
      if (allocatedAmount > 0) {
        window.billReturnCreditAllocations[sale.id] = allocatedAmount;
        remainingCredit -= allocatedAmount;
      }
    }

    updateReturnAppliedToHintsFromAllocations();
    if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
    if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();

    if (window.toastr && returnCreditAmount > 0) {
      const allocated = returnCreditAmount - remainingCredit;
      const msg =
        `Rs.${allocated.toFixed(2)} return credit FIFO-applied` +
        (remainingCredit > 0 ? ` (Rs.${remainingCredit.toFixed(2)} unused)` : '') +
        `. Use Reallocate or bill rows to change.`;
      window.toastr.info(msg, '', { timeOut: 4000, progressBar: true, closeButton: true });
    }
  }

  function showAdjustCreditDialog(saleId) {
    const sales = window.availableCustomerSales || [];
    const sale = sales.find((s) => s.id == saleId);
    if (!sale) return;

    const currentCredit = (window.billReturnCreditAllocations && window.billReturnCreditAllocations[saleId]) || 0;

    let totalReturnCredit = 0;
    $('.return-checkbox:checked').each(function () {
      const action = $('.return-action[data-return-id="' + $(this).data('return-id') + '"]').val();
      if (action === 'apply_to_sales') totalReturnCredit += parseFloat($(this).data('amount'));
    });

    let otherAllocations = 0;
    if (window.billReturnCreditAllocations) {
      Object.keys(window.billReturnCreditAllocations).forEach((key) => {
        if (key != saleId) otherAllocations += window.billReturnCreditAllocations[key];
      });
    }

    const availableCredit = totalReturnCredit - otherAllocations;
    const maxAllowable = Math.min(availableCredit, sale.total_due);

    if (!window.Swal) return;
    window.Swal.fire({
      title: `Adjust Return Credit`,
      html: `
        <div class="text-start">
          <p><strong>Bill:</strong> ${sale.invoice_no}</p>
          <p><strong>Bill Due:</strong> Rs.${Number(sale.total_due).toFixed(2)}</p>
          <p><strong>Current Allocated:</strong> Rs.${Number(currentCredit).toFixed(2)}</p>
          <p><strong>Total Return Credit:</strong> Rs.${Number(totalReturnCredit).toFixed(2)}</p>
          <p><strong>Available to Allocate:</strong> Rs.${Number(availableCredit).toFixed(2)}</p>
          <hr>
          <label class="form-label">Enter amount (0 to remove):</label>
          <input type="number" id="creditAmount" class="form-control"
            value="${currentCredit}" min="0" max="${maxAllowable}" step="0.01">
          <small class="text-muted">Max: Rs.${Number(maxAllowable).toFixed(2)}</small>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Apply',
      cancelButtonText: 'Cancel',
      preConfirm: () => {
        const amount = parseFloat(document.getElementById('creditAmount').value) || 0;
        if (amount < 0 || amount > maxAllowable) {
          window.Swal.showValidationMessage(`Amount must be between 0 and ${maxAllowable.toFixed(2)}`);
          return false;
        }
        return amount;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        const newAmount = result.value;
        if (!window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};
        if (newAmount > 0) window.billReturnCreditAllocations[saleId] = newAmount;
        else delete window.billReturnCreditAllocations[saleId];

        if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
        if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();
        updateReturnAppliedToHintsFromAllocations();
        if (window.toastr) window.toastr.success(`Return credit updated to Rs.${Number(newAmount).toFixed(2)}`, 'Updated');
      }
    });
  }

  window.updateReturnAppliedToHintsFromAllocations = updateReturnAppliedToHintsFromAllocations;
  window.autoAllocateReturnCreditsToSales = autoAllocateReturnCreditsToSales;
  window.showAdjustCreditDialog = showAdjustCreditDialog;

  // ── Phase 6: Multi-method submit/payload builder ───────────────────────────
  function submitMultiMethodPayment() {
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

    const selectedReturns = window.selectedReturns || [];
    const availableCustomerSales = window.availableCustomerSales || [];

    const paymentGroups = [];
    let hasValidPayments = false;
    let validationFailed = false;
    let groupIndex = 0;

    let billReturnAllocations = {};
    const hasApplyToSalesReturns = selectedReturns.some((r) => r.action === 'apply_to_sales');
    if (hasApplyToSalesReturns && window.billReturnCreditAllocations) {
      billReturnAllocations = window.billReturnCreditAllocations;
    }

    $('.payment-method-item').each(function () {
      const $payment = $(this);
      const paymentId = $payment.data('payment-id');
      const method = $payment.find('.payment-method-select').val();
      const totalAmount = parseAmountValue($payment.find('.payment-total-amount').val());

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
        const amount = parseAmountValue($allocation.find('.allocation-amount').val());
        if (billId && amount > 0) {
          groupData.bills.push({ sale_id: parseInt(billId, 10), amount });
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
        groupData.ob_amount = parseAmountValue(obPortionText);
      }

      if (paymentType === 'opening_balance') {
        paymentGroups.push(groupData);
        hasValidPayments = true;
      } else if (paymentType === 'both' && (groupData.bills.length > 0 || (groupData.ob_amount || 0) > 0)) {
        paymentGroups.push(groupData);
        hasValidPayments = true;
      } else if (groupData.bills.length > 0) {
        paymentGroups.push(groupData);
        hasValidPayments = true;
      }
    });

    if (validationFailed) return false;

    const canSubmitReturnOnlyWithoutCash = () => {
      if (paymentGroups.length > 0) return false;
      if (!selectedReturns || selectedReturns.length === 0) return false;
      const hasApply = selectedReturns.some((r) => r.action === 'apply_to_sales');
      const hasRefund = selectedReturns.some((r) => r.action === 'cash_refund');
      if (hasApply) {
        let allocSum = 0;
        if (billReturnAllocations && typeof billReturnAllocations === 'object') {
          Object.values(billReturnAllocations).forEach((v) => {
            allocSum += parseFloat(v) || 0;
          });
        }
        if (allocSum < 0.01) return false;
      }
      return hasApply || hasRefund;
    };

    if (!hasValidPayments && !canSubmitReturnOnlyWithoutCash()) {
      const hasApplyOnly = selectedReturns && selectedReturns.some((r) => r.action === 'apply_to_sales');
      let allocSum = 0;
      if (billReturnAllocations && typeof billReturnAllocations === 'object') {
        Object.values(billReturnAllocations).forEach((v) => {
          allocSum += parseFloat(v) || 0;
        });
      }

      if (hasApplyOnly && allocSum < 0.01) {
        if (window.toastr)
          window.toastr.warning(
            'Allocation missing: choose bill-wise return credit via Change Allocation, then submit.',
            'Allocation Required',
            { timeOut: 5000 }
          );
        setTimeout(() => {
          $('#reallocateAllCreditsBtn').trigger('click');
        }, 120);
        return false;
      }

      if (window.toastr)
        window.toastr.error(
          'Please add at least one payment method with bill allocations, or submit returns only (apply to sales with credit allocated / cash refund).'
        );
      return false;
    }

    const billTotals = {};
    paymentGroups.forEach((group) => {
      group.bills.forEach((bill) => {
        billTotals[bill.sale_id] = (billTotals[bill.sale_id] || 0) + bill.amount;
      });
    });

    for (const [billId, totalAllocated] of Object.entries(billTotals)) {
      const bill = availableCustomerSales.find((s) => s.id == billId);
      if (bill && totalAllocated > bill.total_due) {
        if (window.toastr) window.toastr.error(`Total allocation for ${bill.invoice_no} exceeds bill amount`);
        return false;
      }
    }

    $('#submitBulkPayment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    let advanceCreditApplied = 0;
    if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
      advanceCreditApplied = parseFloat($('#advanceCreditAmountInput').val()) || 0;
    }

    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
      url: '/submit-flexible-bulk-payment',
      method: 'POST',
      contentType: 'application/json; charset=UTF-8',
      dataType: 'json',
      data: JSON.stringify({
        customer_id: customerId,
        payment_date: paymentDate,
        payment_type: paymentType,
        payment_groups: paymentGroups,
        selected_returns: selectedReturns,
        bill_return_allocations: billReturnAllocations,
        advance_credit_applied: advanceCreditApplied,
        notes: $('#notes').val() || '',
        _token: csrfToken,
      }),
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      success: function (response) {
        if (response.status === 200) {
          const total = parseFloat(response.total_amount || 0);
          const allocationOnly = response.allocation_only === true || total <= 0.01;

          $('#receiptReferenceNo').text(response.bulk_reference || 'N/A');
          $('#receiptTotalAmount').text(total.toFixed(2));

          if (allocationOnly) {
            $('#receiptModalTitle').html('<i class="fas fa-exchange-alt"></i> Allocation Successful');
            $('#receiptModalIcon').attr('class', 'fas fa-balance-scale fa-3x text-success mb-3');
            $('#receiptModalSubtitle').text('Return credit settlement completed');
            $('#receiptAmountLabel').text('Cash collected:');
            $('#receiptModalFootnote').text(
              'No payment row was created: return credit was applied to the sale. ' +
                'Sales and returns balances were updated; ledger for cash did not change.'
            );
            $('#receiptReferenceWrap, #receiptCopyWrap').hide();
          } else {
            $('#receiptModalTitle').html('<i class="fas fa-check-circle"></i> Payment Successful');
            $('#receiptModalIcon').attr('class', 'fas fa-receipt fa-3x text-success mb-3');
            $('#receiptModalSubtitle').text('Payment Reference Number');
            $('#receiptAmountLabel').text('Total Amount:');
            $('#receiptModalFootnote').text('Save this reference number for future payment tracking and verification.');
            $('#receiptReferenceWrap, #receiptCopyWrap').show();
          }

          $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
          $('#paymentReceiptModal').modal('show');

          if (response.message && window.toastr) {
            window.toastr.success(response.message, allocationOnly ? 'Allocation' : 'Payment', { timeOut: 6000 });
          }
        }
      },
      error: function (xhr) {
        const error = xhr.responseJSON;
        if (error && error.errors) {
          const firstKey = Object.keys(error.errors)[0];
          const msgs = error.errors[firstKey];
          if (window.toastr) window.toastr.error(Array.isArray(msgs) ? msgs[0] : String(msgs));
        } else {
          if (window.toastr) window.toastr.error(error?.message || 'Flexible payment submission failed');
        }
      },
      complete: function () {
        $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
      },
    });

    return false;
  }

  window.submitMultiMethodPayment = submitMultiMethodPayment;

  $(document).on('click', '.return-credit-badge', function (e) {
    e.stopPropagation();
    showAdjustCreditDialog($(this).data('sale-id'));
  });

  $(document).on('click', '.quick-remove-credit', function (e) {
    e.stopPropagation();
    const saleId = $(this).data('sale-id');
    const currentCredit = (window.billReturnCreditAllocations && window.billReturnCreditAllocations[saleId]) || 0;
    if (currentCredit > 0 && window.billReturnCreditAllocations) {
      delete window.billReturnCreditAllocations[saleId];
      if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
      if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();
      if (window.toastr) window.toastr.success(`Return credit Rs.${Number(currentCredit).toFixed(2)} removed from bill`, 'Credit Removed');
    }
  });

  $(document).on('click', '.quick-adjust-credit', function (e) {
    e.stopPropagation();
    showAdjustCreditDialog($(this).data('sale-id'));
  });

  $(document).on('click', '#reallocateAllCreditsBtn', function () {
    let totalReturnCredit = 0;
    $('.return-checkbox:checked').each(function () {
      const action = $('.return-action[data-return-id="' + $(this).data('return-id') + '"]').val();
      if (action === 'apply_to_sales') totalReturnCredit += parseFloat($(this).data('amount'));
    });
    if (totalReturnCredit === 0) {
      if (window.toastr) window.toastr.warning('No return credits selected for Apply to invoice', 'No credits');
      return;
    }

    const sales = window.availableCustomerSales || [];
    let billsHTML =
      '<div style="max-height: 400px; overflow-y: auto;"><table class="table table-sm table-hover"><thead class="sticky-top bg-light"><tr><th>Bill #</th><th>Due</th><th>Credit</th><th>Action</th></tr></thead><tbody>';
    sales.forEach((sale) => {
      const currentCredit = (window.billReturnCreditAllocations && window.billReturnCreditAllocations[sale.id]) || 0;
      billsHTML += `
        <tr>
          <td><small>${sale.invoice_no}</small></td>
          <td><small>Rs.${Number(sale.total_due).toFixed(2)}</small></td>
          <td><small class="${currentCredit > 0 ? 'text-info fw-bold' : 'text-muted'}">Rs.${Number(currentCredit).toFixed(2)}</small></td>
          <td>
            <button class="btn btn-xs btn-primary realloc-set-credit" data-sale-id="${sale.id}" data-invoice="${sale.invoice_no}" data-due="${sale.total_due}">
              <i class="fas fa-edit"></i>
            </button>
          </td>
        </tr>
      `;
    });
    billsHTML += '</tbody></table></div>';

    if (!window.Swal) return;
    window.Swal.fire({
      title: 'Reallocate Return Credits',
      html: `
        <div class="text-start">
          <div class="alert alert-info p-2 mb-2">
            <small><strong>Total Available:</strong> Rs.${Number(totalReturnCredit).toFixed(2)}</small>
          </div>
          ${billsHTML}
          <div class="mt-2 text-center">
            <button class="btn btn-sm btn-warning" id="clearAllAllocations">
              <i class="fas fa-eraser"></i> Clear All
            </button>
            <button class="btn btn-sm btn-success" id="autoFifoAllocate">
              <i class="fas fa-magic"></i> Auto FIFO
            </button>
          </div>
        </div>
      `,
      width: '600px',
      showCancelButton: true,
      showConfirmButton: false,
      cancelButtonText: 'Close',
      didOpen: () => {
        $('#clearAllAllocations')
          .off('click')
          .on('click', function () {
            window.billReturnCreditAllocations = {};
            updateReturnAppliedToHintsFromAllocations();
            if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
            if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();
            if (window.toastr) window.toastr.success('All credits cleared!', 'Success');
            window.Swal.close();
            setTimeout(() => $('#reallocateAllCreditsBtn').click(), 100);
          });

        $('#autoFifoAllocate')
          .off('click')
          .on('click', function () {
            autoAllocateReturnCreditsToSales(totalReturnCredit);
            if (window.toastr) window.toastr.success('FIFO allocation applied!', 'Success');
            window.Swal.close();
          });
      },
    });

    $(document)
      .off('click', '.realloc-set-credit')
      .on('click', '.realloc-set-credit', function () {
        const saleId = $(this).data('sale-id');
        const invoice = $(this).data('invoice');
        const due = parseFloat($(this).data('due'));
        const currentCredit = (window.billReturnCreditAllocations && window.billReturnCreditAllocations[saleId]) || 0;

        let allocated = 0;
        if (window.billReturnCreditAllocations) {
          Object.keys(window.billReturnCreditAllocations).forEach((key) => {
            if (key != saleId) allocated += window.billReturnCreditAllocations[key];
          });
        }
        const available = totalReturnCredit - allocated;
        const maxAllowable = Math.min(available, due);

        window.Swal.fire({
          title: `Set Credit for ${invoice}`,
          html: `
            <div class="text-start">
              <p><small><strong>Current:</strong> Rs.${Number(currentCredit).toFixed(2)}</small></p>
              <p><small><strong>Total Return Credit:</strong> Rs.${Number(totalReturnCredit).toFixed(2)}</small></p>
              <p><small><strong>Available:</strong> Rs.${Number(available).toFixed(2)}</small></p>
              <p><small><strong>Max Allowable:</strong> Rs.${Number(maxAllowable).toFixed(2)}</small></p>
              <input type="number" id="setCreditAmount" class="form-control" value="${currentCredit}" min="0" max="${maxAllowable}" step="0.01">
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Set',
          preConfirm: () => {
            const amount = parseFloat($('#setCreditAmount').val()) || 0;
            if (amount < 0 || amount > maxAllowable) {
              window.Swal.showValidationMessage(`Between 0 and ${maxAllowable.toFixed(2)}`);
              return false;
            }
            return amount;
          },
        }).then((result) => {
          if (result.isConfirmed) {
            if (!window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};
            if (result.value > 0) window.billReturnCreditAllocations[saleId] = result.value;
            else delete window.billReturnCreditAllocations[saleId];

            if (typeof window.populateFlexibleBillsList === 'function') window.populateFlexibleBillsList();
            if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function') window.updateExistingBillAllocationsForReturnCredits();
            if (window.toastr) window.toastr.success('Credit updated!', 'Success');
            $('#reallocateAllCreditsBtn').click();
          }
        });
      });
  });
});

