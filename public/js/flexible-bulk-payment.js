// Enhanced Flexible Multi-Method Bulk Payment JavaScript
// Tamil: பல payment methods-ல ஒரே பொழுதில் bulk payment பண்ண

class FlexibleBulkPayment {
    constructor() {
        this.paymentGroups = [];
        this.availableSales = [];
        this.currentCustomer = null;
        this.groupCounter = 0;
    }

    // Initialize the flexible payment system
    init() {
        // Existing bulk payment button click
        $('#bulkPaymentBtn').click(() => {
            this.showFlexiblePaymentModal();
        });

        // Customer selection change
        $(document).on('change', '#flexibleCustomerSelect', (e) => {
            this.loadCustomerSales($(e.target).val());
        });

        // Add payment group
        $(document).on('click', '#addPaymentGroup', () => {
            this.addPaymentGroup();
        });

        // Remove payment group
        $(document).on('click', '.remove-payment-group', (e) => {
            this.removePaymentGroup($(e.target).closest('.payment-group'));
        });

        // Payment method change
        $(document).on('change', '.group-payment-method', (e) => {
            this.handleMethodChange($(e.target));
        });

        // Add bill to group
        $(document).on('click', '.add-bill-to-group', (e) => {
            this.addBillToGroup($(e.target).closest('.payment-group'));
        });

        // Remove bill from group
        $(document).on('click', '.remove-bill', (e) => {
            $(e.target).closest('.bill-row').remove();
            this.calculateGroupTotals();
        });

        // Amount change calculation
        $(document).on('input', '.bill-amount', () => {
            this.calculateGroupTotals();
        });

        // Submit flexible payment
        $(document).on('click', '#submitFlexiblePayment', () => {
            this.submitFlexiblePayment();
        });
    }

    // Show flexible payment modal
    showFlexiblePaymentModal() {
        // Create enhanced modal if not exists
        if ($('#flexibleBulkPaymentModal').length === 0) {
            this.createFlexibleModal();
        }
        
        $('#flexibleBulkPaymentModal').modal('show');
        this.loadCustomers();
    }

