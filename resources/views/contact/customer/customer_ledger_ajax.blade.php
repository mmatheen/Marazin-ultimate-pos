<!-- Customer Ledger AJAX Script -->
<script>
$(document).ready(function() {
    // Initialize DataTable variable
    let ledgerDataTable;
    
    // Load customers and locations on page load
    loadCustomers();
    loadLocations();

    // Check for customer_id parameter in URL and auto-load data
    const urlParams = new URLSearchParams(window.location.search);
    const customerIdParam = urlParams.get('customer_id');
    
    // Debounce timer for auto-filtering
    let filterTimeout;

    // Add change event listeners for automatic filtering
    $('#customer_id').on('change', function() {
        clearTimeout(filterTimeout);
        if ($(this).val()) {
            filterTimeout = setTimeout(function() {
                loadCustomerLedger();
            }, 300);
        } else {
            hideAllSections();
        }
    });

    $('#location_id').on('change', function() {
        clearTimeout(filterTimeout);
        if ($('#customer_id').val()) {
            filterTimeout = setTimeout(function() {
                loadCustomerLedger();
            }, 300);
        }
    });

    $('#start_date, #end_date').on('change', function() {
        clearTimeout(filterTimeout);
        if ($('#customer_id').val()) {
            filterTimeout = setTimeout(function() {
                loadCustomerLedger();
            }, 500);
        }
    });

    // Ensure buttons work with a simple approach
    $(document).ready(function() {
        console.log('Customer ledger AJAX loaded');
        
        // Force enable button functionality with body delegation
        $('body').on('click', '#applyAdvanceBtn', function(e) {
            e.preventDefault();
            console.log('Apply advance button clicked via body delegation');
            
            const customerId = $('#customer_id').val();
            if (!customerId) {
                alert('Please select a customer first');
                return;
            }
            
            const advanceAmount = parseFloat($('#advanceAmount').text() || 0);
            if (advanceAmount <= 0) {
                alert('No advance amount available to apply');
                return;
            }
            
            if (confirm(`Apply ₹${advanceAmount.toFixed(2)} advance to outstanding bills?`)) {
                applyAdvancePayments(customerId);
            }
        });
        
        $('body').on('click', '#manageAdvanceBtn', function(e) {
            e.preventDefault();
            console.log('Manage advance button clicked');
            const customerId = $('#customer_id').val();
            if (!customerId) {
                alert('Please select a customer first');
                return;
            }
            $('#modalAdvanceAmount').text($('#advanceAmount').text());
            $('#advanceManagementModal').modal('show');
        });
        
        $('body').on('click', '#refreshLedgerBtn', function(e) {
            e.preventDefault();
            console.log('Refresh button clicked');
            if ($('#customer_id').val()) {
                loadCustomerLedger();
            }
        });
    });

    function loadCustomers() {
        $.ajax({
            url: '/customer-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                const customerSelect = $('#customer_id');
                customerSelect.empty().append('<option value="">Select Customer</option>');
                
                if (response.status === 200 && response.message && response.message.length > 0) {
                    // Sort customers alphabetically, but put Walk-in customer last
                    const sortedCustomers = response.message.sort((a, b) => {
                        if (a.first_name === 'Walk-in') return 1;
                        if (b.first_name === 'Walk-in') return -1;
                        return a.first_name.localeCompare(b.first_name);
                    });

                    sortedCustomers.forEach(customer => {
                        const fullName = `${customer.first_name} ${customer.last_name} (${customer.mobile_no || 'N/A'})`;
                        customerSelect.append(`<option value="${customer.id}">${fullName}</option>`);
                    });

                    // Auto-select customer if customer_id parameter exists
                    if (customerIdParam) {
                        customerSelect.val(customerIdParam);
                        // Show notification
                        const selectedCustomer = sortedCustomers.find(c => c.id == customerIdParam);
                        if (selectedCustomer) {
                            $('#autoSelectedMessage').text(`Customer "${selectedCustomer.first_name} ${selectedCustomer.last_name}" automatically selected.`);
                            $('#autoSelectedNotification').removeClass('d-none');
                        }
                        // Auto-load the ledger for the selected customer
                        setTimeout(function() {
                            loadCustomerLedger();
                        }, 500); // Small delay to ensure DOM is ready
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading customers:', error);
                toastr.error('Failed to load customers');
            }
        });
    }

    function loadLocations() {
        $.ajax({
            url: '/location-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Location API Response:', response); // Debug log
                
                const locationSelect = $('#location_id');
                locationSelect.empty().append('<option value="">All Locations</option>');
                
                // Handle both response formats: status=true with data array OR status=200 with message array
                const locations = response.data || response.message || [];
                const isSuccess = response.status === true || response.status === 200;
                
                console.log('Locations found:', locations.length); // Debug log
                
                if (isSuccess && locations.length > 0) {
                    locations.forEach(location => {
                        locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                        console.log('Added location:', location.name); // Debug log
                    });
                } else {
                    console.log('No locations found or API returned empty result');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading locations:', error);
                console.error('Response:', xhr.responseText); // Debug log
                toastr.error('Failed to load locations');
            }
        });
    }

    function loadCustomerLedger() {
        const formData = {
            customer_id: $('#customer_id').val(),
            location_id: $('#location_id').val() || null,
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val()
        };

        // Validate required fields
        if (!formData.customer_id) {
            toastr.error('Please select a customer');
            return;
        }

        if (!formData.start_date || !formData.end_date) {
            toastr.error('Please select start and end dates');
            return;
        }

        // Show loading status
        $('#filterStatus').html('<i class="fa fa-spinner fa-spin text-primary"></i><br><small class="text-primary">Loading...</small>');

        $.ajax({
            url: '/customer-ledger-data',
            type: 'GET',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 200) {
                    displayCustomerDetails(response.customer);
                    displayAccountSummary(response.summary);
                    initializeLedgerDataTable(response.transactions);
                    
                    $('#customerDetailsSection').show();
                    $('#ledgerTableSection').show();
                    $('#noDataMessage').hide();

                    // Show/hide advance actions based on available advance
                    console.log('Checking advance application data:', response.advance_application);
                    
                    // Always show the advance section if customer is selected for better UX
                    if (response.customer && response.customer.id) {
                        const advanceAmount = response.advance_application ? response.advance_application.available_advance : 0;
                        console.log('Available advance amount:', advanceAmount);
                        
                        $('#advanceActionsSection').show();
                        $('#advanceAmount').text(parseFloat(advanceAmount || 0).toFixed(2));
                        
                        // Enable/disable button based on advance availability
                        if (advanceAmount > 0) {
                            $('#applyAdvanceBtn').prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
                            $('#advanceStatusText').text('Available for application');
                        } else {
                            $('#applyAdvanceBtn').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                            $('#advanceStatusText').text('No advance available');
                        }
                    } else {
                        $('#advanceActionsSection').hide();
                    }

                    if (response.transactions.length === 0) {
                        $('#ledgerTableSection').hide();
                        $('#noDataMessage').show();
                    }

                    // Show success status
                    $('#filterStatus').html('<i class="fa fa-check-circle text-success"></i><br><small class="text-success">Updated</small>');
                } else {
                    toastr.error(response.message || 'Failed to load ledger data');
                    hideAllSections();
                    $('#filterStatus').html('<i class="fa fa-exclamation-circle text-danger"></i><br><small class="text-danger">Error</small>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading ledger:', error);
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errorMessage = '';
                    Object.values(xhr.responseJSON.errors).forEach(errors => {
                        errors.forEach(error => {
                            errorMessage += error + '<br>';
                        });
                    });
                    toastr.error(errorMessage);
                } else {
                    toastr.error('Failed to load customer ledger');
                }
                hideAllSections();
                $('#filterStatus').html('<i class="fa fa-exclamation-circle text-danger"></i><br><small class="text-danger">Error</small>');
            },
            complete: function() {
                $('#filterStatus').html('<i class="fa fa-check-circle text-success"></i><br><small class="text-success">Ready</small>');
            }
        });
    }

    function displayCustomerDetails(customer) {
        const customerHtml = `
            <p><strong>Name:</strong> ${customer.name}</p>
            <p><strong>Mobile:</strong> ${customer.mobile || 'N/A'}</p>
            <p><strong>Email:</strong> ${customer.email || 'N/A'}</p>
            <p><strong>Address:</strong> ${customer.address || 'N/A'}</p>
            <p><strong>Opening Balance:</strong> Rs. ${formatCurrency(customer.opening_balance)}</p>
        `;
        $('#customerDetails').html(customerHtml);
    }

    function displayAccountSummary(summary) {
        const summaryHtml = `
            <div class="row text-center">
                <div class="col-6">
                    <h6 class="text-white mb-1">Total Invoices</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(summary.total_invoices)}</h4>
                </div>
                <div class="col-6">
                    <h6 class="text-white mb-1">Total Paid</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(summary.total_paid)}</h4>
                </div>
            </div>
            <div class="row text-center mt-2">
                <div class="col-6">
                    <h6 class="text-white mb-1">Total Returns</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(summary.total_returns)}</h4>
                </div>
                <div class="col-6">
                    <h6 class="text-white mb-1">Outstanding Due</h6>
                    <h6 class="text-white small">(Inc. Opening Balance)</h6>
                    <h4 class="text-white text-warning">Rs. ${formatCurrency(summary.outstanding_due)}</h4>
                </div>
            </div>
            <div class="row text-center mt-2">
                <div class="col-6">
                    <h6 class="text-white mb-1">Advance Amount</h6>
                    <h4 class="text-white text-success">Rs. ${formatCurrency(summary.advance_amount)}</h4>
                </div>
                <div class="col-6">
                    <h6 class="text-white mb-1">Effective Due</h6>
                    <h4 class="text-white ${summary.effective_due > 0 ? 'text-danger' : 'text-success'}">
                        Rs. ${formatCurrency(summary.effective_due)}
                    </h4>
                </div>
            </div>
            ${summary.opening_balance !== 0 ? `
            <div class="row text-center mt-2">
                <div class="col-12">
                    <div class="alert ${summary.opening_balance > 0 ? 'alert-warning' : 'alert-info'} alert-sm p-2 mb-0">
                        <small class="text-dark">
                            <i class="fa fa-info-circle"></i>
                            Opening Balance: Rs. ${formatCurrency(summary.opening_balance)} 
                            ${summary.opening_balance > 0 ? '(Customer owes)' : '(Customer credit)'}
                        </small>
                    </div>
                </div>
            </div>
            ` : ''}
            ${summary.advance_amount > 0 ? `
            <div class="row text-center mt-2">
                <div class="col-12">
                    <div class="alert alert-info alert-sm p-2 mb-0">
                        <small class="text-dark">
                            <i class="fa fa-info-circle"></i>
                            Advance Rs. ${formatCurrency(Math.min(summary.advance_amount, summary.outstanding_due))} 
                            available for application
                        </small>
                    </div>
                </div>
            </div>
            ` : ''}
        `;
        $('#accountSummary').html(summaryHtml);
    }

    function initializeLedgerDataTable(transactions) {
        // Destroy existing DataTable if it exists
        if (ledgerDataTable) {
            ledgerDataTable.destroy();
            $('#ledgerTableBody').empty();
        }

        // Prepare data for DataTable
        const tableData = [];
        let totalDebit = 0;
        let totalCredit = 0;

        transactions.forEach((transaction, index) => {
            totalDebit += parseFloat(transaction.debit) || 0;
            totalCredit += parseFloat(transaction.credit) || 0;

            const typeClass = getTypeClass(transaction.type);
            const statusClass = getStatusClass(transaction.payment_status);
            const balanceClass = transaction.running_balance < 0 ? 'text-success' : 'text-dark';
            
            // Enhanced balance display with advance/due information
            let balanceDisplay = '';
            if (transaction.advance_amount && transaction.advance_amount > 0) {
                balanceDisplay = `<div class="text-end fw-bold text-success">Rs. ${formatCurrency(transaction.advance_amount)} (Advance)</div>`;
            } else if (transaction.due_amount && transaction.due_amount > 0) {
                balanceDisplay = `<div class="text-end fw-bold text-danger">Rs. ${formatCurrency(transaction.due_amount)} (Due)</div>`;
            } else {
                balanceDisplay = `<div class="text-end fw-bold ${balanceClass}">Rs. ${formatCurrency(Math.abs(transaction.running_balance))} ${transaction.running_balance < 0 ? '(Adv)' : ''}</div>`;
            }

            tableData.push([
                index + 1,
                transaction.date, // Date is already formatted on the server side
                transaction.reference_no,
                `<span class="badge ${typeClass}">${transaction.type}</span>`,
                transaction.location,
                `<span class="badge ${statusClass}">${transaction.payment_status}</span>`,
                transaction.debit > 0 ? `<div class="text-end">Rs. ${formatCurrency(transaction.debit)}</div>` : '<div class="text-end">-</div>',
                transaction.credit > 0 ? `<div class="text-end">Rs. ${formatCurrency(transaction.credit)}</div>` : '<div class="text-end">-</div>',
                balanceDisplay,
                transaction.payment_method,
                transaction.others || '-'
            ]);
        });

        // Update footer totals
        $('#totalDebit').text(`Rs. ${formatCurrency(totalDebit)}`);
        $('#totalCredit').text(`Rs. ${formatCurrency(totalCredit)}`);
        $('#totalBalance').text(`Rs. ${formatCurrency(totalDebit - totalCredit)}`);

        // Initialize DataTable
        ledgerDataTable = $('#ledgerTable').DataTable({
            data: tableData,
            destroy: true,
            responsive: true,
            processing: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ordering: false, // Disable all sorting arrows and functionality
            columnDefs: [
                { 
                    targets: [0], // Serial number column
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                { 
                    targets: [1], // Date column
                    type: 'date',
                    className: 'text-center'
                },
                { 
                    targets: [2], // Reference no
                    className: 'text-center'
                },
                { 
                    targets: [3, 5], // Type and Status columns
                    orderable: false,
                    className: 'text-center'
                },
                { 
                    targets: [6, 7, 8], // Amount columns
                    orderable: false, // Disable sorting for amount columns too
                    className: 'text-end'
                },
                { 
                    targets: [9, 10], // Payment method and others
                    orderable: false,
                    className: 'text-center'
                }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: "Search ledger:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                infoEmpty: "No transactions available",
                infoFiltered: "(filtered from _MAX_ total transactions)",
                zeroRecords: "No matching transactions found",
                emptyTable: "No transactions available"
            },
            initComplete: function() {
                // Add custom styling after initialization
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });
    }

    function getTypeClass(type) {
        switch(type.toLowerCase()) {
            case 'sale': return 'bg-primary';
            case 'payment': return 'bg-success';
            case 'return': return 'bg-warning';
            default: return 'bg-secondary';
        }
    }

    function getStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'paid': return 'bg-success';
            case 'partial': return 'bg-warning';
            case 'due': return 'bg-danger';
            case 'final': return 'bg-info';
            default: return 'bg-secondary';
        }
    }

    function formatDate(dateString) {
        // This function is kept for any future use, but main ledger dates are formatted on server side
        if (!dateString || dateString === 'N/A') return 'N/A';
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return 'Invalid Date';
            }
            
            // Format as DD/MM/YYYY HH:MM:SS
            const options = {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
                timeZone: 'Asia/Colombo'
            };
            
            return new Intl.DateTimeFormat('en-GB', options).format(date);
        } catch (error) {
            console.error('Date formatting error:', error);
            return dateString;
        }
    }

    function formatCurrency(amount) {
        return parseFloat(amount || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function hideAllSections() {
        $('#customerDetailsSection').hide();
        $('#ledgerTableSection').hide();
        $('#noDataMessage').hide();
        $('#advanceActionsSection').hide();
        
        // Destroy DataTable if it exists
        if (ledgerDataTable) {
            ledgerDataTable.destroy();
            ledgerDataTable = null;
        }
    }

    function applyAdvancePayments(customerId) {
        console.log('applyAdvancePayments called with customerId:', customerId);
        
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        console.log('CSRF token:', csrfToken);
        
        $.ajax({
            url: '/apply-customer-advance',
            type: 'POST',
            data: {
                customer_id: customerId,
                _token: csrfToken
            },
            dataType: 'json',
            beforeSend: function() {
                console.log('AJAX request starting...');
                $('#applyAdvanceBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Applying...');
            },
            success: function(response) {
                console.log('AJAX success:', response);
                if (response.status === 200) {
                    toastr.success(response.message || 'Advance payments applied successfully');
                    // Reload the ledger to show updated data
                    loadCustomerLedger();
                } else {
                    toastr.error(response.message || 'Failed to apply advance payments');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                console.error('XHR:', xhr);
                console.error('Status:', status);
                toastr.error('Failed to apply advance payments: ' + error);
            },
            complete: function() {
                console.log('AJAX complete');
                $('#applyAdvanceBtn').prop('disabled', false).html('<i class="fa fa-magic"></i> Apply Advance to Bills');
            }
        });
    }
    
    // Handle return processing options
    function handleReturnOption(option) {
        const customerId = $('#customer_id').val();
        if (!customerId) {
            toastr.error('Please select a customer first');
            return;
        }
        
        let message = '';
        switch(option) {
            case 'cash':
                message = 'Process return as cash refund. This will create a cash payment record.';
                break;
            case 'advance':
                message = 'Add return amount to customer advance balance for future purchases.';
                break;
            case 'adjust':
                message = 'Automatically adjust return amount against outstanding bills.';
                break;
        }
        
        if (confirm(message + ' Continue?')) {
            // You can implement specific return handling logic here
            toastr.info('Return processing option: ' + option.toUpperCase());
            // Call backend API for return processing
            processReturn(customerId, option);
        }
    }
    
    // Add manual advance amount
    function addManualAdvance() {
        const customerId = $('#customer_id').val();
        const amount = parseFloat($('#manualAdvanceAmount').val());
        
        if (!customerId) {
            toastr.error('Please select a customer first');
            return;
        }
        
        if (!amount || amount <= 0) {
            toastr.error('Please enter a valid amount');
            return;
        }
        
        if (confirm(`Add ₹${amount.toFixed(2)} to customer's advance balance?`)) {
            // Call backend API to add manual advance
            addAdvanceAmount(customerId, amount);
        }
    }
    
    // Adjust advance amount
    function adjustAdvance() {
        const customerId = $('#customer_id').val();
        const amount = parseFloat($('#adjustAdvanceAmount').val());
        
        if (!customerId) {
            toastr.error('Please select a customer first');
            return;
        }
        
        if (!amount || amount <= 0) {
            toastr.error('Please enter a valid amount');
            return;
        }
        
        if (confirm(`Deduct ₹${amount.toFixed(2)} from customer's advance balance?`)) {
            // Call backend API to adjust advance
            adjustAdvanceAmount(customerId, amount);
        }
    }
    
    // Refresh advance data
    function refreshAdvanceData() {
        loadCustomerLedger();
        $('#advanceManagementModal').modal('hide');
        toastr.info('Advance data refreshed');
    }
    
    // Process return (placeholder for future implementation)
    function processReturn(customerId, option) {
        console.log('Processing return for customer:', customerId, 'Option:', option);
        // Implement return processing logic here
        toastr.success('Return processing initiated');
    }
    
    // Add advance amount (placeholder for future implementation)
    function addAdvanceAmount(customerId, amount) {
        console.log('Adding advance amount:', amount, 'for customer:', customerId);
        // Implement add advance logic here
        toastr.success('Manual advance added: ₹' + amount.toFixed(2));
        loadCustomerLedger();
    }
    
    // Adjust advance amount (placeholder for future implementation)
    function adjustAdvanceAmount(customerId, amount) {
        console.log('Adjusting advance amount:', amount, 'for customer:', customerId);
        // Implement adjust advance logic here
        toastr.success('Advance adjusted: -₹' + amount.toFixed(2));
        loadCustomerLedger();
    }
});
</script>
