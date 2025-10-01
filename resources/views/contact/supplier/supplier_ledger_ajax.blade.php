
<script>
// Global function definitions for onclick handlers
function handleReturnOption(option) {
    switch(option) {
        case 'cash':
            alert('Cash credit option selected. This will process returns as cash payments.');
            break;
        case 'advance':
            alert('Add to advance option selected. Return amounts will be added to supplier advance balance.');
            break;
        case 'adjust':
            alert('Adjust against bills option selected. Returns will be adjusted against outstanding purchase bills.');
            break;
    }
}

function addManualAdvance() {
    const amount = parseFloat($('#manualAdvanceAmount').val());
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid advance amount');
        return;
    }
    
    // Implement manual advance addition logic
    alert(`Manual advance of Rs${amount.toFixed(2)} will be added.`);
    $('#manualAdvanceAmount').val('');
}

function adjustAdvance() {
    const amount = parseFloat($('#adjustAdvanceAmount').val());
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid adjustment amount');
        return;
    }
    
    // Implement advance adjustment logic
    alert(`Advance adjustment of Rs${amount.toFixed(2)} will be deducted.`);
    $('#adjustAdvanceAmount').val('');
}

function refreshAdvanceData() {
    const supplierId = $('#supplier_id').val();
    if (supplierId) {
        // These functions will be available after document ready
        if (typeof loadSupplierDetails === 'function') {
            loadSupplierDetails(supplierId);
        }
        if (typeof updateModalAdvanceAmount === 'function') {
            updateModalAdvanceAmount();
        }
    }
}

