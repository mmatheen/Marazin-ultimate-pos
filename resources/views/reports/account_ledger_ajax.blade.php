<!-- Account Ledger AJAX Script -->
<script>
$(document).ready(function() {
    // Initialize DataTable variable
    let ledgerDataTable;
    
    // Add debug logging
    console.log('Account Ledger AJAX loaded');
    
    // Function to clear previous ledger display data
    function clearLedgerDisplay() {
        console.log('🧹 AGGRESSIVELY clearing all previous ledger display data');
        
        // FORCE clear account summary section
        $('#accountSummary').empty().html('');
        
        // Also clear individual elements if they exist
        $('#totalTransactions, #totalPaid, #totalReturns, #outstandingDue, #effectiveDue, #advanceAmount').text('Rs. 0.00');
        
        // Force clear customer information with empty values
        $('#customerName, #supplierName').text('').html('');
        $('#customerMobile, #supplierMobile').text('').html('');
        $('#customerEmail, #supplierEmail').text('').html('');
        $('#customerAddress, #supplierAddress').text('').html('');
        $('#customerOpeningBalance, #supplierOpeningBalance').text('Rs. 0.00').html('Rs. 0.00');
        $('#customerCurrentBalance, #supplierCurrentBalance').text('Rs. 0.00').html('Rs. 0.00');
        
        // FORCE clear the entire contact details section
        $('#contactDetails').empty().html('');
        $('#contactDetailsSection').hide();
        
        // AGGRESSIVELY clear ledger table
        if (ledgerDataTable) {
            try {
                ledgerDataTable.clear().draw();
                ledgerDataTable.destroy();
                ledgerDataTable = null;
            } catch(e) {
                console.log('DataTable clear error:', e);
            }
        }
        $('#ledgerTableBody').empty().html('');
        $('#ledgerTable tbody').empty();
        
        // Reset status with forced updates
        $('#filterStatus').empty().html('<i class="fa fa-info-circle text-muted"></i> <small class="text-muted">Select customer to load ledger</small>');
        $('#readyStatus').empty().html('<small class="text-muted">Ready to load</small>');
        
        // FORCE hide all sections
        $('#customerInfo, #supplierInfo').addClass('d-none').hide();
        $('#summaryCard').addClass('d-none').hide();
        $('#ledgerTableContainer').addClass('d-none').hide();
        $('#noDataMessage').hide();
        $('#advanceActionsSection').hide();
        
        // Clear any cached AJAX responses
        if (window.lastLedgerResponse) {
            delete window.lastLedgerResponse;
        }
        
        console.log('✅ Aggressive clearing completed');
    }
    
    // Check for URL parameters and auto-load data
    const urlParams = new URLSearchParams(window.location.search);
    const customerIdParam = urlParams.get('customer_id');
    const supplierIdParam = urlParams.get('supplier_id');
    const contactIdParam = urlParams.get('contact_id');
    
    // Load locations on page load
    loadLocations();

    // Auto-select ledger type and contact based on URL parameters
    if (customerIdParam) {
        $('#ledger_type').val('customer').trigger('change');
        setTimeout(() => {
            $('#contact_id').val(customerIdParam).trigger('change');
            showAutoSelectedNotification('Customer automatically selected from customer list.');
            setTimeout(() => loadLedger(), 500);
        }, 1000);
    } else if (supplierIdParam) {
        $('#ledger_type').val('supplier').trigger('change');
        setTimeout(() => {
            $('#contact_id').val(supplierIdParam).trigger('change');
            showAutoSelectedNotification('Supplier automatically selected from supplier list.');
            setTimeout(() => loadLedger(), 500);
        }, 1000);
    } else if (contactIdParam) {
        // Try to determine type based on contact_id by checking both lists
        loadBothContactLists(contactIdParam);
    }

    function showAutoSelectedNotification(message) {
        $('#autoSelectedMessage').text(message);
        $('#autoSelectedNotification').removeClass('d-none');
        setTimeout(() => {
            $('#autoSelectedNotification').addClass('d-none');
        }, 5000);
    }

    function loadBothContactLists(contactId) {
        // Load customers first and check if contact exists
        $.ajax({
            url: '/customer-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                const customer = response.find(c => c.id == contactId);
                if (customer) {
                    $('#ledger_type').val('customer').trigger('change');
                    setTimeout(() => {
                        $('#contact_id').val(contactId).trigger('change');
                        showAutoSelectedNotification(`Customer "${customer.first_name} ${customer.last_name}" automatically selected.`);
                        setTimeout(() => loadLedger(), 500);
                    }, 1000);
                } else {
                    // Check suppliers if not found in customers
                    $.ajax({
                        url: '/supplier-get-all',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            const supplier = response.find(s => s.id == contactId);
                            if (supplier) {
                                $('#ledger_type').val('supplier').trigger('change');
                                setTimeout(() => {
                                    $('#contact_id').val(contactId).trigger('change');
                                    showAutoSelectedNotification(`Supplier "${supplier.business_name || supplier.first_name + ' ' + supplier.last_name}" automatically selected.`);
                                    setTimeout(() => loadLedger(), 500);
                                }, 1000);
                            }
                        }
                    });
                }
            }
        });
    }

    // Ledger type change handler
    $('#ledger_type').on('change', function() {
        const ledgerType = $(this).val();
        console.log('Ledger type changed to:', ledgerType);
        const contactSelect = $('#contact_id');
        const contactLabel = $('#contact_label');
        const summaryCard = $('#summaryCard');
        
        // Reset form
        contactSelect.val('').trigger('change');
        hideAllSections();
        
        if (ledgerType) {
            // Enable contact dropdown
            contactSelect.prop('disabled', false);
            
            // Update labels and styles based on type
            if (ledgerType === 'customer') {
                contactLabel.text('Customer');
                summaryCard.removeClass('bg-warning text-dark').addClass('bg-info text-white');
                loadCustomers();
            } else if (ledgerType === 'supplier') {
                contactLabel.text('Supplier');
                summaryCard.removeClass('bg-info text-white').addClass('bg-warning text-dark');
                loadSuppliers();
            }
        } else {
            contactSelect.prop('disabled', true);
            contactLabel.text('Contact');
            $('#loadingStatus').hide();
            $('#readyStatus').show();
            $('#refreshBtn').hide();
        }
    });

    // Contact selection change handler - Auto-load ledger
    $('#contact_id').on('change', function() {
        const contactId = $(this).val();
        const ledgerType = $('#ledger_type').val();
        
        // Clear previous data first
        clearLedgerDisplay();
        
        if (contactId && ledgerType) {
            // Show loading status
            $('#readyStatus').hide();
            $('#loadingStatus').show();
            $('#refreshBtn').show();
            
            // Auto-load ledger after short delay
            setTimeout(function() {
                loadLedger();
            }, 300);
        } else {
            hideAllSections();
            $('#loadingStatus').hide();
            $('#readyStatus').show();
            $('#refreshBtn').hide();
        }
    });

    // Remove load ledger button click handler since we auto-load now
    // $('#loadLedgerBtn').on('click', function() {
    //     loadLedger();
    // });

    // Refresh button click handler
    $('#refreshBtn').on('click', function() {
        if ($('#contact_id').val() && $('#ledger_type').val()) {
            loadLedger();
        }
    });

    // Auto-load on date changes if contact is selected
    $('#start_date, #end_date').on('change', function() {
        const contactId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (contactId && ledgerType) {
            $('#loadingStatus').show();
            $('#readyStatus').hide();
            setTimeout(function() {
                loadLedger();
            }, 300);
        }
    });

    // Location change handler
    $('#location_id').on('change', function() {
        const contactId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (contactId && ledgerType) {
            $('#loadingStatus').show();
            $('#readyStatus').hide();
            setTimeout(function() {
                loadLedger();
            }, 300);
        }
    });

    // Show Full History checkbox change handler
    $('#show_full_history').on('change', function() {
        const contactId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (contactId && ledgerType) {
            $('#loadingStatus').show();
            $('#readyStatus').hide();
            const isChecked = $(this).is(':checked');
            console.log('Show full history changed:', isChecked);
            setTimeout(function() {
                loadLedger();
            }, 300);
        }
    });

    function loadCustomers() {
        console.log('Loading customers...');
        $.ajax({
            url: '/customer-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Customer API Response:', response); // Debug log
                
                const customerSelect = $('#contact_id');
                customerSelect.empty().append('<option value="">Select Customer</option>');
                
                // Handle both response formats: status=true with data array OR status=200 with message array
                const customers = response.data || response.message || [];
                const isSuccess = response.status === true || response.status === 200;
                
                console.log('Customers found:', customers.length); // Debug log
                console.log('API Success status:', isSuccess); // Debug log
                
                if (isSuccess && customers.length > 0) {
                    // Sort customers by name for better UX
                    customers.sort((a, b) => {
                        const nameA = (a.first_name || '') + ' ' + (a.last_name || '');
                        const nameB = (b.first_name || '') + ' ' + (b.last_name || '');
                        return nameA.localeCompare(nameB);
                    });
                    
                    customers.forEach(customer => {
                        const fullName = (customer.first_name || '') + ' ' + (customer.last_name || '');
                        customerSelect.append(`<option value="${customer.id}">${fullName.trim()}</option>`);
                        console.log('Added customer:', fullName.trim()); // Debug log
                    });
                } else {
                    console.log('No customers found or API returned empty result');
                    if (!isSuccess) {
                        console.error('API returned unsuccessful status:', response.status);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading customers:', error);
                console.error('Response:', xhr.responseText); // Debug log
                
                // Check for authentication errors
                if (xhr.status === 401) {
                    toastr.error('Authentication required. Please log in again.');
                } else if (xhr.status === 403) {
                    toastr.error('Access denied. Insufficient permissions.');
                } else {
                    toastr.error('Failed to load customers');
                }
            }
        });
    }

    function loadSuppliers() {
        console.log('Loading suppliers...');
        $.ajax({
            url: '/supplier-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Supplier response:', response);
                const supplierSelect = $('#contact_id');
                supplierSelect.empty().append('<option value="">Select Supplier</option>');
                
                // Handle different response structures
                let suppliers = [];
                if (response.status === 200) {
                    if (response.data && Array.isArray(response.data)) {
                        suppliers = response.data;
                    } else if (response.message && Array.isArray(response.message)) {
                        suppliers = response.message;
                    }
                }
                
                if (suppliers.length > 0) {
                    suppliers.forEach(function(supplier) {
                        const option = $('<option></option>')
                            .val(supplier.id)
                            .text(`${supplier.first_name} ${supplier.last_name} (${supplier.mobile_no || supplier.mobile || 'N/A'})`)
                            .data('details', supplier);
                        supplierSelect.append(option);
                    });
                    console.log(`Loaded ${suppliers.length} suppliers`);
                    
                    // Reinitialize Select2 after adding options
                    if (supplierSelect.hasClass('select2-hidden-accessible')) {
                        supplierSelect.select2('destroy');
                    }
                    supplierSelect.select2();
                } else {
                    console.log('No suppliers found in response');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading suppliers:', error);
                console.error('Status:', status);
                console.error('Status Code:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                if (xhr.status === 401) {
                    toastr.error('Authentication required. Please login again.');
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to view suppliers.');
                } else {
                    toastr.error('Failed to load suppliers. Please check the console for more details.');
                }
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

    function loadLedger() {
        const ledgerType = $('#ledger_type').val();
        const contactId = $('#contact_id').val();
        const locationId = $('#location_id').val();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        // Debug logging for troubleshooting
        console.log('Loading ledger with parameters:', {
            ledgerType: ledgerType,
            contactId: contactId,
            locationId: locationId,
            startDate: startDate,
            endDate: endDate
        });

        if (!ledgerType || !contactId) {
            toastr.warning('Please select ledger type and contact');
            return;
        }

        // AGGRESSIVE CACHE CLEARING AND DATA RESET
        clearLedgerDisplay(); // Clear all previous data first
        
        // Disable AJAX caching completely
        $.ajaxSetup({ 
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });

        // Show loading state
        $('#filterStatus').html('<i class="fa fa-spinner fa-spin text-info"></i> <small class="text-info">Loading...</small>');
        $('#loadingStatus').show();
        $('#readyStatus').hide();

        // First load contact details
        loadContactDetails(ledgerType, contactId);

        // Then load ledger data
        const url = ledgerType === 'customer' ? '/customer-ledger-data' : '/supplier-ledger-data';
        const dataKey = ledgerType === 'customer' ? 'customer_id' : 'supplier_id';
        
        // Get show_full_history parameter
        const showFullHistory = $('#show_full_history').is(':checked');

        $.ajax({
            url: url + '?_t=' + Date.now(), // Add timestamp to force fresh request
            type: 'GET',
            cache: false,
            data: {
                [dataKey]: contactId,
                location_id: locationId,
                start_date: startDate,
                end_date: endDate,
                show_full_history: showFullHistory,
                '_cache_bust': Date.now() // Additional cache buster
            },
            success: function(response) {
                console.log('🔥 AJAX SUCCESS - Full API Response:', response);
                console.log('🎯 Customer ID requested:', contactId);
                console.log('📊 Response customer object:', response.customer);
                console.log('💰 Response summary object:', response.summary);
                
                $('#filterStatus').html('<i class="fa fa-check-circle text-success"></i> <small class="text-success">Loaded</small>');
                
                // Hide loading and show ready status
                $('#loadingStatus').hide();
                $('#readyStatus').show();
                $('#readyStatus').html('<i class="fa fa-check-circle text-success"></i> <small class="text-success">Ledger loaded</small>');
                
                // Handle the correct response structure
                const isSuccess = response.status === 200;
                const transactions = response.transactions || [];
                const customer = response.customer || {};
                const summary = response.summary || {};
                const advanceApp = response.advance_application || {};
                
                console.log('🔍 Processing response for customer:', customer.name || 'Unknown', 'ID:', customer.id, 'Transactions:', transactions.length);
                console.log('💸 Summary effective_due:', summary.effective_due, 'outstanding_due:', summary.outstanding_due);
                
                if (isSuccess) {
                    // ALWAYS update customer details when response is successful
                    if (customer.id) {
                        updateContactDetailsFromResponse(customer, ledgerType);
                    }
                    
                    // Update summary even if no transactions (to show correct customer balance)
                    updateSummaryDisplay(summary);
                    
                    if (transactions.length > 0) {
                        populateLedgerTable(transactions, summary);
                        $('#ledgerTableSection').show();
                        $('#noDataMessage').hide();
                    } else {
                        // Show customer info but empty table
                        $('#ledgerTableSection').show();
                        if (ledgerDataTable) {
                            ledgerDataTable.clear().draw();
                        } else {
                            $('#ledgerTableBody').html('<tr><td colspan="8" class="text-center text-muted">No transactions found for the selected period</td></tr>');
                        }
                        $('#noDataMessage').hide(); // Don't show "no data" message, show empty table instead
                    }
                } else {
                    $('#ledgerTableSection').hide();
                    $('#noDataMessage').show();
                }

                // Show advance management for customers with credit balance
                if (ledgerType === 'customer' && summary) {
                    const advanceAmount = advanceApp.available_advance || summary.advance_amount || 0;
                    console.log('Available advance amount:', advanceAmount);
                    
                    $('#advanceAmount').text(`Rs ${formatCurrency(advanceAmount)}`);
                    $('#advanceActionsSection').show();
                    
                    // Enable/disable button based on advance availability
                    if (advanceAmount > 0) {
                        $('#applyAdvanceBtn, #manageAdvanceBtn').prop('disabled', false);
                        $('#applyAdvanceBtn').removeClass('btn-secondary').addClass('btn-success');
                    } else {
                        $('#applyAdvanceBtn').prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
                        $('#manageAdvanceBtn').prop('disabled', false); // Always allow manage for customers
                    }
                } else {
                    $('#advanceActionsSection').hide();
                }
            },
            error: function(xhr, status, error) {
                $('#filterStatus').html('<i class="fa fa-exclamation-circle text-danger"></i> <small class="text-danger">Error</small>');
                console.error('Error loading ledger:', error);
                toastr.error('Failed to load ledger data');
                
                // Hide loading and show error status
                $('#loadingStatus').hide();
                $('#readyStatus').show();
                $('#readyStatus').html('<i class="fa fa-exclamation-circle text-danger"></i> <small class="text-danger">Error loading</small>');
                
                hideAllSections();
            }
        });
    }

    function loadContactDetails(ledgerType, contactId) {
        const contactData = $('#contact_id option:selected').data('details');
        const contactInfoTitle = $('#contact_info_title');
        
        if (contactData) {
            // Update section title
            contactInfoTitle.text(ledgerType === 'customer' ? 'Customer' : 'Supplier');
            
            // Populate contact details
            const contactDetailsHtml = `
                <p><strong>Name:</strong> ${contactData.first_name} ${contactData.last_name}</p>
                <p><strong>Mobile:</strong> ${contactData.mobile_no || contactData.mobile || 'N/A'}</p>
                <p><strong>Email:</strong> ${contactData.email || 'N/A'}</p>
                <p><strong>Address:</strong> ${contactData.address || 'N/A'}</p>
                <p><strong>Opening Balance:</strong> Rs. ${formatCurrency(contactData.opening_balance || 0)}</p>
                <p><strong>Current Balance:</strong> Rs. ${formatCurrency(contactData.current_balance || 0)}</p>
            `;
            
            $('#contactDetails').html(contactDetailsHtml);
            $('#contactDetailsSection').show();
        }
    }

    function updateContactDetailsFromResponse(customer, ledgerType) {
        const contactInfoTitle = $('#contact_info_title');
        
        // Update section title
        contactInfoTitle.text(ledgerType === 'customer' ? 'Customer' : 'Supplier');
        
        // Populate contact details from API response
        const contactDetailsHtml = `
            <p><strong>Name:</strong> ${customer.name || 'N/A'}</p>
            <p><strong>Mobile:</strong> ${customer.mobile || 'N/A'}</p>
            <p><strong>Email:</strong> ${customer.email || 'N/A'}</p>
            <p><strong>Address:</strong> ${customer.address || 'N/A'}</p>
            <p><strong>Opening Balance:</strong> Rs. ${formatCurrency(customer.opening_balance || 0)}</p>
            <p><strong>Current Balance:</strong> Rs. ${formatCurrency(customer.current_balance || 0)}</p>
        `;
        
        $('#contactDetails').html(contactDetailsHtml);
        $('#contactDetailsSection').show();
    }

    function updateSummaryDisplay(summary) {
        console.log('🎯 updateSummaryDisplay called with summary:', summary);
        
        // Extract values with detailed logging
        const totalTransactions = summary.total_transactions || 0;
        const totalPaid = summary.total_paid || 0;
        const totalReturns = summary.total_returns || 0;
        const outstandingDue = summary.outstanding_due || 0;
        const effectiveDue = summary.effective_due || 0;
        const advanceAmount = summary.advance_amount || 0;
        
        console.log('💰 Extracted values:', {
            totalTransactions,
            totalPaid, 
            totalReturns,
            outstandingDue,
            effectiveDue,
            advanceAmount
        });
        
        // Build the complete account summary HTML
        const summaryHtml = `
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Total Transactions</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(totalTransactions)}</h4>
                </div>
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Total Paid</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(totalPaid)}</h4>
                </div>
            </div>
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Total Returns</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(totalReturns)}</h4>
                </div>
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Outstanding Due</h6>
                    <h6 class="text-white small">(Inc. Opening Balance)</h6>
                    <h4 class="text-white">Rs. ${formatCurrency(outstandingDue)}</h4>
                </div>
            </div>
            <div class="row text-center">
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Advance Amount</h6>
                    <h4 class="text-success">Rs. ${formatCurrency(advanceAmount)}</h4>
                </div>
                <div class="col-6 mb-3">
                    <h6 class="text-white mb-1">Effective Due</h6>
                    <h4 class="${effectiveDue > 0 ? 'text-danger' : 'text-success'}">Rs. ${formatCurrency(effectiveDue)}</h4>
                </div>
            </div>
        `;
        
        // Update the account summary container
        $('#accountSummary').html(summaryHtml);
        
        // Show the summary card and ensure proper styling
        $('#summaryCard').removeClass('d-none').show();
        $('#summaryCard .card-body').addClass('bg-info text-white'); // Ensure blue background
        
        console.log('✅ Summary display updated successfully with HTML');
    }

    function populateLedgerTable(ledgerData, summary) {
        // Destroy existing DataTable if it exists
        if (ledgerDataTable) {
            ledgerDataTable.destroy();
        }

        // Clear existing table data
        $('#ledgerTableBody').empty();

        let totalDebit = 0;
        let totalCredit = 0;
        const tableData = [];

        // Populate table with ledger entries
        ledgerData.forEach(function(entry, index) {
            // Debug: Log the date format being received
            if (index === 0) {
                console.log('First entry date format:', entry.date, typeof entry.date);
                console.log('Full entry object:', entry);
            }
            
            totalDebit += parseFloat(entry.debit || 0);
            totalCredit += parseFloat(entry.credit || 0);

            const typeClass = getTypeClass(entry.transaction_type || entry.type);
            const statusClass = getStatusClass(entry.payment_status);
            const runningBalance = parseFloat(entry.running_balance || 0);
            const balanceClass = runningBalance < 0 ? 'text-success' : 'text-dark';
            
            // Enhanced balance display using running_balance from response
            let balanceDisplay = '';
            if (runningBalance < 0) {
                balanceDisplay = `<div class="text-end fw-bold text-success">Rs. ${formatCurrency(Math.abs(runningBalance))} (Advance)</div>`;
            } else if (runningBalance > 0) {
                balanceDisplay = `<div class="text-end fw-bold text-danger">Rs. ${formatCurrency(runningBalance)} (Due)</div>`;
            } else {
                balanceDisplay = `<div class="text-end fw-bold text-secondary">Rs. 0.00 (Clear)</div>`;
            }

            // Try different date fields that might be present in the API response
            const dateValue = entry.date || entry.created_at || entry.transaction_date || entry.updated_at;
            
            tableData.push([
                index + 1,
                formatDate(dateValue), // Try multiple possible date fields
                entry.reference_no || 'N/A',
                `<span class="badge ${typeClass}">${entry.type || 'N/A'}</span>`, // Using 'type' field
                entry.location || 'N/A', // Using 'location' field
                `<span class="badge ${statusClass}">${entry.payment_status || 'N/A'}</span>`,
                entry.debit > 0 ? `<div class="text-end">Rs. ${formatCurrency(entry.debit)}</div>` : '<div class="text-end">-</div>',
                entry.credit > 0 ? `<div class="text-end">Rs. ${formatCurrency(entry.credit)}</div>` : '<div class="text-end">-</div>',
                balanceDisplay,
                entry.payment_method || 'N/A',
                entry.notes || entry.others || 'N/A'
            ]);
        });

        // Update totals in footer - use the final running balance from the last entry
        const finalBalance = ledgerData.length > 0 ? ledgerData[ledgerData.length - 1].running_balance : 0;
        $('#totalDebit').text(`Rs. ${formatCurrency(totalDebit)}`);
        $('#totalCredit').text(`Rs. ${formatCurrency(totalCredit)}`);
        $('#totalBalance').text(`Rs. ${formatCurrency(finalBalance)}`);

        // Populate account summary with detailed information
        if (summary) {
            const summaryHtml = `
                <div class="row text-center">
                    <div class="col-6">
                        <h6 class="text-white mb-1">Total Transactions</h6>
                        <h4 class="text-white">Rs. ${formatCurrency(summary.total_transactions || summary.total_invoices || 0)}</h4>
                    </div>
                    <div class="col-6">
                        <h6 class="text-white mb-1">Total Paid</h6>
                        <h4 class="text-white">Rs. ${formatCurrency(summary.total_payments || summary.total_paid || 0)}</h4>
                    </div>
                </div>
                <div class="row text-center mt-2">
                    <div class="col-6">
                        <h6 class="text-white mb-1">Total Returns</h6>
                        <h4 class="text-white">Rs. ${formatCurrency(summary.total_returns || 0)}</h4>
                    </div>
                    <div class="col-6">
                        <h6 class="text-white mb-1">Outstanding Due</h6>
                        <h6 class="text-white small">(Inc. Opening Balance)</h6>
                        <h4 class="text-white text-warning">Rs. ${formatCurrency(summary.outstanding || summary.outstanding_due || 0)}</h4>
                    </div>
                </div>
                <div class="row text-center mt-2">
                    <div class="col-6">
                        <h6 class="text-white mb-1">Advance Amount</h6>
                        <h4 class="text-white text-success">Rs. ${formatCurrency(summary.advance_amount || 0)}</h4>
                    </div>
                    <div class="col-6">
                        <h6 class="text-white mb-1">Effective Due</h6>
                        <h4 class="text-white ${(summary.effective_due || 0) > 0 ? 'text-danger' : 'text-success'}">
                            Rs. ${formatCurrency(summary.effective_due || 0)}
                        </h4>
                    </div>
                </div>
                ${(summary.opening_balance || 0) !== 0 ? `
                <div class="row text-center mt-2">
                    <div class="col-12">
                        <div class="alert ${(summary.opening_balance || 0) > 0 ? 'alert-warning' : 'alert-info'} alert-sm p-2 mb-0">
                            <small class="text-dark">
                                <i class="fa fa-info-circle"></i>
                                Opening Balance: Rs. ${formatCurrency(summary.opening_balance || 0)} 
                                ${(summary.opening_balance || 0) > 0 ? '(Amount owed)' : '(Credit balance)'}
                            </small>
                        </div>
                    </div>
                </div>
                ` : ''}
                ${(summary.advance_amount || 0) > 0 ? `
                <div class="row text-center mt-2">
                    <div class="col-12">
                        <div class="alert alert-info alert-sm p-2 mb-0">
                            <small class="text-dark">
                                <i class="fa fa-info-circle"></i>
                                Advance Rs. ${formatCurrency(Math.min(summary.advance_amount || 0, summary.outstanding || summary.outstanding_due || 0))} 
                                available for application
                            </small>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            $('#accountSummary').html(summaryHtml);
        }

        // Initialize DataTable with enhanced features
        ledgerDataTable = $('#ledgerTable').DataTable({
            data: tableData,
            destroy: true,
            responsive: true,
            processing: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            ordering: false, // Disable sorting to maintain chronological order
            columnDefs: [
                { 
                    targets: [0], // Serial number column
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                { 
                    targets: [1], // Date column
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
                    orderable: false,
                    className: 'text-end'
                },
                { 
                    targets: [9, 10], // Payment method and others
                    orderable: false,
                    className: 'text-center'
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Export Excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> Export PDF',
                    className: 'btn btn-danger btn-sm'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-secondary btn-sm'
                }
            ],
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
        if (!type) return 'bg-secondary';
        switch(type.toLowerCase()) {
            case 'sale': return 'bg-primary';
            case 'payment': case 'payments': return 'bg-success';
            case 'return': case 'sale_return': return 'bg-warning';
            case 'purchase': return 'bg-info';
            case 'opening_balance': return 'bg-dark';
            default: return 'bg-secondary';
        }
    }

    function getStatusClass(status) {
        if (!status) return 'bg-secondary';
        switch(status.toLowerCase()) {
            case 'paid': return 'bg-success';
            case 'partial': return 'bg-warning';
            case 'due': return 'bg-danger';
            case 'final': return 'bg-info';
            default: return 'bg-secondary';
        }
    }

    function formatCurrency(amount) {
        return parseFloat(amount || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function hideAllSections() {
        $('#contactDetailsSection').hide();
        $('#ledgerTableSection').hide();
        $('#advanceActionsSection').hide();
        $('#noDataMessage').hide();
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            let date;
            
            // If it's already a valid date string
            if (typeof dateString === 'string') {
                // Clean up escaped forward slashes from JSON response
                let cleanDateString = dateString.replace(/\\\//g, '/');
                
                // Handle API format: DD/MM/YYYY HH:MM:SS (23/09/2025 08:47:30)
                if (cleanDateString.match(/^\d{1,2}\/\d{1,2}\/\d{4} \d{1,2}:\d{2}:\d{2}$/)) {
                    // Parse DD/MM/YYYY HH:MM:SS format
                    const parts = cleanDateString.split(' ');
                    const dateParts = parts[0].split('/');
                    const timeParts = parts[1].split(':');
                    
                    // Create date object (month is 0-indexed in JS)
                    date = new Date(
                        parseInt(dateParts[2]), // year
                        parseInt(dateParts[1]) - 1, // month (0-indexed)
                        parseInt(dateParts[0]), // day
                        parseInt(timeParts[0]), // hour
                        parseInt(timeParts[1]), // minute
                        parseInt(timeParts[2]) // second
                    );
                }
                // Handle API format: DD/MM/YYYY (23/09/2025)
                else if (cleanDateString.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
                    const dateParts = cleanDateString.split('/');
                    date = new Date(
                        parseInt(dateParts[2]), // year
                        parseInt(dateParts[1]) - 1, // month (0-indexed)
                        parseInt(dateParts[0]) // day
                    );
                }
                // Handle MySQL datetime format (YYYY-MM-DD HH:MM:SS)
                else if (cleanDateString.match(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)) {
                    date = new Date(cleanDateString);
                }
                // Handle MySQL date format (YYYY-MM-DD)
                else if (cleanDateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    date = new Date(cleanDateString + ' 00:00:00');
                }
                // Handle ISO format (2025-09-23T03:17:30.000000Z)
                else if (cleanDateString.match(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/)) {
                    date = new Date(cleanDateString);
                }
                // Try parsing as-is for other formats
                else {
                    date = new Date(cleanDateString);
                }
            } else {
                date = new Date(dateString);
            }
            
            // Check if the date is valid
            if (isNaN(date.getTime())) {
                console.warn('Invalid date format:', dateString);
                return dateString; // Return original string if can't parse
            }
            
            // Format as DD/MM/YYYY HH:MM
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${day}/${month}/${year} ${hours}:${minutes}`;
            
        } catch (error) {
            console.error('Error formatting date:', dateString, error);
            return dateString; // Return original string if error occurs
        }
    }

    // Advance payment handlers (for customer ledger)
    $('body').on('click', '#applyAdvanceBtn', function(e) {
        e.preventDefault();
        const customerId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (ledgerType !== 'customer') return;
        
        if (!customerId) {
            toastr.warning('Please select a customer first');
            return;
        }
        
        const advanceAmount = parseFloat($('#advanceAmount').text().replace('Rs. ', '') || 0);
        if (advanceAmount <= 0) {
            toastr.warning('No advance amount available to apply');
            return;
        }
        
        if (confirm(`Apply Rs. ${advanceAmount.toFixed(2)} advance to outstanding bills?`)) {
            applyAdvancePayments(customerId);
        }
    });

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
                if (response.status === 200 || response.success) {
                    toastr.success(response.message || 'Advance payments applied successfully');
                    // Reload the ledger to show updated data
                    loadLedger();
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
                $('#applyAdvanceBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Apply to Outstanding');
            }
        });
    }

    // Advanced customer management handlers
    $('body').on('click', '#manageAdvanceBtn', function(e) {
        e.preventDefault();
        console.log('Manage advance button clicked');
        const contactId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (!contactId) {
            toastr.warning('Please select a contact first');
            return;
        }
        
        if (ledgerType === 'customer') {
            $('#modalAdvanceAmount').text($('#advanceAmount').text());
            // Show advance management modal (you can create this modal)
            toastr.info('Advanced management features coming soon');
        }
    });

    // Handle return processing options (for customers)
    function handleReturnOption(option) {
        const contactId = $('#contact_id').val();
        const ledgerType = $('#ledger_type').val();
        
        if (!contactId || ledgerType !== 'customer') {
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
            toastr.info('Return processing option: ' + option.toUpperCase());
            processReturn(contactId, option);
        }
    }

    // Process return (placeholder for future implementation)
    function processReturn(contactId, option) {
        console.log('Processing return for contact:', contactId, 'Option:', option);
        toastr.success('Return processing initiated');
        // Implement return processing logic here
    }

    // URL parameter handling for auto-selection
    function checkUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const customerIdParam = urlParams.get('customer_id');
        const supplierIdParam = urlParams.get('supplier_id');
        
        if (customerIdParam) {
            $('#ledger_type').val('customer').trigger('change');
            setTimeout(function() {
                if ($('#contact_id option[value="' + customerIdParam + '"]').length) {
                    $('#contact_id').val(customerIdParam).trigger('change');
                    setTimeout(function() {
                        loadLedger();
                    }, 500);
                }
            }, 1000);
        } else if (supplierIdParam) {
            $('#ledger_type').val('supplier').trigger('change');
            setTimeout(function() {
                if ($('#contact_id option[value="' + supplierIdParam + '"]').length) {
                    $('#contact_id').val(supplierIdParam).trigger('change');
                    setTimeout(function() {
                        loadLedger();
                    }, 500);
                }
            }, 1000);
        }
    }

    // Initialize URL parameter checking
    $(document).ready(function() {
        setTimeout(checkUrlParameters, 1500);
    });
});
</script>