    // Create flexible bulk payment modal
    createFlexibleModal() {
        const modalHtml = `
            <div class="modal fade" id="flexibleBulkPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Flexible Multi-Method Bulk Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Customer Selection -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="flexibleCustomerSelect" class="form-label">Select Customer</label>
                                    <select id="flexibleCustomerSelect" class="form-control">
                                        <option value="">Select Customer</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="flexiblePaymentDate" class="form-label">Payment Date</label>
                                    <input type="date" id="flexiblePaymentDate" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                                </div>
                            </div>

                            <!-- Customer Summary Cards -->
                            <div class="row mb-4" id="customerSummaryCards" style="display:none;">
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6>Opening Balance</h6>
                                            <h4 id="openingBalanceAmount">Rs. 0.00</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6>Total Sales</h6>
                                            <h4 id="totalSalesAmount">Rs. 0.00</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6>Total Due</h6>
                                            <h4 id="totalDueAmount">Rs. 0.00</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6>Available Bills</h6>
                                            <h4 id="availableBillsCount">0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Groups Container -->
                            <div id="paymentGroupsContainer">
                                <!-- Dynamic payment groups will be added here -->
                            </div>

                            <!-- Add Payment Group Button -->
                            <div class="text-center mb-4">
                                <button type="button" id="addPaymentGroup" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Add Payment Method Group
                                </button>
                            </div>

                            <!-- Total Summary -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <h6>Total Groups: <span id="totalGroups">0</span></h6>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6>Total Bills: <span id="totalBills">0</span></h6>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6>Total Amount: <span id="grandTotalAmount">Rs. 0.00</span></h6>
                                                </div>
                                                <div class="col-md-3">
                                                    <h6>Remaining Due: <span id="remainingDueAmount">Rs. 0.00</span></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <label for="flexiblePaymentNotes" class="form-label">Payment Notes</label>
                                    <textarea id="flexiblePaymentNotes" class="form-control" rows="3" placeholder="Optional notes for this payment..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="submitFlexiblePayment" class="btn btn-success">
                                <i class="fas fa-credit-card"></i> Process Multi-Method Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    }

    // Load customers
    async loadCustomers() {
        try {
            const response = await $.ajax({
                url: '/customer-get-all',
                method: 'GET'
            });

            if (response.status === 200) {
                const customers = response.data;
                let options = '<option value="">Select Customer</option>';
                
                customers.forEach(customer => {
                    const customerName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim() || customer.business_name || 'Unnamed Customer';
                    options += `<option value="${customer.id}">${customerName} - ${customer.mobile || 'No Mobile'}</option>`;
                });
                
                $('#flexibleCustomerSelect').html(options);
            }
        } catch (error) {
            console.error('Failed to load customers:', error);
            toastr.error('Failed to load customers');
        }
    }

    // Load customer's due sales
    async loadCustomerSales(customerId) {
        if (!customerId) {
            $('#customerSummaryCards').hide();
            $('#paymentGroupsContainer').empty();
            this.availableSales = [];
            return;
        }

        try {
            const response = await $.ajax({
                url: `/customer-get-by-id/${customerId}`,
                method: 'GET'
            });

            if (response.status === 200) {
                this.currentCustomer = response.data;
                
                // Show customer summary
                this.displayCustomerSummary();
                
                // Load due sales
                await this.loadDueSales(customerId);
                
                // Add first payment group
                this.addPaymentGroup();
            }
        } catch (error) {
            console.error('Failed to load customer data:', error);
            toastr.error('Failed to load customer data');
        }
    }

    // Display customer summary
    displayCustomerSummary() {
        if (!this.currentCustomer) return;

        $('#openingBalanceAmount').text(`Rs. ${parseFloat(this.currentCustomer.opening_balance || 0).toFixed(2)}`);
        $('#customerSummaryCards').show();
    }

    // Load due sales for customer
    async loadDueSales(customerId) {
        try {
            const response = await $.ajax({
                url: '/sales/paginated',
                method: 'GET',
                data: {
                    customer_id: customerId,
                    payment_status: 'due,partial'
                }
            });

            if (response.data && response.data.data) {
                this.availableSales = response.data.data.filter(sale => sale.total_due > 0);
                
                // Update summary
                const totalSales = this.availableSales.reduce((sum, sale) => sum + parseFloat(sale.final_total || 0), 0);
                const totalDue = this.availableSales.reduce((sum, sale) => sum + parseFloat(sale.total_due || 0), 0);
                
                $('#totalSalesAmount').text(`Rs. ${totalSales.toFixed(2)}`);
                $('#totalDueAmount').text(`Rs. ${totalDue.toFixed(2)}`);
                $('#availableBillsCount').text(this.availableSales.length);
            }
        } catch (error) {
            console.error('Failed to load due sales:', error);
            toastr.error('Failed to load due sales');
        }
    }

    // Add payment group
    addPaymentGroup() {
        const groupIndex = this.groupCounter++;
        const salesOptions = this.availableSales.map(sale => 
            `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${sale.invoice_no}">
                ${sale.invoice_no} - Due: Rs.${parseFloat(sale.total_due).toFixed(2)}
            </option>`
        ).join('');

        const groupHtml = `
            <div class="payment-group card mb-3" data-group-index="${groupIndex}">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label mb-0">Payment Method</label>
                            <select class="form-control group-payment-method" name="payment_groups[${groupIndex}][method]" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6 method-fields">
                            <!-- Method-specific fields will appear here -->
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-payment-group">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Bills for this Payment Method:</h6>
                    <div class="bills-container">
                        <div class="bill-row row mb-2">
                            <div class="col-md-6">
                                <select class="form-control bill-select" name="payment_groups[${groupIndex}][bills][0][sale_id]" required>
                                    <option value="">Select Bill</option>
                                    ${salesOptions}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control bill-amount" 
                                       name="payment_groups[${groupIndex}][bills][0][amount]" 
                                       step="0.01" min="0.01" placeholder="Amount" required>
                                <small class="text-muted">Due: Rs. <span class="due-display">0.00</span></small>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success btn-sm add-bill-to-group">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="group-summary mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Group Total: Rs. <span class="group-total-amount">0.00</span></strong>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Bills in Group: <span class="group-bills-count">0</span></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#paymentGroupsContainer').append(groupHtml);
        
        // Update bill dropdown change handler
        this.bindBillSelectEvents(groupIndex);
        
        this.calculateGroupTotals();
    }

