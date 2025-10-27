<script>
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); // For CSRF token

        // Check for URL parameters and auto-select filters
        const urlParams = new URLSearchParams(window.location.search);
        const urlCustomerId = urlParams.get('customer_id');
        const urlLocationId = urlParams.get('location_id');
        
        console.log('=== Sales Page URL Parameters ===');
        console.log('customer_id from URL:', urlCustomerId);
        console.log('location_id from URL:', urlLocationId);

        // Set filter values IMMEDIATELY before DataTable initialization
        if (urlCustomerId) {
            console.log('Pre-setting customer filter to:', urlCustomerId);
            $('#customerFilter').val(urlCustomerId);
            console.log('Customer filter value after setting:', $('#customerFilter').val());
        }
        if (urlLocationId) {
            console.log('Pre-setting location filter to:', urlLocationId);
            $('#locationFilter').val(urlLocationId);
            console.log('Location filter value after setting:', $('#locationFilter').val());
        }

        // Make sure table exists and destroy any existing DataTable
        if ($.fn.DataTable.isDataTable('#salesTable')) {
            $('#salesTable').DataTable().destroy();
        }

        // Check if table element exists
        if ($('#salesTable').length === 0) {
            return;
        }

        const table = $('#salesTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            stateSave: false,
            ajax: {
                url: '/api/sales/paginated',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                data: function(d) {
                    // STRICT DataTable server-side parameter enforcement
                    var requestData = {
                        draw: parseInt(d.draw) || 1,
                        start: parseInt(d.start) || 0,
                        length: parseInt(d.length) || 10
                    };

                    // Add search parameters if present
                    if (d.search && d.search.value) {
                        requestData['search[value]'] = d.search.value;
                        requestData['search[regex]'] = d.search.regex || false;
                    }

                    // Add order parameters if present
                    if (d.order && d.order[0]) {
                        requestData['order[0][column]'] = parseInt(d.order[0].column) || 0;
                        requestData['order[0][dir]'] = d.order[0].dir || 'desc';
                    }

                    // Add custom filters
                    console.log('=== Reading Filter Values ===');
                    console.log('customerFilter element:', $('#customerFilter').length > 0);
                    console.log('customerFilter value:', $('#customerFilter').val());
                    console.log('locationFilter value:', $('#locationFilter').val());
                    
                    if ($('#customerFilter').val()) requestData.customer_id = $('#customerFilter')
                        .val();
                    if ($('#locationFilter').val()) requestData.location_id = $('#locationFilter')
                        .val();
                    if ($('#userFilter').val()) requestData.user_id = $('#userFilter').val();
                    if ($('#paymentStatusFilter').val()) requestData.payment_status = $(
                        '#paymentStatusFilter').val();
                    if ($('#paymentMethodFilter').val()) requestData.payment_method = $(
                        '#paymentMethodFilter').val();
                    if ($('#dateRangeFilter').val()) {
                        var dateRange = $('#dateRangeFilter').val().split(' - ');
                        if (dateRange.length === 2) {
                            requestData.start_date = dateRange[0];
                            requestData.end_date = dateRange[1];
                        }
                    }

                    console.log('DataTable AJAX request data:', requestData);
                    console.log('================================');
                    return requestData;
                },
                // Simple DataTable server-side response handler
                dataSrc: function(json) {
                    console.log('DataTable Success:', {
                        recordsTotal: json.recordsTotal,
                        recordsFiltered: json.recordsFiltered,
                        data_length: json.data ? json.data.length : 0,
                        draw: json.draw,
                        debug: json.debug || null
                    });

                    // Handle empty database case
                    if (json.debug && json.debug.message === 'No sales found in database') {

                        // Show helpful message to user
                        if (typeof toastr !== 'undefined') {
                            toastr.info(
                                'No sales found. Create your first sale using POS or Add Sale!',
                                'Getting Started', {
                                    timeOut: 8000,
                                    extendedTimeOut: 4000
                                });
                        }

                        return [];
                    }


                    // Standard DataTable server-side response
                    if (json.data && Array.isArray(json.data)) {
                        return json.data;
                    }

                    return [];
                },
                error: function(xhr, error, code) {

                    let errorMessage = 'Failed to load sales data';
                    if (xhr.status === 401) {
                        errorMessage = 'Authentication required. Please login again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error occurred';
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            },
            columns: [{
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Action',
                    render: function(data, type, row) {
                        return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="feather-menu"></i> Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><button type="button" value="${row.id}" class="view-details dropdown-item"><i class="feather-eye text-info"></i> View</button></li>
                                @can('edit sale')
                                <li><button type="button" value="${row.id}" class="edit_btn dropdown-item"><i class="feather-edit text-info"></i> Edit</button></li>
                                @endcan
                                <li><button type="button" value="${row.id}" class="delete_btn dropdown-item"><i class="feather-trash-2 text-danger"></i> Delete</button></li>
                                <li><button type="button" value="${row.id}" class="add-payment dropdown-item"><i class="feather-dollar-sign text-success"></i> Add Payment</button></li>
                                <li><button type="button" value="${row.id}" class="view-payments dropdown-item"><i class="feather-list text-primary"></i> View Payments</button></li>
                                <li><button type="button" value="${row.id}" class="sell-return dropdown-item"><i class="feather-rotate-ccw text-warning"></i> Sell Return</button></li>
                            </ul>
                        </div>`;
                    }
                },
                {
                    data: null,
                    name: 'sales_date',
                    title: 'Date',
                    render: function(data, type, row) {
                        return row.sales_date || 'N/A';
                    }
                },
                {
                    data: null,
                    name: 'id',
                    title: 'Invoice No.',
                    render: function(data, type, row) {
                        return row.invoice_no || row.id || 'N/A';
                    }
                },
                {
                    data: null,
                    name: 'customer_name',
                    title: 'Customer Name',
                    render: function(data, type, row) {
                        if (row.customer) {
                            // Handle both new format (name) and old format (first_name + last_name)
                            if (row.customer.name) {
                                return row.customer.name;
                            } else if (row.customer.first_name || row.customer.last_name) {
                                return ((row.customer.first_name || '') + ' ' + (row.customer
                                    .last_name || '')).trim();
                            }
                        }
                        return 'N/A';
                    }
                },
                {
                    data: null,
                    name: 'customer_phone',
                    title: 'Phone',
                    render: function(data, type, row) {
                        if (row.customer) {
                            return row.customer.phone || row.customer.mobile_no || 'N/A';
                        }
                        return 'N/A';
                    }
                },
                {
                    data: null,
                    name: 'location_name',
                    title: 'Location',
                    render: function(data, type, row) {
                        return row.location ? row.location.name : 'N/A';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Payment Status',
                    render: function(data, type, row) {
                        // Use payment status from database (don't recalculate)
                        let paymentStatus = row.payment_status || 'Due';

                        // Ensure proper capitalization
                        paymentStatus = paymentStatus.charAt(0).toUpperCase() + paymentStatus
                            .slice(1).toLowerCase();

                        let badgeClass = paymentStatus === 'Paid' ? 'bg-success' :
                            paymentStatus === 'Partial' ? 'bg-warning' : 'bg-danger';

                        return `<span class="badge ${badgeClass}">${paymentStatus}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Payment Method',
                    render: function(data, type, row) {
                        if (row.payments && row.payments.length > 0) {
                            // If multiple payments, show all methods
                            if (row.payments.length > 1) {
                                let methods = row.payments.map(p => p.method).join(', ');
                                return `<span title="${methods}">${methods}</span>`;
                            } else {
                                // Single payment method
                                return row.payments[0].method || 'N/A';
                            }
                        }
                        return 'N/A';
                    }
                },
                {
                    data: null,
                    name: 'final_total',
                    title: 'Total Amount',
                    render: function(data, type, row) {
                        let finalTotal = typeof row.final_total === 'string' ?
                            row.final_total :
                            parseFloat(row.final_total || 0).toFixed(2);
                        return finalTotal;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Paid Amount',
                    render: function(data, type, row) {
                        let totalPaid = 0;
                        if (row.payments && row.payments.length > 0) {
                            totalPaid = row.payments.reduce((sum, payment) => {
                                let amount = typeof payment.amount === 'string' ?
                                    parseFloat(payment.amount.replace(/,/g, '')) :
                                    parseFloat(payment.amount || 0);
                                return sum + amount;
                            }, 0);
                        }
                        return totalPaid.toFixed(2);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Due Amount',
                    render: function(data, type, row) {
                        // Use total_due from database (don't recalculate)
                        let dueAmount = typeof row.total_due === 'string' ?
                            parseFloat(row.total_due.replace(/,/g, '')) :
                            parseFloat(row.total_due || 0);
                        return Math.max(0, dueAmount).toFixed(2);
                    }
                },
                {
                    data: null,
                    name: 'status',
                    title: 'Status',
                    render: function(data, type, row) {
                        return row.status || 'N/A';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    title: 'Total Items',
                    render: function(data, type, row) {
                        // Use total_items from database (don't recalculate)
                        return row.total_items || 0;
                    }
                },
                {
                    data: null,
                    name: 'user_name',
                    title: 'Added By',
                    render: function(data, type, row) {
                        if (row.user) {
                            return row.user.name || row.user.user_name || 'N/A';
                        }
                        return 'N/A';
                    }
                }
            ],
            pageLength: 10, // STRICT default to 10
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            order: [
                [1, 'desc']
            ], // Order by sales_date descending
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"t>><"row"<"col-sm-5"i><"col-sm-7"p>>',
            lengthChange: true, // Enable length change dropdown
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pagingType: "simple_numbers", // Use simple pagination with numbers
            stateSave: false, // Disable state saving to prevent conflicts
            language: {
                processing: '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading sales data...</div>',
                emptyTable: 'No sales records found',
                info: 'Showing _START_ to _END_ of _TOTAL_ sales',
                infoEmpty: 'No sales to show',
                loadingRecords: 'Loading...',
                search: 'Search sales:',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            }
        });

        var saleValidationOptions = {
            rules: {
                customer_id: {
                    required: true
                },
                location_id: {
                    required: true
                },
                sales_date: {
                    required: true
                },
                status: {
                    required: true
                },

            },
            messages: {
                customer_id: {
                    required: "Customer is required"
                },
                location_id: {
                    required: "Location is required"
                },
                sales_date: {
                    required: "Sales Date is required"
                },
                status: {
                    required: "Status is required"
                },

            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.is("select")) {
                    error.addClass('text-danger');
                    error.insertAfter(element.closest('div'));
                } else if (element.is(":checkbox")) {
                    error.addClass('text-danger');
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to forms
        $('#addSalesForm').validate(saleValidationOptions);

        // Show Image Preview
        $(".show-file").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                if (file.type === "application/pdf") {
                    reader.onload = function(e) {
                        $("#pdfViewer").attr("src", e.target.result);
                        $("#pdfViewer").show();
                        $("#selectedImage").hide();
                    };
                } else if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#selectedImage").attr("src", e.target.result);
                        $("#selectedImage").show();
                        $("#pdfViewer").hide();
                    };
                }

                reader.readAsDataURL(file);
            }
        });

        // Add DataTable event handlers
        table.on('draw.dt', function() {
            console.log('DataTable draw completed');
        });

        table.on('error.dt', function(e, settings, techNote, message) {
            console.error('DataTable error:', message, techNote);
        });

        // Enhanced pagination event tracking
        table.on('length.dt', function(e, settings, len) {
            console.log('=== Page Length Changed ===');
            console.log('New length:', len);
            console.log('Previous page info:', table.page.info());
            console.log('Table will reload with', len, 'entries per page');

            // Force immediate reload to ensure strict compliance
            setTimeout(() => {
                console.log('After length change - page info:', table.page.info());
            }, 500);
            console.log('===========================');
        });

        // Track page changes (Next/Previous button clicks)
        table.on('page.dt', function(e, settings) {
            console.log('=== Page Changed ===');
            console.log('New page info:', table.page.info());
            console.log('Current page:', table.page());
            console.log('Page length:', table.page.len());
            console.log('==================');
        });

        // Also listen for changes on the length select element directly
        $(document).on('change', 'select[name="salesTable_length"]', function() {
            const selectedValue = $(this).val();
            console.log('=== Length Selector Direct Change ===');
            console.log('Selected value:', selectedValue);
            console.log('Current page info before change:', table.page.info());

            // Force table refresh with new length
            setTimeout(() => {
                console.log('Page info after change:', table.page.info());
            }, 1000);
        });

        // Debug: Watch for any select changes in the DataTable wrapper
        $(document).on('change', '.dataTables_wrapper select', function() {
            console.log('Any DataTable select changed:', $(this).attr('name'), 'to:', $(this).val());
        });

        // Auto-select filters from URL parameters after Select2 initialization
        function applyURLFilters() {
            console.log('=== Applying URL Filters ===');
            
            if (urlCustomerId) {
                console.log('Setting customer filter to:', urlCustomerId);
                
                // Check if Select2 is initialized
                if ($('#customerFilter').hasClass('select2-hidden-accessible')) {
                    // Select2 is initialized, use Select2 API
                    $('#customerFilter').val(urlCustomerId).trigger('change.select2');
                    console.log('Customer filter set via Select2');
                } else {
                    // Fallback to regular select
                    $('#customerFilter').val(urlCustomerId).trigger('change');
                    console.log('Customer filter set via regular select');
                }
            }
            
            if (urlLocationId) {
                console.log('Setting location filter to:', urlLocationId);
                
                if ($('#locationFilter').hasClass('select2-hidden-accessible')) {
                    $('#locationFilter').val(urlLocationId).trigger('change.select2');
                    console.log('Location filter set via Select2');
                } else {
                    $('#locationFilter').val(urlLocationId).trigger('change');
                    console.log('Location filter set via regular select');
                }
            }
            
            console.log('=== URL Filters Applied ===');
        }
        
        // Wait for Select2 to initialize, then apply filters
        // Try multiple times with increasing delays to ensure Select2 is ready
        setTimeout(applyURLFilters, 800);
        setTimeout(applyURLFilters, 1500);
        setTimeout(applyURLFilters, 2500);

        // Add filter change handlers to refresh table
        $('#customerFilter, #locationFilter, #userFilter, #paymentStatusFilter, #paymentMethodFilter, #dateRangeFilter')
            .on('change', function() {
                console.log('Filter changed:', $(this).attr('id'), 'to:', $(this).val());
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().ajax.reload();
                }
            });

        // Global function to refresh sales data (can be called from payment success callbacks)
        window.refreshSalesTable = function() {
            if ($.fn.DataTable.isDataTable('#salesTable')) {
                $('#salesTable').DataTable().ajax.reload(null, false); // false = stay on current page
                console.log('Sales table refreshed after payment update');
            }
        };

        // Legacy support for existing code
        window.fetchSalesData = function() {
            window.refreshSalesTable();
        };

        // Ensure pagination buttons are properly clickable
        $(document).on('click', '.paginate_button:not(.disabled)', function(e) {
            console.log('Pagination button clicked:', $(this).text(), 'Class:', $(this).attr('class'));
        });

        // Debug: Check if length selector exists after DataTable initialization
        setTimeout(() => {
            const lengthSelector = $('select[name="salesTable_length"]');
            console.log('=== DataTable Length Selector Debug ===');
            console.log('Length selector found:', lengthSelector.length > 0);
            if (lengthSelector.length > 0) {
                console.log('Current value:', lengthSelector.val());
                console.log('Available options:', lengthSelector.find('option').map((i, el) => $(el)
                    .val()).get());
                console.log('Element HTML:', lengthSelector[0].outerHTML);
            } else {
                console.log('Length selector not found! DataTable wrapper:', $('.dataTables_wrapper')
                    .length);
                console.log('All selects in wrapper:', $('.dataTables_wrapper select').length);
            }
            console.log('=====================================');
        }, 1500);

        // Simple debug functions for testing
        window.debugSalesTable = function() {
            const info = table.page.info();
            console.log('DataTable Info:', info);
            console.log('Page length:', table.page.len());
            alert(
                `Page: ${info.page + 1} of ${info.pages}\nRecords: ${info.start + 1} to ${info.end} of ${info.recordsTotal}\nPage Length: ${table.page.len()}`);
        };

        window.testPageLength = function(length) {
            table.page.len(length).draw();
        };

        // DataTable initialization completed above - no additional code needed

        // Initialize modal event handlers
        $(document).on('click', '[data-bs-dismiss="modal"]', function() {
            const modalId = $(this).closest('.modal').attr('id');
            if (modalId) {
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                if (modal) {
                    modal.hide();
                } else {
                    $('#' + modalId).modal('hide');
                }
            }
        });

        // Ensure modal backdrop and scroll issues are handled
        $(document).on('hidden.bs.modal', '.modal', function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });

        // Show the modal when the button is clicked
        $('#bulkPaymentBtn').click(function() {
            $('#bulkPaymentModal').modal('show');

            // Load customers when modal opens (reuse the same function)
            loadCustomersForBulkPayment();
        });

        let originalOpeningBalance = 0; // Store the actual customer opening balance
        let saleDueAmount = 0; // Store sale due amount
        let totalDueAmount = 0; // Store total due amount

        // Use event delegation to handle customer select changes (works for both modal and separate page)
        $(document).on('change', '#customerSelect', function() {
            var customerId = $(this).val();
            var selectedOption = $(this).find(':selected');

            originalOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
            saleDueAmount = parseFloat(selectedOption.data('sale-due')) || 0;
            totalDueAmount = parseFloat(selectedOption.data('total-due')) || 0;

            // Display balance breakdown with proper currency formatting
            $('#openingBalance').text('Rs. ' + originalOpeningBalance.toFixed(2));
            // Sale due balance removed - using totalDueAmount instead
            $('#totalCustomerDue').text('Rs. ' + totalDueAmount.toFixed(2));

            // Reset and clear previous validation errors
            $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
            $('#globalPaymentAmount').val('');

            // Load sales data and update UI based on payment type
            loadSalesData(customerId);
        });

        // Handle payment type selection
        $('input[name="paymentType"]').change(function() {
            var paymentType = $(this).val();
            var customerId = $('#customerSelect').val();

            if (paymentType === 'opening_balance') {
                $('#salesListContainer').hide();
                $('#globalPaymentAmount').attr('max', originalOpeningBalance);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + originalOpeningBalance
                    .toFixed(2));
            } else if (paymentType === 'sale_dues') {
                $('#salesListContainer').show();
                $('#globalPaymentAmount').attr('max', saleDueAmount);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + saleDueAmount.toFixed(2));
            } else if (paymentType === 'both') {
                $('#salesListContainer').show();
                $('#globalPaymentAmount').attr('max', totalDueAmount);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));
            }

            if (customerId) {
                loadSalesData(customerId);
            }
        });

        // Function to load sales data
        function loadSalesData(customerId) {

            $.ajax({
                url: '/sales',
                type: 'GET',
                dataType: 'json',
                data: {
                    customer_id: customerId
                }, // Ensure customer_id is sent
                success: function(response) {
                    var salesTable = $('#salesList').DataTable();
                    salesTable.clear().draw(); // Clear the table before adding new data
                    var totalSalesAmount = 0,
                        totalPaidAmount = 0,
                        totalDueAmount = 0;

                    console.log('Sales response:', response); // Debug log

                    if (response.sales && response.sales.length > 0) {
                        response.sales.forEach(function(sale) {
                            if (sale.customer_id == customerId && sale.status ===
                                'final') { // Only final sales
                                var finalTotal = parseFloat(sale.final_total) || 0;
                                var totalPaid = parseFloat(sale.total_paid) || 0;
                                var totalDue = parseFloat(sale.total_due) || 0;

                                // Include all sales (both paid and due) in totals
                                totalSalesAmount += finalTotal;
                                totalPaidAmount += totalPaid;
                                totalDueAmount += totalDue;

                                // Only add to table if there's a due amount
                                if (totalDue > 0) {
                                    salesTable.row.add([
                                        sale.id + " (" + sale.invoice_no + ")",
                                        finalTotal.toFixed(2),
                                        totalPaid.toFixed(2),
                                        totalDue.toFixed(2),
                                        '<input type="number" class="form-control reference-amount" data-reference-id="' +
                                        sale.id + '" min="0" max="' + totalDue +
                                        '" step="0.01" placeholder="0.00" value="0">'
                                    ]).draw();
                                }
                            }
                        });
                    }

                    console.log('Totals calculated:', {
                        totalSalesAmount: totalSalesAmount,
                        totalPaidAmount: totalPaidAmount,
                        totalDueAmount: totalDueAmount
                    });

                    // Update the display totals
                    $('#totalSalesAmount').text('Rs. ' + totalSalesAmount.toFixed(2));
                    $('#totalPaidAmount').text('Rs. ' + totalPaidAmount.toFixed(2));
                    $('#totalDueAmount').text('Rs. ' + totalDueAmount.toFixed(2));

                    // Store totalDueAmount for validation purposes
                    window.saleDueAmount = totalDueAmount;

                    // Update individual payment total
                    updateIndividualPaymentTotal();
                },
                error: function(xhr, status, error) {
                    console.error("Error loading sales data: ", status, error);
                }
            });
        }

        // Use event delegation for global payment amount input (works for both modal and separate page)
        $(document).on('input', '#globalPaymentAmount', function() {
            var globalAmount = parseFloat($(this).val()) || 0;
            var customerOpeningBalance = originalOpeningBalance; // Always use original balance
            var totalDueAmount = parseFloat($('#totalDueAmount').text()) || 0;
            var remainingAmount = globalAmount;
            var paymentType = $('input[name="paymentType"]:checked').val();

            // Validate global amount based on payment type
            var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;
            var maxAmount = 0;
            if (paymentType === 'opening_balance') {
                maxAmount = customerOpeningBalance;
            } else if (paymentType === 'sale_dues') {
                maxAmount = totalCustomerDue; // Allow payment up to total customer due
            } else if (paymentType === 'both') {
                maxAmount = totalCustomerDue; // Same as total customer due
            }

            // Clear existing validation feedback first
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            if (globalAmount > maxAmount) {
                $(this).addClass('is-invalid').after(
                    '<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>'
                );
                return;
            }

            // Handle payment distribution based on payment type
            if (paymentType === 'opening_balance') {
                // Only apply to opening balance
                let newOpeningBalance = Math.max(0, customerOpeningBalance - remainingAmount);
                $('#openingBalance').text(newOpeningBalance.toFixed(2));

                // Clear all sales payment inputs
                $('.reference-amount').val(0);

            } else if (paymentType === 'sale_dues') {
                // Only apply to sales dues in order
                $('.reference-amount').each(function() {
                    var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)')
                    .text()) || 0;
                    if (remainingAmount > 0 && referenceDue > 0) {
                        var paymentAmount = Math.min(remainingAmount, referenceDue);
                        $(this).val(paymentAmount.toFixed(2));
                        remainingAmount -= paymentAmount;
                    } else {
                        $(this).val(0);
                    }
                });

                // Don't change opening balance
                $('#openingBalance').text(customerOpeningBalance.toFixed(2));

            } else if (paymentType === 'both') {
                // First deduct from opening balance
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
                $('#openingBalance').text(newOpeningBalance.toFixed(2));

                // Then apply remaining amount to sales in order
                $('.reference-amount').each(function() {
                    var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)')
                    .text()) || 0;
                    if (remainingAmount > 0 && referenceDue > 0) {
                        var paymentAmount = Math.min(remainingAmount, referenceDue);
                        $(this).val(paymentAmount.toFixed(2));
                        remainingAmount -= paymentAmount;
                    } else {
                        $(this).val(0);
                    }
                });
            }

            // Update the individual payment total display
            updateIndividualPaymentTotal();
        });

        // Function to update individual payment total
        function updateIndividualPaymentTotal() {
            var total = 0;
            $('.reference-amount').each(function() {
                var amount = parseFloat($(this).val()) || 0;
                total += amount;
            });
            $('#individualPaymentTotal').text('Rs. ' + total.toFixed(2));
        }

        // Validate individual payment amounts and update total
        $(document).on('input', '.reference-amount', function() {
            var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
            var paymentAmount = parseFloat($(this).val()) || 0;

            // Clear existing validation feedback first
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            if (paymentAmount > referenceDue) {
                $(this).addClass('is-invalid').after(
                    '<span class="invalid-feedback d-block">Amount exceeds total due.</span>'
                );
            }

            // Update individual payment total whenever amount changes
            updateIndividualPaymentTotal();
        });

        // Function to update the opening balance
        function updateOpeningBalance() {
            var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
            var customerOpeningBalance = parseFloat($('#customerSelect').find(':selected').data(
                'opening-balance')) || 0;
            var totalPayment = 0;

            // Calculate the total payment from individual amounts
            $('.reference-amount').each(function() {
                totalPayment += parseFloat($(this).val()) || 0;
            });

            var remainingAmount = globalAmount - totalPayment;

            // Adjust the opening balance based on the remaining amount
            if (remainingAmount >= 0) {
                $('#openingBalance').text((customerOpeningBalance - remainingAmount).toFixed(2));
            } else {
                $('#openingBalance').text("0.00");
            }
        }

        // Handle global payment amount input
        $('#globalPaymentAmount').change(function() {
            updateOpeningBalance();
        });

        // Function to load customers for bulk payment
        // Debug: Check if this script is loading
        console.log('sales_ajax.blade.php script is loading...');

        // Make this function globally available
        window.loadCustomersForBulkPayment = function() {
            console.log('Loading customers for bulk payment...');
            console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
            console.log('Current user authenticated:', $('meta[name="csrf-token"]').length > 0);

            $.ajax({
                url: '/customer-get-all',
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    console.log('Customer response for bulk payment:', response);
                    var customerSelect = $('#customerSelect');
                    customerSelect.empty();
                    customerSelect.append(
                        '<option value="" selected disabled>Select Customer</option>');

                    if (response.status === 200 && response.message && response.message.length >
                        0) {
                        response.message.forEach(function(customer) {
                            // Skip walk-in customer (customer ID 1)
                            if (customer.id === 1) {
                                return;
                            }

                            // Calculate total due amount
                            var openingBalance = parseFloat(customer.opening_balance) ||
                                0;
                            var saleDue = parseFloat(customer.total_sale_due) || 0;
                            var currentDue = parseFloat(customer.current_due) || 0;

                            // Only show customers who have due amounts
                            if (currentDue > 0) {
                                var lastName = customer.last_name ? customer.last_name :
                                    '';
                                var fullName = (customer.first_name || '') + (lastName ?
                                    ' ' + lastName : '');
                                var displayText = fullName + ' (Due: Rs. ' + currentDue
                                    .toFixed(2) + ')';
                                if (openingBalance > 0) {
                                    displayText += ' [Opening: Rs. ' + openingBalance
                                        .toFixed(2) + ']';
                                }

                                customerSelect.append(
                                    '<option value="' + customer.id +
                                    '" data-opening-balance="' + openingBalance +
                                    '" data-sale-due="' + saleDue +
                                    '" data-total-due="' + currentDue +
                                    '">' + displayText + '</option>'
                                );
                            }
                        });
                    } else {
                        console.error("Failed to fetch customer data or no customers found.",
                            response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error loading customers:", {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });

                    // Show user-friendly error message
                    var errorMessage = 'Failed to load customers.';
                    if (xhr.status === 401) {
                        errorMessage =
                            'Authentication required. Please refresh the page and login again.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Permission denied to access customer data.';
                    }

                    $('#customerSelect').append('<option value="" disabled>Error: ' +
                        errorMessage + '</option>');
                }
            });
        }

        // Debug: Confirm function is defined
        console.log('loadCustomersForBulkPayment function defined successfully');
        console.log('Type of window.loadCustomersForBulkPayment:', typeof window.loadCustomersForBulkPayment);

        // Initialize DataTable and Bulk Payment functionality
        $(document).ready(function() {
            console.log('Document ready - checking for customerSelect element...');
            console.log('Current URL:', window.location.href);
            console.log('CustomerSelect element found:', $('#customerSelect').length > 0);

            // Initialize Select2 when bulk payment modal is shown
            $('#bulkPaymentModal').on('shown.bs.modal', function() {
                console.log('Bulk payment modal shown - initializing Select2...');
                
                // Destroy existing Select2 instance if exists
                if ($('#customerSelect').hasClass('select2-hidden-accessible')) {
                    $('#customerSelect').select2('destroy');
                }
                
                // Initialize select2 for customer dropdown with proper settings
                $('#customerSelect').select2({
                    placeholder: "Select Customer",
                    allowClear: true,
                    dropdownParent: $('#bulkPaymentModal'), // Ensure dropdown renders inside modal
                    width: '100%' // Proper width alignment
                });
                
                console.log('Select2 initialized for customerSelect in modal');
                
                // Add event listener to ensure search input gets focus when dropdown opens
                $('#customerSelect').on('select2:open', function() {
                    setTimeout(function() {
                        document.querySelector('.select2-search__field').focus();
                    }, 100);
                });
                
                // Set today's date as default for "Paid On" field
                var today = new Date();
                var todayFormatted = today.getFullYear() + '-' + 
                    String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(today.getDate()).padStart(2, '0');
                $('#paidOn').val(todayFormatted);
                console.log('Set default date to today:', todayFormatted);
                
                // Load customers after Select2 is initialized
                loadCustomersForBulkPayment();
            });

            $('#salesList').DataTable({
                columns: [{
                        title: "ID (Invoice)"
                    },
                    {
                        title: "Final Total"
                    },
                    {
                        title: "Total Paid"
                    },
                    {
                        title: "Total Due"
                    },
                    {
                        title: "Payment"
                    }
                ]
            });

            // Handle payment method change
            $('#paymentMethod').on('change', function() {
                togglePaymentFields('bulkPaymentModal');
            });

            // Initialize payment fields when modal is shown
            $('#bulkPaymentModal').on('shown.bs.modal', function() {
                togglePaymentFields('bulkPaymentModal');
            });

            // Clear the modal content when the modal is hidden
            $('#bulkPaymentModal').on('hidden.bs.modal', function() {
                // Destroy Select2 instance before clearing
                if ($('#customerSelect').hasClass('select2-hidden-accessible')) {
                    $('#customerSelect').select2('destroy');
                }
                
                $('#bulkPaymentForm')[0].reset(); // Reset the form
                $('#customerSelect').val('').trigger('change'); // Clear customer selection
                $('#salesList').DataTable().clear().draw(); // Clear the sales list
                $('#openingBalance').text('Rs. 0.00'); // Reset the opening balance
                // Sale due balance removed - only using totalDueAmount
                $('#totalSalesAmount').text('Rs. 0.00'); // Reset the total sales amount
                $('#totalPaidAmount').text('Rs. 0.00'); // Reset the total paid amount
                $('#totalDueAmount').text('Rs. 0.00'); // Reset the total due amount
                $('#totalCustomerDue').text('Rs. 0.00'); // Reset total customer due
                $('#individualPaymentTotal').text('Rs. 0.00'); // Reset individual payment total
                $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback')
                    .remove(); // Remove validation feedback
                $('#paymentMethod').val('cash'); // Reset payment method to cash
                togglePaymentFields('bulkPaymentModal'); // Reset payment fields
                $('input[name="paymentType"][value="sale_dues"]').prop('checked',
                true); // Reset to sale dues
                $('#salesListContainer').show(); // Show sales list by default
                togglePaymentFields('bulkPaymentModal'); // Ensure the correct fields are shown
            });
        });
        // Handle payment submission (works for both modal and separate page)
        $(document).on('click', '#submitBulkPayment', function() {
            console.log('Submit bulk payment button clicked');

            var customerId = $('#customerSelect').val();
            var paymentMethod = $('#paymentMethod').val();

            console.log('Customer ID:', customerId);
            console.log('Payment Method raw:', paymentMethod);
            // Get payment date - there might be multiple inputs with same ID, get the one with value
            var paymentDate = '';
            $('#paidOn, [name="payment_date"]').each(function() {
                var val = $(this).val();
                if (val && val.trim() !== '') {
                    paymentDate = val;
                    console.log('Found date value in input type:', this.type, 'Value:', val);
                    return false; // Break the loop
                }
            });

            // If still empty, try to get from visible date inputs or set today's date
            if (!paymentDate) {
                paymentDate = $('input[type="date"]:visible').val();
                console.log('Trying visible date input, payment_date:', paymentDate);
            }

            // If still empty, set today's date
            if (!paymentDate) {
                var today = new Date();
                paymentDate = today.getFullYear() + '-' +
                    String(today.getMonth() + 1).padStart(2, '0') + '-' +
                    String(today.getDate()).padStart(2, '0');
                console.log('Using today\'s date:', paymentDate);
            }

            console.log('Raw payment date from input:', paymentDate);
            console.log('Payment date input element exists:', $('#paidOn').length > 0);
            console.log('Payment date input is visible:', $('#paidOn').is(':visible'));
            console.log('Payment date input element:', $('#paidOn')[0]);
            console.log('Payment date input data-date:', $('#paidOn').data('date'));
            console.log('All form inputs with values:');
            $('#paidOn, [name="payment_date"]').each(function() {
                console.log('Input:', this.id || this.name, 'Value:', $(this).val(), 'Type:',
                    this.type);
            });

            // Convert date format from DD-MM-YYYY to YYYY-MM-DD if needed
            if (paymentDate && paymentDate.includes('-')) {
                var dateParts = paymentDate.split('-');
                if (dateParts.length === 3 && dateParts[0].length === 2) {
                    // Assume DD-MM-YYYY format, convert to YYYY-MM-DD
                    paymentDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                }
            }
            var globalPaymentAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
            var paymentType = $('input[name="paymentType"]:checked').val();

            console.log('Global payment amount raw value:', $('#globalPaymentAmount').val());
            console.log('Global payment amount parsed:', globalPaymentAmount);
            console.log('Global amount element exists:', $('#globalPaymentAmount').length > 0);
            console.log('Global amount element is visible:', $('#globalPaymentAmount').is(':visible'));
            console.log('All amount inputs on page:');
            $('[id*="Amount"], [name*="amount"], input[type="number"]').each(function() {
                console.log('Amount input:', this.id || this.name || 'unnamed', 'Value:', $(
                    this).val(), 'Visible:', $(this).is(':visible'));
            });
            var salePayments = [];

            console.log('Form values collected:', {
                customerId: customerId,
                paymentMethod: paymentMethod,
                paymentDate: paymentDate,
                globalPaymentAmount: globalPaymentAmount,
                paymentType: paymentType
            });

            console.log('Date after conversion:', paymentDate);

            // Validate customer selection
            if (!customerId) {
                console.error('Validation failed: No customer selected');
                return;
            }

            // Validate payment amount based on payment type
            var maxAmount = 0;
            var customerOpeningBalance = parseFloat($('#customerSelect').find(':selected').data(
                'opening-balance')) || 0;
            var totalDueAmount = parseFloat($('#totalDueAmount').text().replace('Rs. ', '')) || 0;
            var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;

            console.log('Balance details:', {
                customerOpeningBalance: customerOpeningBalance,
                totalDueAmount: totalDueAmount, // This is sale dues only
                totalCustomerDue: totalCustomerDue // This is total customer due (sales + opening balance)
            });

            console.log('All balance elements on page:');
            $('#totalCustomerDue, #totalDueAmount, #saleDueAmount').each(function() {
                console.log('Element:', this.id, 'Text:', $(this).text(), 'Parsed:', parseFloat(
                    $(this).text().replace('Rs. ', '')));
            });

            if (paymentType === 'opening_balance') {
                maxAmount = customerOpeningBalance;
            } else if (paymentType === 'sale_dues') {
                // Allow payment up to total customer due even when "sale_dues" is selected
                // The backend will intelligently distribute the payment
                maxAmount = totalCustomerDue;
            } else if (paymentType === 'both') {
                maxAmount = totalCustomerDue;
            }

            // Check if user is doing individual payments or global payment
            var hasIndividualPayments = false;
            var totalIndividualAmount = 0;
            $('.reference-amount').each(function() {
                var amount = parseFloat($(this).val()) || 0;
                if (amount > 0) {
                    hasIndividualPayments = true;
                    totalIndividualAmount += amount;
                }
            });

            console.log('Payment validation:', {
                customerId: customerId,
                globalPaymentAmount: globalPaymentAmount,
                hasIndividualPayments: hasIndividualPayments,
                totalIndividualAmount: totalIndividualAmount,
                paymentType: paymentType,
                paymentDate: paymentDate,
                paymentMethod: paymentMethod,
                maxAmount: maxAmount
            });

            // If no individual payments, require global payment amount
            if (!hasIndividualPayments && globalPaymentAmount <= 0) {
                console.error('Validation failed: No payment amounts entered');
                return;
            }

            // If global payment amount is provided, validate it
            if (globalPaymentAmount > 0 && globalPaymentAmount > maxAmount) {
                console.error('Validation failed: Payment amount exceeds total customer due', {
                    globalAmount: globalPaymentAmount,
                    maxAmount: maxAmount,
                    paymentType: paymentType,
                    saleDues: totalDueAmount,
                    totalCustomerDue: totalCustomerDue
                });
                return;
            }

            // Validate payment type
            if (!paymentType) {
                console.error('Validation failed: No payment type selected');
                return;
            }

            // Validate payment date
            if (!paymentDate) {
                console.error('Validation failed: No payment date selected');
                return;
            }

            // For sale dues and both, collect individual sale payments
            if (paymentType === 'sale_dues' || paymentType === 'both') {
                $('.reference-amount').each(function() {
                    var referenceId = $(this).data('reference-id');
                    var paymentAmount = parseFloat($(this).val()) || 0;
                    if (paymentAmount > 0) {
                        salePayments.push({
                            reference_id: referenceId,
                            amount: paymentAmount
                        });
                    }
                });

                // Validate that at least one sale payment is made for sale_dues type
                if (paymentType === 'sale_dues' && salePayments.length === 0) {
                    console.error('Validation failed: No sale payments for sale_dues type');
                    return;
                }
            }

            var paymentData = {
                entity_type: 'customer',
                entity_id: customerId,
                payment_method: paymentMethod,
                payment_date: paymentDate,
                global_amount: globalPaymentAmount || 0,
                payment_type: paymentType,
                payments: salePayments
            };

            // Add payment method specific fields
            if (paymentMethod === 'card') {
                paymentData.card_number = $('#cardNumber').val();
                paymentData.card_holder_name = $('#cardHolderName').val();
                paymentData.card_type = $('#cardType').val();
                paymentData.card_expiry_month = $('#expiryMonth').val();
                paymentData.card_expiry_year = $('#expiryYear').val();
                paymentData.card_security_code = $('#securityCode').val();
            } else if (paymentMethod === 'cheque') {
                paymentData.cheque_number = $('#chequeNumber').val();
                paymentData.cheque_bank_branch = $('#bankBranch').val();
                paymentData.cheque_received_date = $('#cheque_received_date').val();
                paymentData.cheque_valid_date = $('#cheque_valid_date').val();
                paymentData.cheque_given_by = $('#cheque_given_by').val();
            } else if (paymentMethod === 'bank_transfer') {
                paymentData.bank_account_number = $('#bankAccountNumber').val();
            }

            console.log('Payment data being sent:', paymentData);

            $.ajax({
                url: '/api/submit-bulk-payment',
                type: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify(paymentData),
                success: function(response) {
                    console.log('Payment submission successful:', response);
                    toastr.success(response.message, 'Payment Submitted');
                    $('#bulkPaymentModal').modal('hide');
                    $('#bulkPaymentForm')[0].reset(); // Reset the form

                    // Refresh the DataTable to show updated payment information
                    refreshSalesTable();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });

                    console.error('Payment submission failed:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseJSON: xhr.responseJSON,
                        responseText: xhr.responseText
                    });

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        console.error('Validation errors:', xhr.responseJSON.errors);
                    }
                }
            });
        });



        // Event handler for view details button
        $('#salesTable tbody').on('click', 'button.view-details', function() {
            var saleId = $(this).val();
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;
                        const location = saleDetails.location;
                        const products = saleDetails.products;

                        // Store sale ID in modal data attribute for print function
                        $('#saleDetailsModal').data('sale-id', saleId);

                        // Populate modal fields
                        $('#modalTitle').text('Sale Details - Invoice No: ' + saleDetails
                            .invoice_no);
                        $('#customerDetails').text((customer?.first_name || 'N/A') + ' ' + (
                            customer?.last_name || ''));
                        $('#locationDetails').text(location.name);
                        $('#salesDetails').text('Date: ' + saleDetails.sales_date +
                            ', Status: ' + saleDetails.status);

                        // Populate products table
                        const productsTableBody = $('#productsTable tbody');
                        productsTableBody.empty();
                        if (products && Array.isArray(products)) {
                            products.forEach((product, index) => {
                                const productRow = $('<tr>');
                                productRow.append('<td>' + (index + 1) + '</td>');
                                productRow.append('<td>' + product.product
                                    .product_name + '</td>');
                                productRow.append('<td>' + product.product.sku +
                                    '</td>');
                                productRow.append('<td>' + product.quantity +
                                    '</td>');
                                productRow.append('<td>' + product.price + '</td>');
                                productRow.append('<td>' + (product.quantity *
                                    product.price).toFixed(2) + '</td>');
                                productsTableBody.append(productRow);
                            });
                        }

                        // Populate payment info table
                        const paymentInfoTableBody = $('#paymentInfoTable tbody');
                        paymentInfoTableBody.empty();
                        if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                            saleDetails.payments.forEach((payment) => {
                                const paymentRow = $('<tr>');
                                paymentRow.append('<td>' + payment.payment_date +
                                    '</td>');
                                paymentRow.append('<td>' + payment.reference_no +
                                    '</td>');
                                paymentRow.append('<td>' + payment.amount +
                                    '</td>');
                                paymentRow.append('<td>' + payment.payment_method +
                                    '</td>');
                                paymentRow.append('<td>' + payment.notes + '</td>');
                                paymentInfoTableBody.append(paymentRow);
                            });
                        }

                        // Populate amount details table
                        const amountDetailsTableBody = $('#amountDetailsTable tbody');
                        amountDetailsTableBody.empty();
                        amountDetailsTableBody.append('<tr><td>Total Amount</td><td>' +
                            saleDetails.final_total + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Paid Amount</td><td>' +
                            saleDetails.total_paid + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Due Amount</td><td>' +
                            saleDetails.total_due + '</td></tr>');

                        // Show modal using Bootstrap 5 API
                        const saleModal = new bootstrap.Modal(document.getElementById(
                            'saleDetailsModal'));
                        saleModal.show();
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        /* Event handler for add payment button */
        $('#salesTable tbody').on('click', 'button.add-payment', function() {
            var saleId = $(this).val();
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;
                        const location = saleDetails.location;

                        // Populate payment modal fields
                        $('#paymentModalLabel').text('Add Payment - Invoice No: ' +
                            saleDetails.invoice_no);
                        $('#paymentCustomerDetail').text((customer?.first_name || 'N/A') +
                            ' ' +
                            (customer?.last_name || ''));
                        $('#paymentLocationDetails').text(location.name);
                        $('#totalAmount').text(saleDetails.final_total);
                        $('#totalPaidAmount').text(saleDetails.total_paid);

                        $('#saleId').val(saleDetails.id);
                        $('#payment_type').val('sale');
                        $('#customer_id').val(customer.id);
                        $('#reference_no').val(saleDetails.invoice_no);
                        // Set default date to today
                        $('#paidOn').val(new Date().toISOString().split('T')[0]);

                        // Set the amount field to the total due amount
                        $('#payAmount').val(saleDetails.total_due);

                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(saleDetails.total_due);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        // Event handler for view payments button
        $('#salesTable tbody').on('click', 'button.view-payments', function(event) {
            event.preventDefault();
            var saleId = $(this).val();
            $('#viewPaymentModal').data('sale-id', saleId);
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;

                        // Populate view payments modal fields
                        $('#viewPaymentModalLabel').text('View Payments ( Reference No: ' +
                            saleDetails.invoice_no + ' )');
                        $('#viewCustomerDetail').text((customer?.first_name || 'N/A') +
                            ' ' + (customer?.last_name || ''));
                        $('#viewBusinessDetail').text(saleDetails.location.name);
                        $('#viewReferenceNo').text(saleDetails.invoice_no);
                        $('#viewDate').text(saleDetails.sales_date);
                        $('#viewPurchaseStatus').text(saleDetails.status);
                        $('#viewPaymentStatus').text(saleDetails.payment_status);

                        const paymentsTableBody = $('#viewPaymentModal table tbody');
                        paymentsTableBody.empty();
                        if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                            saleDetails.payments.forEach((payment) => {
                                const paymentRow = $('<tr>');
                                paymentRow.append('<td>' + payment.payment_date +
                                    '</td>');
                                paymentRow.append('<td>' + payment.reference_no +
                                    '</td>');
                                paymentRow.append('<td>' + payment.amount +
                                    '</td>');
                                paymentRow.append('<td>' + payment.payment_method +
                                    '</td>');
                                paymentRow.append('<td>' + payment.notes + '</td>');
                                paymentRow.append('<td>' + 'Account Name' +
                                    '</td>'); // Replace with actual account name
                                paymentRow.append(
                                    '<td><button type="button" value="' +
                                    payment.id +
                                    '" class="btn btn-outline-warning btn-sm edit-payment"><i class="feather-edit text-warning me-1"></i>Edit</button></td>'
                                );
                                paymentRow.append(
                                    '<td><button type="button" value="' +
                                    payment.id +
                                    '" class="btn btn-outline-danger btn-sm delete-payment"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>'
                                );
                                paymentsTableBody.append(paymentRow);
                            });
                        } else {
                            paymentsTableBody.append(
                                '<tr><td colspan="7" class="text-center">No records found</td></tr>'
                            );
                        }

                        $('#viewPaymentModal').modal('show');
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        /* Event handler for delete payment button */
        $(document).on('click', 'button.delete-payment', function() {
            var paymentId = $(this).val();
            if (confirm('Are you sure you want to delete this payment?')) {
                $.ajax({
                    url: '/payments/' + paymentId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        toastr.success('Payment deleted successfully.', 'Deleted');
                        $('#viewPaymentModal').modal('hide');

                        // Refresh the DataTable to show updated payment information
                        refreshSalesTable();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message);
                    }
                });
            }
        });

        /* Event handler for edit payment button */
        $(document).on('click', 'button.edit-payment', function() {
            var paymentId = $(this).val();
            $.ajax({
                url: '/payments/' + paymentId,
                type: 'GET',
                success: function(response) {
                    if (response.data) {
                        const payment = response.data;
                        const customer = payment.customer || {};
                        const location = payment.location || {};

                        // Populate edit payment modal fields
                        $('#paymentModalLabel').text('Edit Payment - Reference No: ' + (
                            payment.reference_no || 'N/A'));
                        $('#paymentCustomerDetail').text((customer.first_name || 'N/A') +
                            ' ' + (customer.last_name || ''));
                        $('#paymentLocationDetails').text(location.name || 'N/A');
                        $('#totalAmount').text(payment.final_total || 'N/A');
                        $('#totalPaidAmount').text(payment.total_paid || 'N/A');

                        $('#saleId').val(payment.reference_id);
                        $('#payment_type').val(payment.payment_type);
                        $('#customer_id').val(payment.customer_id);
                        $('#reference_no').val(payment.reference_no);
                        $('#paidOn').val(payment.payment_date);
                        $('#payAmount').val(payment.amount);
                        $('#paymentNotes').val(payment.notes);

                        // Ensure the Edit Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(payment.total_due || 0);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });

                        $('#paymentModal').modal('show');
                    } else {
                        console.error('Payment data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching payment details:', error);
                }
            });
        });

        // Function to print the modal content - calls printRecentTransaction API
        window.printModal = function() {
            // Get the sale ID from the modal data attribute
            var saleId = $('#saleDetailsModal').data('sale-id');

            if (!saleId) {
                toastr.error('Unable to find sale ID for printing', 'Error');
                return;
            }

            // Call the printRecentTransaction API to get the proper receipt
            printReceipt(saleId);
        };

        // Function to print the receipt for the sale - prefer centralized implementation
        function printReceipt(saleId) {
            if (typeof window.printReceipt === 'function') {
                // If a centralized printReceipt exists (from POS file), call it
                return window.printReceipt(saleId);
            }

            // Fallback: local implementation (same as before)
            fetch(`/sales/print-recent-transaction/${saleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.invoice_html) {
                        const iframe = document.createElement('iframe');
                        iframe.style.position = 'fixed';
                        iframe.style.width = '0';
                        iframe.style.height = '0';
                        iframe.style.border = 'none';
                        document.body.appendChild(iframe);

                        iframe.contentDocument.open();
                        iframe.contentDocument.write(data.invoice_html);
                        iframe.contentDocument.close();

                        iframe.onload = function() {
                            iframe.contentWindow.print();
                            iframe.contentWindow.onafterprint = function() {
                                document.body.removeChild(iframe);
                            };
                        };
                    } else {
                        toastr.error('Failed to fetch the receipt. Please try again.', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching the receipt:', error);
                    toastr.error('An error occurred while fetching the receipt. Please try again.', 'Error');
                });
        }

        $('#savePayment').click(function() {
            var formData = new FormData($('#paymentForm')[0]);

            $.ajax({
                url: '/api/payments',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#paymentModal').modal('hide');
                    document.getElementsByClassName('successSound')[0].play();
                    toastr.success(response.message, 'Payment Added');

                    // Refresh the DataTable to show updated payment information
                    refreshSalesTable();
                },
                error: function(xhr, status, error) {
                    console.error('Error adding payment:', error);
                }
            });
        });




        $('#addSalesForm').on('submit', function(event) {
            event.preventDefault();

            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text('Processing...');

            const formData = new FormData(this);
            formData.set('sales_date', convertDateFormat($('#sales_date').val()));

            $('#addSaleProduct tbody tr').each(function(index) {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat($(this).find('.price-input').val()) || 0;
                const discount = parseFloat($(this).find('.discount-percent').val()) || 0;
                const tax = parseFloat($(this).find('.product-tax').val()) || 0;
                const priceType = $(this).find('.price-type').val() || 'retail';
                const subtotal = (quantity * unitPrice) - discount + tax;

                formData.append(`products[${index}][product_id]`, $(this).data('id'));
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][unit_price]`, unitPrice);
                formData.append(`products[${index}][discount]`, discount);
                formData.append(`products[${index}][tax]`, tax);
                formData.append(`products[${index}][subtotal]`, subtotal);
                formData.append(`products[${index}][batch_id]`, $(this).find('.batch-dropdown')
                    .val());
                formData.append(`products[${index}][price_type]`, priceType);
            });

            if (!$('#addSalesForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                submitButton.prop('disabled', false).text('Save');
                return;
            }

            const saleId = $('#sale_id').val();
            const url = saleId ? `/sales/update/${saleId}` : '/sales/store';

            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    submitButton.prop('disabled', false).text('Save');
                    if (response.message) {
                        toastr.success(response.message, 'Success');

                        // Refresh sales data for Recent Transactions
                        if (typeof fetchSalesData === 'function') {
                            fetchSalesData();
                        }

                        resetFormAndValidation();
                        window.location.href = '/list-sale';
                        if (response.invoice_html) {
                            $('#invoiceContainer').html(response.invoice_html);
                        }
                    } else {
                        toastr.error('Failed to add sale.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    submitButton.prop('disabled', false).text('Save');
                    console.error('Error adding sale:', error);
                    toastr.error('Something went wrong while adding the sale.', 'Error');
                }
            });
        });


        // Function to convert date format from DD-MM-YYYY to YYYY-MM-DD
        function convertDateFormat(dateStr) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`;
        }


        // Fetch locations using AJAX
        $.ajax({
            url: '/location-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Location Data:', data); // Log location data
                if (data.status === true && data.data && Array.isArray(data.data)) {
                    const locationSelect = $('#location');
                    locationSelect.html(
                        '<option selected disabled>Please Select Locations</option>');

                    data.data.forEach(function(location) {
                        const option = $('<option></option>').val(location.id).text(location
                            .name);
                        // Check if the location ID matches the user's location ID and set it as selected
                        if (location.id === data.user_id) {
                            option.attr('selected', 'selected');
                        }
                        locationSelect.append(option);
                    });
                } else {
                    console.error('Failed to fetch location data:', data.message ||
                    'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching location data:', error);
            }
        });
        // Fetch customers using AJAX
        // Fetch customers using AJAX
        $.ajax({
            url: '/customer-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Customer Data:', data); // Log customer data
                if (data.status === 200) {
                    const customerSelect = $('#customer-id');
                    customerSelect.html('<option selected disabled>Customer</option>');

                    data.message.forEach(function(customer) {
                        const option = $('<option></option>')
                            .val(customer.id)
                            .text(
                                `${customer.first_name || 'N/A'} ${customer.last_name || ''} (ID: ${customer.id})`
                            )
                            .data('details', customer);
                        // Check if the customer is the "Walking Customer" and set it as selected
                        if ((customer.first_name === "Walking" || customer.first_name ===
                                null) &&
                            (customer.last_name === "Customer" || customer.last_name ===
                                null)) {
                            option.attr('selected', 'selected');
                        }
                        customerSelect.append(option);
                    });

                    // Trigger change event to display details of the default selected customer (Walking Customer)
                    customerSelect.trigger('change');
                } else {
                    console.error('Failed to fetch customer data:', data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching customer data:', error);
            }
        });

        // Handle customer selection
        $('#customer-id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const customerDetails = selectedOption.data('details');

            if (customerDetails) {
                $('#customer-name').text(
                    `${customerDetails.first_name || 'N/A'} ${customerDetails.last_name || ''}`);
                $('#customer-phone').text(customerDetails.mobile_no);
            }
        });
        // Global variable to store combined product data
        let allProducts = [];

        // Fetch data from the single API and combine it
        fetch('/products/stocks')
            .then(response => response.json())
            .then(data => {
                console.log('Stock Data:', data); // Log stock data
                if (data.status === 200 && Array.isArray(data.data)) {
                    allProducts = data.data.map(stock => {
                        const product = stock.product;
                        const totalQuantity = stock.total_stock;

                        // Ensure product object and necessary properties are defined
                        if (product && typeof product.id !== 'undefined' && typeof product
                            .product_name !== 'undefined') {
                            return {
                                id: product.id,
                                name: product.product_name,
                                sku: product.sku,
                                quantity: totalQuantity,
                                price: product.retail_price,
                                product_details: product,
                                batches: stock.batches
                            };
                        } else {
                            console.error('Invalid product data:', product);
                            return null;
                        }
                    }).filter(product => product !== null); // Filter out invalid products

                    initAutocomplete();
                } else {
                    console.error('Unexpected format or status for stocks data:', data);
                }
            })
            .catch(err => console.error('Error fetching product data:', err));

        // Function to initialize autocomplete functionality
        function initAutocomplete() {
            // Check if the product search input element exists (only for add sale pages)
            if ($('#productSearchInput').length === 0) {
                console.log('Product search input not found - skipping autocomplete initialization');
                return;
            }

            if (typeof $.ui === 'undefined' || typeof $.ui.autocomplete === 'undefined') {
                console.error('jQuery UI Autocomplete is not loaded.');
                return;
            }

            const autocompleteInstance = $("#productSearchInput").autocomplete({
                minLength: 2, // Trigger autocomplete after at least 2 letters
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.name && product.name.toLowerCase().includes(searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );
                    response(filteredProducts.map(product => ({
                        label: `${product.name} (${product.sku || 'No SKU'})`,
                        value: product.name,
                        product: product
                    })));
                },
                response: function(event, ui) {
                    // If only one result, automatically add the product
                    if (ui.content.length === 1) {
                        const product = ui.content[0].product;
                        $("#productSearchInput").val(ui.content[0].value);
                        product.quantity =
                            1; // Ensure quantity is set to 1 when adding a new product
                        addProductToTable(product);
                        $(this).autocomplete('close');
                    }
                },
                select: function(event, ui) {
                    $("#productSearchInput").val(ui.item.value);
                    ui.item.product.quantity =
                        1; // Ensure quantity is set to 1 when adding a new product
                    addProductToTable(ui.item.product);
                    $(this).autocomplete('close');
                    return false;
                }
            }).autocomplete("instance");

            if (autocompleteInstance) {
                console.log('Autocomplete instance initialized successfully.');
                autocompleteInstance._renderItem = function(ul, item) {
                    return $("<li>")
                        .append(`<div>${item.label}</div>`)
                        .appendTo(ul);
                };
            } else {
                console.error('Failed to initialize autocomplete instance.');
            }
        }

        // Function to get batches
        function getBatches(product, selectedBatchId, isEditing) {
            if (!Array.isArray(product.batches)) {
                return [];
            }

            if (isEditing) {
                return product.batches.map(batch => ({
                    batch_id: batch.id,
                    batch_price: parseFloat(batch.retail_price) || 0,
                    batch_quantity: batch.qty || 0,
                    batch_quantity_plus_sold: batch.qty + (batch.id === selectedBatchId ? product
                        .quantity : 0) // Adjust batch quantity if editing
                }));
            } else {
                return product.batches.flatMap(batch =>
                    Array.isArray(batch.location_batches) ? batch.location_batches.map(locationBatch => ({
                        batch_id: batch.id,
                        batch_price: parseFloat(batch.retail_price) || 0,
                        batch_quantity: locationBatch.quantity || 0
                    })) : []
                );
            }
        }

        // Function to add product to table
        function addProductToTable(product, selectedBatchId = null, isEditing = false) {
            // Check if product already added
            const existingRow = $(`#addSaleProduct tbody tr[data-id="${product.id}"]`);
            if (existingRow.length > 0) {
                // Update quantity if product already added
                const quantityInput = existingRow.find('.quantity-input');
                quantityInput.val(parseInt(quantityInput.val(), 10) + 1);
                updateRow(existingRow);
                updateTotals();
                return;
            }

            // Validate product data
            if (!product || typeof product.id === 'undefined' || typeof product.name === 'undefined') {
                console.error("Invalid product data:", product);
                return;
            }

            // Set default quantity if it's not provided
            if (typeof product.quantity === 'undefined') {
                product.quantity = 1;
            }

            const batches = getBatches(product, selectedBatchId, isEditing);

            const totalQuantity = batches.reduce((total, batch) => total + (isEditing ? batch
                .batch_quantity_plus_sold : batch.batch_quantity), 0); // Calculate total quantity correctly
            const finalPrice = typeof product.price !== 'undefined' ? parseFloat(product.price) : 0;

            // Calculate discount and net price
            const discountPercent = product.discount || 0;
            const discountType = product.discount_type || 'fixed';
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountPercent;
            } else if (discountType === 'percentage') {
                discountAmount = finalPrice * (discountPercent / 100);
            }

            const netPrice = finalPrice - discountAmount;
            const subtotal = netPrice * product.quantity;

            // Generate batch options
            const batchOptions = batches.map(batch => `
        <option value="${batch.batch_id}"
                data-price="${batch.batch_price}"
                data-quantity="${batch.batch_quantity}"
                ${isEditing ? `data-quantity-plus-sold="${batch.batch_quantity_plus_sold}"` : ''}
                ${selectedBatchId === batch.batch_id ? 'selected' : ''}>
            Batch ${batch.batch_id} - Qty: ${isEditing ? batch.batch_quantity_plus_sold : batch.batch_quantity} - Price: ${batch.batch_price}
        </option>
    `).join('');

            const newRow = `
        <tr data-id="${product.id}">
            <td>${product.name || '-'} <br><span style="font-size:12px;">Current stock: ${totalQuantity}</span>
                <select class="form-select batch-dropdown" aria-label="Select Batch">
                    <option value="all" data-price="${finalPrice}" data-quantity="${totalQuantity}">
                        All Batches - Total Qty: ${totalQuantity} - Price: ${finalPrice}
                    </option>
                    ${batchOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" value="${product.quantity}" min="1">
            </td>
            <td>
                <input type="number" class="form-control price-input" value="${finalPrice.toFixed(2)}" min="0">
            </td>
            <td>
                <input type="number" class="form-control discount-percent" value="${discountPercent}" min="0" max="100">
                <select class="form-select mt-4 discount-type" aria-label="Select Discount Type">
                    <option value="fixed" ${discountType === 'fixed' ? 'selected' : ''}>Fixed</option>
                    <option value="percentage" ${discountType === 'percentage' ? 'selected' : ''}>Percentage</option>
                </select>
            </td>
            <td class="retail-price">${netPrice.toFixed(2)}</td>
            <td class="subtotal">${subtotal.toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm remove-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;


            const $newRow = $(newRow);
            $('#addSaleProduct').DataTable().row.add($newRow).draw();
            allProducts = allProducts.filter(p => p.id !== product.id);

            // Update footer and set up event listeners
            updateFooter();
            toastr.success('Product added to the table!', 'Success');

            // Event listeners for row updates
            const quantityInput = $newRow.find('.quantity-input');
            const priceInput = $newRow.find('.price-input');
            const discountTypeSelect = $newRow.find('.discount-type');
            const batchDropdown = $newRow.find('.batch-dropdown');

            $newRow.find('.remove-btn').on('click', function(event) {
                event.preventDefault(); // Prevent form submission
                var row = $(this).closest('tr');
                $('#confirmRemoveModal').data('row', row).modal('show');
            });

            $newRow.find('.quantity-minus').on('click', () => {
                if (quantityInput.val() > 1) {
                    quantityInput.val(quantityInput.val() - 1);
                    updateTotals();
                }
            });

            $newRow.find('.quantity-plus').on('click', () => {
                let newQuantity = parseInt(quantityInput.val(), 10) + 1;
                const selectedOption = batchDropdown.find(':selected');
                const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
                    selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
                );

                if (newQuantity > maxQuantity) {
                    document.getElementsByClassName('errorSound')[0].play();
                    toastr.error(`You cannot add more than ${maxQuantity} units of this product.`,
                        'Error');
                } else {
                    quantityInput.val(newQuantity);
                    updateTotals();
                }
            });

            // quantityInput.on('input', () => {
            //     const quantityValue = parseInt(quantityInput.val(), 10);
            //     const selectedOption = batchDropdown.find(':selected');
            //     const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
            //         selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
            //     );

            //     if (quantityValue > maxQuantity) {
            //         quantityInput.val(maxQuantity);
            //         document.getElementsByClassName('errorSound')[0].play();
            //         toastr.error(`You cannot add more than ${maxQuantity} units of this product.`,
            //             'Error');
            //     }
            //     updateTotals();
            // });

            quantityInput.on('input', () => {
                // Update row and totals when quantity is changed
                updateRow($newRow);
                updateTotals();
            });

            priceInput.on('input', () => {
                // Update row and totals when price is changed
                updateRow($newRow);
                updateTotals();
            });

            discountTypeSelect.on('change', () => {
                // Update row and totals when discount type is changed
                updateRow($newRow);
                updateTotals();
            });

            batchDropdown.on('change', () => {
                const selectedOption = batchDropdown.find(':selected');
                const batchPrice = parseFloat(selectedOption.data('price')) || 0;
                const batchQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(
                    selectedOption.data('quantity-plus-sold') || selectedOption.data('quantity'), 10
                );

                if (quantityInput.val() > batchQuantity) {
                    quantityInput.val(batchQuantity);
                    toastr.error(`You cannot add more than ${batchQuantity} units from this batch.`,
                        'Error');
                }
                priceInput.val(batchPrice.toFixed(2));
                updateRow($newRow); // Update row when batch is changed
                updateTotals();
            });
        }

        function updateRow($row) {
            const batchElement = $row.find('.batch-dropdown option:selected');
            const quantity = parseFloat($row.find('.quantity-input').val()) || 0;
            const price = parseFloat($row.find('.price-input').val()) || 0;
            const discountPercent = parseFloat($row.find('.discount-percent').val()) || 0;
            const discountType = $row.find('.discount-type').val();
            const batchQuantity = parseFloat(batchElement.data('quantity')) || 0;

            if (quantity > batchQuantity) {
                alert('Requested quantity exceeds available batch quantity.');
                $row.find('.quantity-input').val(batchQuantity);
                quantity = batchQuantity;
            }

            const subTotal = quantity * price;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountPercent;
            } else if (discountType === 'percentage') {
                discountAmount = subTotal * (discountPercent / 100);
            }

            const netCost = subTotal - discountAmount;
            const lineTotal = netCost;

            $row.find('.subtotal').text(subTotal.toFixed(2));
            $row.find('.net-cost').text(netCost.toFixed(2));
            $row.find('.line-total').text(lineTotal.toFixed(2));
            $row.find('.retail-price').text(price.toFixed(2));

            // Update batch quantity if the quantity is updated
            batchElement.data('quantity', batchQuantity - quantity);
        }

        function updateTotals() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                totalItems += parseFloat($(this).find('.quantity-input').val()) || 0;
                netTotalAmount += parseFloat($(this).find('.subtotal').text()) || 0;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));

            const discountType = $('#discount_type').val();
            const discountAmount = parseFloat($('#discount_amount').val()) || 0;
            let discountNetTotalAmount = netTotalAmount;

            if (discountType === 'percentage') {
                discountNetTotalAmount -= (netTotalAmount * (discountAmount / 100));
            } else if (discountType === 'fixed') {
                discountNetTotalAmount -= discountAmount;
            }

            $('#discount-net-total-amount').text(discountNetTotalAmount.toFixed(2));

            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const paymentDue = discountNetTotalAmount - paidAmount;
            $('.payment-due').text(`Rs. ${paymentDue.toFixed(2)}`);
        }

        // Function to update calculations
        function updateCalculations() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                // Get the quantity, price, and discount details
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                const discountPercent = parseFloat($(this).find('.discount-percent').val()) || 0;
                const discountType = $(this).find('.discount-type').val();

                // Calculate the subtotal before discount
                const subTotal = quantity * price;
                let discountAmount = 0;

                // Calculate the discount amount based on the discount type
                if (discountType === 'fixed') {
                    discountAmount = discountPercent;
                } else if (discountType === 'percentage') {
                    discountAmount = subTotal * (discountPercent / 100);
                }

                // Calculate the net cost after discount
                const netCost = subTotal - discountAmount;

                // Update the subtotal and net cost in the respective columns
                $(this).find('.subtotal').text(subTotal.toFixed(2));
                $(this).find('.net-cost').text(netCost.toFixed(2));

                // Add the quantity and net cost to the total items and net total amount
                totalItems += quantity;
                netTotalAmount += netCost;
            });

            const discountTypeOverall = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmountOverall = 0;

            if (discountTypeOverall === 'fixed') {
                discountAmountOverall = discountInput;
            } else if (discountTypeOverall === 'percentage') {
                discountAmountOverall = (netTotalAmount * discountInput) / 100;
            }

            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10' || taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmountOverall) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmountOverall + taxAmount;

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#purchase-total').text(`Purchase Total: Rs. ${finalTotal.toFixed(2)}`);
            $('#discount-display').text(`(-) Rs. ${discountAmountOverall.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs. ${taxAmount.toFixed(2)}`);
            updatePaymentDue(finalTotal, discountAmountOverall);
        }

        // Function to update payment due amount
        function updatePaymentDue(finalTotal, discountAmount) {
            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const discountNetTotalAmount = finalTotal - discountAmount;
            const paymentDue = discountNetTotalAmount - paidAmount;
            $('.payment-due').text(`Rs. ${paymentDue.toFixed(2)}`);
        }

        // Function to update footer
        function updateFooter() {
            // Example footer update logic
            const totalItems = parseFloat($('#total-items').text());
            const netTotalAmount = parseFloat($('#net-total-amount').text());
            // Update footer elements with these values
            $('#footer-total-items').text(totalItems);
            $('#footer-net-total-amount').text(`Rs. ${netTotalAmount.toFixed(2)}`);
        }

        // Event listener for remove button click
        $(document).on('click', '.remove-btn', function(event) {
            event.preventDefault(); // Prevent form submission
            var row = $(this).closest('tr');
            $('#confirmRemoveModal').data('row', row).modal('show');
        });

        // Event listener for confirmation modal
        $('#confirmRemoveButton').on('click', function() {
            var row = $('#confirmRemoveModal').data('row');
            removeProduct(row);
            $('#confirmRemoveModal').modal('hide');
        });

        // Function to handle the removal of the product from the DataTable
        function removeProduct(row) {
            var table = $('#addSaleProduct').DataTable();
            var productId = row.data('id');
            var product = allProducts.find(p => p.id === productId);

            // Re-add the removed product back to the allProducts array
            if (product) {
                allProducts.push(product);
            }

            table.row(row).remove().draw();

            toastr.success('Product removed successfully!', 'Success');
            updateCalculations();
            updateFooter();
            initAutocomplete(); // Re-initialize autocomplete to include the removed product
        }

        // Trigger calculations on events
        $(document).on('change keyup',
            '.quantity-input, .discount-percent, .price-input, .discount-type, #discount-amount, #discount-type, #tax-type',
            function() {
                updateCalculations();
            });


        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#addSalesForm')[0].reset(); // Reset the form
            $('.error-message').html(''); // Clear error messages
            $('#addSaleProduct').DataTable().clear().draw(); // Clear the data table
            $('#addSalesForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addSalesForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // // Use event delegation for the action buttons and stop event propagation
        $(document).on('click', '.delete_btn', function(event) {
            var id = $(this).val();

            swal({
                    title: "Are you sure?",
                    text: "Do you really want to delete this sale? This action cannot be undone.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: false
                },
                function(isConfirm) {
                    if (isConfirm) {
                        $.ajax({
                            url: `sales/delete/${id}`,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.status === 200) {
                                    toastr.success(response.message ||
                                        "Sale deleted successfully!");
                                    const successSound = document.querySelector(
                                        '.successSound');

                                    // Refresh the DataTable to show updated sales list
                                    refreshSalesTable();

                                    swal.close();
                                    successSound.play();

                                } else {
                                    swal("Error!", response.message ||
                                        "An error occurred while deleting the sale.",
                                        "error");
                                }
                            },
                            error: function(xhr, status, error) {
                                let errorMessage =
                                    "Unable to delete the sale. Please try again later.";
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                swal("Error!", errorMessage, "error");
                                console.error('Delete error:', error);
                            }
                        });
                    }
                });
        });

        // // Use event delegation for the action buttons and stop event propagation
        $(document).on('click', '.edit_btn', function(event) {
            var id = $(this).val();
            window.location.href = `/sales/edit/${id}`;
        });

        // Handle sell return button click
        $(document).on('click', '.sell-return', function(event) {
            event.preventDefault();
            var saleId = $(this).val();
            
            // Get the sale details to find the invoice number
            var row = $('#salesTable').DataTable().row($(this).closest('tr')).data();
            var invoiceNo = row ? (row.invoice_no || row.id) : saleId;
            
            console.log('Navigating to sale return with invoice:', invoiceNo);
            
            // Navigate to sale return page with invoice number as parameter (use 'invoiceNo' not 'invoice_no')
            window.location.href = `{{ route('sale-return/add') }}?invoiceNo=${invoiceNo}`;
        });




        // Function to fetch sale data for editing
        function fetchSaleData(saleId) {
            $.ajax({
                url: `/sales/edit/${saleId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        populateForm(response.sales);
                        $('#editSaleModal').modal('show');
                    } else {
                        toastr.error('Failed to fetch sale data.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 404) {
                        toastr.error('Sale not found.', 'Error');
                    } else {
                        toastr.error('Something went wrong while fetching the sale data.', 'Error');
                    }
                    console.error('Error fetching sale data:', error);
                }
            });
        }

        // Extract the sale ID from the URL and fetch data if editing
        $(document).ready(function() {
            const pathSegments = window.location.pathname.split('/');
            const saleId = pathSegments[pathSegments.length - 1];

            if (saleId && saleId !== 'add-sale' && saleId !== 'list-sale') {
                fetchSaleData(saleId);
            }
        });
        // Populate form with sale data
        function populateForm(sale) {
            $('#sale_id').val(sale.id);
            $('#location').val(sale.location_id).change();
            $('#customer-id').val(sale.customer_id).change();
            $('#sales_date').val(convertDateFormat(sale.sales_date));
            $('#status').val(sale.status).change();
            $('#invoice_no').val(sale.invoice_no);

            // Clear existing products in the table
            const productTable = $('#addSaleProduct').DataTable();
            productTable.clear().draw();

            // Populate products table
            if (sale.products && Array.isArray(sale.products)) {
                sale.products.forEach(product => {
                    const productData = {
                        id: product.product_id,
                        name: product.product.product_name,
                        sku: product.product.sku,
                        quantity: product.quantity,
                        price: product.price,
                        discount: product.discount,
                        price_type: product.price_type,
                        batch_quantity_plus_sold: product.batch_quantity_plus_sold,
                        batches: product.product.batches || []
                    };
                    addProductToTable(productData, product.batch_id,
                        true); // Pass true to indicate editing
                });
            }

            updateCalculations();
        }





    });

    // Add filter functionality
    // Initialize date range picker
    $('#dateRangeFilter').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });

    $('#dateRangeFilter').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#dateRangeFilter').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Filter change events
    $('#locationFilter, #customerFilter, #userFilter, #paymentStatusFilter, #paymentMethodFilter').change(function() {
        if ($.fn.DataTable.isDataTable('#salesTable')) {
            $('#salesTable').DataTable().ajax.reload();
        }
    });

    // Date range filter change
    $('#dateRangeFilter').on('apply.daterangepicker', function(ev, picker) {
        if ($.fn.DataTable.isDataTable('#salesTable')) {
            $('#salesTable').DataTable().ajax.reload();
        }
    });

    // Function to toggle payment method fields in bulk payment modal
    function togglePaymentFields(modalId) {
        const modal = modalId || 'bulkPaymentModal';
        const paymentMethod = $(`#${modal} #paymentMethod`).val();

        // Hide all payment method specific fields
        $(`#${modal} #cardFields`).hide();
        $(`#${modal} #chequeFields`).hide();
        $(`#${modal} #bankTransferFields`).hide();

        // Show relevant fields based on payment method
        if (paymentMethod === 'card') {
            $(`#${modal} #cardFields`).show();
        } else if (paymentMethod === 'cheque') {
            $(`#${modal} #chequeFields`).show();
        } else if (paymentMethod === 'bank_transfer') {
            $(`#${modal} #bankTransferFields`).show();
        }
    }

    // Initialize date range picker
    if ($('#dateRangeFilter').length) {
        $('#dateRangeFilter').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                    .endOf('month')
                ]
            }
        });

        $('#dateRangeFilter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            // Trigger change event to reload table
            $(this).trigger('change');
        });

        $('#dateRangeFilter').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            // Trigger change event to reload table
            $(this).trigger('change');
        });
    }
</script>
