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
                const locationSelect = $('#location_id');
                locationSelect.empty().append('<option value="">All Locations</option>');
                
                if (response.status === 200 && response.message && response.message.length > 0) {
                    response.message.forEach(location => {
                        locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading locations:', error);
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
            <div class="row text-center mt-3">
                <div class="col-6">
                    <h6 class="text-white mb-1">Total Returns</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(summary.total_returns)}</h4>
                </div>
                <div class="col-6">
                    <h6 class="text-white mb-1">Balance Due</h6>
                    <h4 class="text-white ${summary.balance_due < 0 ? 'text-success' : 'text-warning'}">
                        Rs. ${formatCurrency(Math.abs(summary.balance_due))} ${summary.balance_due < 0 ? '(Advance)' : ''}
                    </h4>
                </div>
            </div>
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

            tableData.push([
                index + 1,
                formatDate(transaction.date),
                transaction.reference_no,
                `<span class="badge ${typeClass}">${transaction.type}</span>`,
                transaction.location,
                `<span class="badge ${statusClass}">${transaction.payment_status}</span>`,
                transaction.debit > 0 ? `<div class="text-end">Rs. ${formatCurrency(transaction.debit)}</div>` : '<div class="text-end">-</div>',
                transaction.credit > 0 ? `<div class="text-end">Rs. ${formatCurrency(transaction.credit)}</div>` : '<div class="text-end">-</div>',
                `<div class="text-end fw-bold ${balanceClass}">Rs. ${formatCurrency(Math.abs(transaction.running_balance))} ${transaction.running_balance < 0 ? '(Adv)' : ''}</div>`,
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
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[1, 'desc']], // Sort by date descending
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
                    orderable: true,
                    className: 'text-end'
                },
                { 
                    targets: [9, 10], // Payment method and others
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
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
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
        
        // Destroy DataTable if it exists
        if (ledgerDataTable) {
            ledgerDataTable.destroy();
            ledgerDataTable = null;
        }
    }
});
</script>