    // Bind bill select events
    bindBillSelectEvents(groupIndex) {
        const $group = $(`.payment-group[data-group-index="${groupIndex}"]`);
        
        $group.on('change', '.bill-select', function() {
            const selectedOption = $(this).find('option:selected');
            const dueAmount = selectedOption.data('due') || 0;
            const $row = $(this).closest('.bill-row');
            
            $row.find('.due-display').text(parseFloat(dueAmount).toFixed(2));
            $row.find('.bill-amount').attr('max', dueAmount).val('');
        });
    }

    // Handle payment method change
    handleMethodChange($select) {
        const method = $select.val();
        const $group = $select.closest('.payment-group');
        const $fieldsContainer = $group.find('.method-fields');
        const groupIndex = $group.data('group-index');

        let fieldsHtml = '';

        switch (method) {
            case 'cheque':
                fieldsHtml = `
                    <label class="form-label mb-1">Cheque Details</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="payment_groups[${groupIndex}][cheque_number]" 
                                   class="form-control form-control-sm mb-1" placeholder="Cheque Number" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="payment_groups[${groupIndex}][cheque_bank_branch]" 
                                   class="form-control form-control-sm mb-1" placeholder="Bank & Branch" required>
                        </div>
                    </div>
                    <input type="date" name="payment_groups[${groupIndex}][cheque_valid_date]" 
                           class="form-control form-control-sm" title="Valid Date" required>
                `;
                break;

            case 'card':
                fieldsHtml = `
                    <label class="form-label mb-1">Card Details</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="payment_groups[${groupIndex}][card_number]" 
                                   class="form-control form-control-sm mb-1" placeholder="Card Number" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="payment_groups[${groupIndex}][card_holder_name]" 
                                   class="form-control form-control-sm" placeholder="Card Holder Name">
                        </div>
                    </div>
                `;
                break;

            case 'bank_transfer':
                fieldsHtml = `
                    <label class="form-label mb-1">Bank Transfer</label>
                    <input type="text" name="payment_groups[${groupIndex}][bank_account_number]" 
                           class="form-control form-control-sm" placeholder="Account Number" required>
                `;
                break;

            default:
                fieldsHtml = '<span class="text-muted">No additional details required for Cash</span>';
        }

        $fieldsContainer.html(fieldsHtml);
    }