$(document).ready(function() {
    console.log('Supplier ledger initialized');
    
    let supplierLedgerTable;
    let currentSupplierData = null;
    let currentAdvanceAmount = 0;
    
    // Initialize date fields with current date
    const today = new Date().toISOString().split('T')[0];
    $('#end_date').val(today);
    
    // Load suppliers and locations
    loadSuppliers();
    loadLocations();
    
    // Initialize DataTable
    initializeDataTable();
    
    // Event handlers using body delegation for better reliability
    $('body').on('click', '#applyAdvanceBtn', function() {
        console.log('Apply advance button clicked');
        applySupplierAdvance();
    });
    
    $('body').on('click', '#manageAdvanceBtn', function() {
        console.log('Manage advance button clicked');
        $('#advanceManagementModal').modal('show');
        updateModalAdvanceAmount();
    });
    
    $('body').on('click', '#refreshLedgerBtn', function() {
        console.log('Refresh button clicked');
        loadSupplierLedger();
    });
    
    $('body').on('click', '#modalApplyAdvanceBtn', function() {
        console.log('Modal apply advance button clicked');
        applySupplierAdvance();
        $('#advanceManagementModal').modal('hide');
    });
    
    // Supplier selection change
    $('#supplier_id').on('change', function() {
        const supplierId = $(this).val();
        if (supplierId) {
            loadSupplierDetails(supplierId);
            loadSupplierLedger();
        } else {
            hideSupplierDetails();
        }
    });
    
    // Date range change
    $('#start_date, #end_date').on('change', function() {
        if ($('#supplier_id').val()) {
            loadSupplierLedger();
        }
    });
    
    // Location filter change  
    $('#location_id').on('change', function() {
        if ($('#supplier_id').val()) {
            loadSupplierLedger();
        }
    });
    
    function loadSuppliers() {
        $.ajax({
            url: '/supplier-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                const supplierSelect = $('#supplier_id');
                supplierSelect.empty().append('<option value="">Select Supplier</option>');
                
                if (response.status === 200 && response.message && response.message.length > 0) {
                    response.message.forEach(function(supplier) {
                        const fullName = `${supplier.first_name} ${supplier.last_name}`;
                        const displayText = `${fullName} - ${supplier.mobile_no || 'N/A'} (ID: ${supplier.id})`;
                        supplierSelect.append(`<option value="${supplier.id}">${displayText}</option>`);
                    });
                    
                    // After suppliers are loaded, check for auto-selection
                    checkAutoSelectSupplier();
                }
            },
            error: function(xhr) {
                console.error('Error loading suppliers:', xhr);
                showNotification('Error loading suppliers', 'error');
            }
        });
    }
    
    function loadLocations() {
        $.ajax({
            url: '/location-get-all',
            method: 'GET',
            success: function(response) {
                const locationSelect = $('#location_id');
                locationSelect.empty().append('<option value="">All Locations</option>');
                
                if (response.message && response.message.length > 0) {
                    response.message.forEach(function(location) {
                        locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading locations:', xhr);
            }
        });
    }
    
    function checkAutoSelectSupplier() {
        const urlParams = new URLSearchParams(window.location.search);
        const supplierId = urlParams.get('supplier_id');
        
        console.log('Checking auto-select for supplier ID:', supplierId);
        
        if (supplierId) {
            // Set the supplier dropdown value
            $('#supplier_id').val(supplierId);
            
            // Show the notification
            $('#autoSelectedNotification').removeClass('d-none');
            $('#autoSelectedMessage').text('Supplier automatically selected from supplier list.');
            
            // Trigger the change event to load data
            $('#supplier_id').trigger('change');
            
            console.log('Auto-selected supplier ID:', supplierId);
        }
    }
    
    function loadSupplierDetails(supplierId) {
        $.ajax({
            url: '/supplier-edit/' + supplierId,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    currentSupplierData = response.message;
                    displaySupplierDetails(response.message);
                    $('#supplierDetailsSection').show();
                } else {
                    showNotification('Error loading supplier details', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error loading supplier details:', xhr);
                showNotification('Error loading supplier details', 'error');
            }
        });
    }
    
    function displaySupplierDetails(supplier) {
        const fullName = `${supplier.first_name || ''} ${supplier.last_name || ''}`.trim();
        const detailsHtml = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Name:</strong> ${fullName || 'N/A'}<br>
                    <strong>Mobile:</strong> ${supplier.mobile_no || 'N/A'}<br>
                    <strong>Email:</strong> ${supplier.email || 'N/A'}
                </div>
                <div class="col-md-6">
                    <strong>Supplier ID:</strong> ${supplier.id || 'N/A'}<br>
                    <strong>Opening Balance:</strong> Rs${parseFloat(supplier.opening_balance || 0).toFixed(2)}<br>
                    <strong>Current Balance:</strong> Rs${parseFloat(supplier.current_balance || 0).toFixed(2)}
                </div>
            </div>
        `;
        $('#supplierDetails').html(detailsHtml);
        
        const currentBalance = parseFloat(supplier.current_balance || 0);
        const advanceAmount = currentBalance > 0 ? currentBalance : 0; // For suppliers, positive balance means advance available
        currentAdvanceAmount = advanceAmount;
        
        const summaryHtml = `
            <div class="row">
                <div class="col-12">
                    <h6 class="text-dark">Current Balance: Rs${currentBalance.toFixed(2)} ${currentBalance < 0 ? '(We owe supplier)' : currentBalance > 0 ? '(Advance available)' : ''}</h6>
                    <h6 class="text-dark">Available Advance: Rs${advanceAmount.toFixed(2)}</h6>
                    <small class="text-muted">Total Purchase Due: Rs${parseFloat(supplier.total_purchase_due || 0).toFixed(2)}</small><br>
                    <small class="text-muted">Total Return Due: Rs${parseFloat(supplier.total_return_due || 0).toFixed(2)}</small>
                </div>
            </div>
        `;
        $('#accountSummary').html(summaryHtml);
        
        // Update advance actions
        updateAdvanceActions(advanceAmount);
    }
    
    function updateAdvanceActions(advanceAmount) {
        $('#advanceAmount').text(advanceAmount.toFixed(2));
        
        if (advanceAmount > 0) {
            $('#advanceActionsSection').show();
            $('#advanceStatusText').text('Available for application to bills');
            $('#advanceStatus').removeClass('badge-secondary').addClass('badge-success').text('Available');
            $('#applyAdvanceBtn').prop('disabled', false);
        } else {
            $('#advanceActionsSection').hide();
        }
    }
    
    function hideSupplierDetails() {
        $('#supplierDetailsSection').hide();
        $('#advanceActionsSection').hide();
        $('#ledgerTableSection').hide();
        $('#noDataMessage').hide();
        currentSupplierData = null;
        currentAdvanceAmount = 0;
    }
    
    function initializeDataTable() {
        // Check if DataTable already exists and destroy it
        if ($.fn.DataTable.isDataTable('#ledgerTable')) {
            $('#ledgerTable').DataTable().destroy();
        }
        
        supplierLedgerTable = $('#ledgerTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            order: [[1, 'asc']], // Sort by date ascending (chronological order)
            columnDefs: [
                { targets: [6, 7, 8], className: 'text-right' }, // Amount columns right-aligned
                { targets: [0], width: '5%' }, // Serial number
                { targets: [1], width: '10%' }, // Date
                { targets: [2], width: '15%' }, // Reference
                { targets: [3], width: '12%' }, // Type
                { targets: [4], width: '12%' }, // Location
                { targets: [5], width: '12%' }, // Payment Status
                { targets: [6, 7, 8], width: '10%' }, // Amount columns
                { targets: [9], width: '10%' }, // Payment Method
                { targets: [10], width: '8%' }  // Others
            ],
            language: {
                processing: "Loading supplier ledger...",
                emptyTable: "No transactions found for this supplier",
                zeroRecords: "No matching transactions found"
            },
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    }
    
    function loadSupplierLedger() {
        const supplierId = $('#supplier_id').val();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const locationId = $('#location_id').val();
        
        if (!supplierId || !startDate || !endDate) {
            showNotification('Please select supplier and date range', 'warning');
            return;
        }
        
        $('#filterStatus').html('<i class="fa fa-spinner fa-spin text-warning"></i><br><small class="text-warning">Loading...</small>');
        
        $.ajax({
            url: '/supplier-ledger-data',
            method: 'GET',
            data: {
                supplier_id: supplierId,
                start_date: startDate,
                end_date: endDate,
                location_id: locationId
            },
            success: function(response) {
                $('#filterStatus').html('<i class="fa fa-check-circle text-success"></i><br><small class="text-success">Loaded</small>');
                
                if (response.status === 200 && response.transactions && response.transactions.length > 0) {
                    populateLedgerTable(response.transactions);
                    updateTableFooter(response.summary);
                    $('#ledgerTableSection').show();
                    $('#noDataMessage').hide();
                } else {
                    $('#ledgerTableSection').hide();
                    $('#noDataMessage').show();
                    showNotification('No data found', 'info');
                }
            },
            error: function(xhr) {
                $('#filterStatus').html('<i class="fa fa-exclamation-circle text-danger"></i><br><small class="text-danger">Error</small>');
                console.error('Error loading ledger:', xhr);
                showNotification('Error loading supplier ledger', 'error');
            }
        });
    }
    
    function populateLedgerTable(data) {
        supplierLedgerTable.clear();
        
        if (data && data.length > 0) {
            data.forEach(function(item, index) {
                const debit = parseFloat(item.debit || 0);
                const credit = parseFloat(item.credit || 0);
                const balance = parseFloat(item.running_balance || item.balance || 0);
                
                supplierLedgerTable.row.add([
                    index + 1,
                    item.date || '',
                    item.reference_no || '',
                    item.type || '',
                    item.location || '',
                    item.payment_status || '',
                    debit > 0 ? `Rs${debit.toFixed(2)}` : '',
                    credit > 0 ? `Rs${credit.toFixed(2)}` : '',
                    `Rs${balance.toFixed(2)}`,
                    item.payment_method || '',
                    item.others || ''
                ]);
            });
        }
        
        supplierLedgerTable.draw();
    }
    
    function updateTableFooter(summary) {
        if (summary) {
            $('#totalDebit').text(`Rs${parseFloat(summary.total_purchases || 0).toFixed(2)}`);
            $('#totalCredit').text(`Rs${parseFloat(summary.total_paid || 0).toFixed(2)}`);
            $('#totalBalance').text(`Rs${parseFloat(summary.balance_due || 0).toFixed(2)}`);
        }
    }
    
    function applySupplierAdvance() {
        const supplierId = $('#supplier_id').val();
        
        if (!supplierId) {
            showNotification('Please select a supplier', 'warning');
            return;
        }
        
        if (currentAdvanceAmount <= 0) {
            showNotification('No advance amount available to apply', 'warning');
            return;
        }
        
        if (!confirm(`Apply Rs${currentAdvanceAmount.toFixed(2)} advance amount to outstanding purchase bills?`)) {
            return;
        }
        
        $.ajax({
            url: '/apply-supplier-advance',
            method: 'POST',
            data: {
                supplier_id: supplierId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status === 200) {
                    showNotification(response.message, 'success');
                    loadSupplierDetails(supplierId);
                    loadSupplierLedger();
                } else {
                    showNotification(response.message || 'Error applying advance', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error applying advance:', xhr);
                showNotification('Error applying advance amount', 'error');
            }
        });
    }
    
    function updateModalAdvanceAmount() {
        $('#modalAdvanceAmount').text(currentAdvanceAmount.toFixed(2));
    }
    
    function handleReturnOption(option) {
        switch(option) {
            case 'cash':
                alert('Cash credit option selected. This will process returns as cash payments.');
                break;
            case 'advance':
                alert('Add to advance option selected. Return amounts will be added to supplier advance balance.');
                break;
            case 'adjust':
                alert('Adjust against bills option selected. Returns will be adjusted against outstanding purchase bills.');
                break;
        }
    }
    
    function addManualAdvance() {
        const amount = parseFloat($('#manualAdvanceAmount').val());
        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid advance amount');
            return;
        }
        
        // Implement manual advance addition logic
        alert(`Manual advance of Rs${amount.toFixed(2)} will be added.`);
        $('#manualAdvanceAmount').val('');
    }
    
    function adjustAdvance() {
        const amount = parseFloat($('#adjustAdvanceAmount').val());
        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid adjustment amount');
            return;
        }
        
        // Implement advance adjustment logic
        alert(`Advance adjustment of Rs${amount.toFixed(2)} will be deducted.`);
        $('#adjustAdvanceAmount').val('');
    }
    
    function refreshAdvanceData() {
        const supplierId = $('#supplier_id').val();
        if (supplierId) {
            loadSupplierDetails(supplierId);
            updateModalAdvanceAmount();
        }
    }
    
    function showNotification(message, type = 'info') {
        const alertClass = type === 'error' ? 'alert-danger' : 
                          type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
    }
});
</script>