    // Add bill to group
    addBillToGroup($group) {
        const groupIndex = $group.data('group-index');
        const billIndex = $group.find('.bill-row').length;
        const salesOptions = this.availableSales.map(sale => 
            `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${sale.invoice_no}">
                ${sale.invoice_no} - Due: Rs.${parseFloat(sale.total_due).toFixed(2)}
            </option>`
        ).join('');

        const billHtml = `
            <div class="bill-row row mb-2">
                <div class="col-md-6">
                    <select class="form-control bill-select" name="payment_groups[${groupIndex}][bills][${billIndex}][sale_id]" required>
                        <option value="">Select Bill</option>
                        ${salesOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control bill-amount" 
                           name="payment_groups[${groupIndex}][bills][${billIndex}][amount]" 
                           step="0.01" min="0.01" placeholder="Amount" required>
                    <small class="text-muted">Due: Rs. <span class="due-display">0.00</span></small>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-bill">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        $group.find('.bills-container').append(billHtml);
        this.bindBillSelectEvents(groupIndex);
        this.calculateGroupTotals();
    }

    // Remove payment group
    removePaymentGroup($group) {
        $group.remove();
        this.calculateGroupTotals();
    }

    // Calculate group totals and grand total
    calculateGroupTotals() {
        let grandTotal = 0;
        let totalGroups = 0;
        let totalBills = 0;

        $('.payment-group').each(function() {
            const $group = $(this);
            let groupTotal = 0;
            let groupBillsCount = 0;

            $group.find('.bill-amount').each(function() {
                const amount = parseFloat($(this).val()) || 0;
                if (amount > 0) {
                    groupTotal += amount;
                    groupBillsCount++;
                }
            });

            $group.find('.group-total-amount').text(groupTotal.toFixed(2));
            $group.find('.group-bills-count').text(groupBillsCount);

            if (groupTotal > 0) {
                grandTotal += groupTotal;
                totalGroups++;
                totalBills += groupBillsCount;
            }
        });

        // Update summary
        $('#totalGroups').text(totalGroups);
        $('#totalBills').text(totalBills);
        $('#grandTotalAmount').text(`Rs. ${grandTotal.toFixed(2)}`);

        // Calculate remaining due
        const totalCustomerDue = this.availableSales.reduce((sum, sale) => sum + parseFloat(sale.total_due), 0);
        const remainingDue = Math.max(0, totalCustomerDue - grandTotal);
        $('#remainingDueAmount').text(`Rs. ${remainingDue.toFixed(2)}`);
    }

    // Submit flexible payment
    async submitFlexiblePayment() {
        const customerId = $('#flexibleCustomerSelect').val();
        const paymentDate = $('#flexiblePaymentDate').val();
        const notes = $('#flexiblePaymentNotes').val();

        if (!customerId) {
            toastr.error('Please select a customer');
            return;
        }

        // Collect payment groups
        const paymentGroups = [];
        let hasValidGroups = false;

        $('.payment-group').each(function() {
            const $group = $(this);
            const method = $group.find('.group-payment-method').val();
            
            if (!method) return;

            const groupData = {
                method: method,
                bills: []
            };

            // Add method-specific data
            if (method === 'cheque') {
                groupData.cheque_number = $group.find('[name*="[cheque_number]"]').val();
                groupData.cheque_bank_branch = $group.find('[name*="[cheque_bank_branch]"]').val();
                groupData.cheque_valid_date = $group.find('[name*="[cheque_valid_date]"]').val();
                
                if (!groupData.cheque_number || !groupData.cheque_bank_branch || !groupData.cheque_valid_date) {
                    toastr.error('Please fill all cheque details');
                    return false;
                }
            } else if (method === 'card') {
                groupData.card_number = $group.find('[name*="[card_number]"]').val();
                groupData.card_holder_name = $group.find('[name*="[card_holder_name]"]').val();
                
                if (!groupData.card_number) {
                    toastr.error('Please enter card number');
                    return false;
                }
            } else if (method === 'bank_transfer') {
                groupData.bank_account_number = $group.find('[name*="[bank_account_number]"]').val();
                
                if (!groupData.bank_account_number) {
                    toastr.error('Please enter bank account number');
                    return false;
                }
            }

            // Collect bills
            $group.find('.bill-row').each(function() {
                const $row = $(this);
                const saleId = $row.find('.bill-select').val();
                const amount = parseFloat($row.find('.bill-amount').val()) || 0;

                if (saleId && amount > 0) {
                    groupData.bills.push({
                        sale_id: parseInt(saleId),
                        amount: amount
                    });
                }
            });

            if (groupData.bills.length > 0) {
                paymentGroups.push(groupData);
                hasValidGroups = true;
            }
        });

        if (!hasValidGroups) {
            toastr.error('Please add at least one payment group with valid bills');
            return;
        }

        // Show loading
        $('#submitFlexiblePayment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        try {
            const response = await $.ajax({
                url: '/submit-flexible-bulk-payment',
                method: 'POST',
                data: {
                    customer_id: customerId,
                    payment_date: paymentDate,
                    payment_groups: paymentGroups,
                    notes: notes,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (response.status === 200) {
                toastr.success(response.message);
                $('#flexibleBulkPaymentModal').modal('hide');
                
                // Refresh the sales table
                if (typeof salesTable !== 'undefined') {
                    salesTable.ajax.reload();
                }
                
                // Reset form
                this.resetForm();
                
                // Show success details
                setTimeout(() => {
                    toastr.info(`Bulk Reference: ${response.bulk_reference}<br>Total Amount: Rs. ${response.total_amount}`, 'Payment Details', {
                        timeOut: 10000,
                        extendedTimeOut: 5000
                    });
                }, 1000);
            }

        } catch (xhr) {
            const error = xhr.responseJSON;
            toastr.error(error?.message || 'Multi-method payment failed');
            console.error('Payment error:', error);
        } finally {
            $('#submitFlexiblePayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Process Multi-Method Payment');
        }
    }

    // Reset form
    resetForm() {
        $('#flexibleCustomerSelect').val('');
        $('#flexiblePaymentDate').val(new Date().toISOString().split('T')[0]);
        $('#flexiblePaymentNotes').val('');
        $('#customerSummaryCards').hide();
        $('#paymentGroupsContainer').empty();
        
        this.availableSales = [];
        this.currentCustomer = null;
        this.groupCounter = 0;
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.flexibleBulkPayment = new FlexibleBulkPayment();
    window.flexibleBulkPayment.init();
    
    console.log('Enhanced Flexible Multi-Method Bulk Payment System Initialized');
});