<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var itemCounter = 0;
    var isEditMode = false;
    var expenseId = null;
    var expenseTable = null;
    
    // Date formatting helper function
    function formatDate(dateString) {
        if (!dateString) return '';
        
        var date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Return original if invalid
        
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();
        
        return day + '-' + month + '-' + year;
    }

    // Initialize based on current page
    if ($('#expenseTable').length > 0) {
        // We're on the list page
        initializeExpenseList();
    }
    
    if ($('#expenseForm').length > 0) {
        // We're on the create/edit page
        initializeExpenseForm();
        loadLocations(); // Load locations for dropdown
        loadSuppliers(); // Load suppliers for dropdown
    }

    // ==================== EXPENSE LIST FUNCTIONS ====================
    
    function initializeExpenseList() {
        // Initialize DataTable
        expenseTable = $('#expenseTable').DataTable({
            responsive: true,
            ordering: true,
            searching: true,
            paging: true,
            pageLength: 25,
            language: {
                search: "Search expenses:",
                lengthMenu: "Show _MENU_ expenses per page",
                info: "Showing _START_ to _END_ of _TOTAL_ expenses",
                infoEmpty: "No expenses found",
                infoFiltered: "(filtered from _MAX_ total expenses)"
            }
        });

        // Load expenses on page load
        loadExpenses();

        // Event listeners for list page
        $('#categoryFilter').change(function() {
            var categoryId = $(this).val();
            loadSubCategories(categoryId, '#subCategoryFilter');
        });

        $('#filterBtn').click(function() {
            loadExpenses();
        });

        // View expense details
        $(document).on('click', '.view_btn', function() {
            var id = $(this).val();
            viewExpenseDetails(id);
        });

        // Edit expense - redirect to create page with edit mode
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            window.location.href = '/expense-create?edit=' + id;
        });

        // Delete expense
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleting_id').val(id);
            $('#deleteModal').modal('show');
        });

        $('.confirm_delete_btn').click(function() {
            var id = $('#deleting_id').val();
            deleteExpense(id);
        });
    }

    // Load expenses function
    function loadExpenses() {
        var filters = {
            category_id: $('#categoryFilter').val(),
            sub_category_id: $('#subCategoryFilter').val(),
            payment_status: $('#paymentStatusFilter').val(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        };

        $.ajax({
            url: '/expense-get-all',
            type: 'GET',
            data: filters,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                expenseTable.clear().draw();
                
                if (response.status == 200 && response.data) {
                    var totalExpenses = 0;
                    var totalAmount = 0;
                    var paidAmount = 0;
                    var dueAmount = 0;

                    response.data.forEach(function(expense, index) {
                        totalExpenses++;
                        totalAmount += parseFloat(expense.total_amount || 0);
                        paidAmount += parseFloat(expense.paid_amount || 0);
                        dueAmount += parseFloat(expense.due_amount || 0);

                        var formattedTotal = 'Rs.' + parseFloat(expense.total_amount || 0).toFixed(2);
                        var formattedPaid = 'Rs.' + parseFloat(expense.paid_amount || 0).toFixed(2);
                        var formattedDue = 'Rs.' + parseFloat(expense.due_amount || 0).toFixed(2);

                        var statusBadge = getStatusBadge(expense.payment_status);
                        
                        var actions = `
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="feather-menu"></i> Actions
                            </button>
                            <ul class="dropdown-menu">`;
                        
                        @can('view expense')
                        actions += '<li><button type="button" value="' + expense.id + '" class="view_btn dropdown-item"><i class="feather-eye text-info me-2"></i> View Details</button></li>';
                        @endcan
                        
                        @can('edit expense')
                        actions += '<li><button type="button" value="' + expense.id + '" class="edit_btn dropdown-item"><i class="feather-edit text-primary me-2"></i> Edit Expense</button></li>';
                        @endcan
                        
                        // Add payment button for expenses with due amount
                        if (expense.payment_status !== 'paid' && parseFloat(expense.due_amount || 0) > 0) {
                            @can('edit expense')
                            actions += '<li><button type="button" value="' + expense.id + '" class="payment_btn dropdown-item"><i class="feather-credit-card text-success me-2"></i> Add Payment</button></li>';
                            @endcan
                        }
                        
                        // Add payment history button
                        actions += '<li><button type="button" value="' + expense.id + '" class="payment_history_btn dropdown-item"><i class="feather-list text-warning me-2"></i> Payment History</button></li>';
                        
                        // Add separator before delete
                        actions += '<li><hr class="dropdown-divider"></li>';
                        
                        @can('delete expense')
                        actions += '<li><button type="button" value="' + expense.id + '" class="delete_btn dropdown-item"><i class="feather-trash-2 text-danger me-2"></i> Delete Expense</button></li>';
                        @endcan
                        
                        actions += `
                            </ul>
                        </div>`;

                        // Format supplier information safely
                        var supplierInfo = 'N/A';
                        if (expense.supplier && expense.supplier.first_name) {
                            supplierInfo = expense.supplier.first_name + ' ' + (expense.supplier.last_name || '') + 
                                         ' (' + (expense.supplier.mobile_no || 'N/A') + ')';
                        } else if (expense.paid_to) {
                            supplierInfo = expense.paid_to;
                        }

                        var row = [
                            expense.expense_no || '',
                            expense.formatted_date || formatDate(expense.date) || '',
                            expense.expense_parent_category ? expense.expense_parent_category.expenseParentCatergoryName : 'N/A',
                            expense.expense_sub_category ? expense.expense_sub_category.subExpenseCategoryname : 'N/A',
                            supplierInfo,
                            expense.location ? expense.location.name : 'N/A',
                            formattedTotal,
                            formattedPaid,
                            formattedDue,
                            statusBadge,
                            actions
                        ];

                        expenseTable.row.add(row).draw(false);
                    });

                    // Update summary cards
                    $('#totalExpenses').text(totalExpenses);
                    $('#totalAmount').text('Rs.' + totalAmount.toFixed(2));
                    $('#paidAmount').text('Rs.' + paidAmount.toFixed(2));
                    $('#dueAmount').text('Rs.' + dueAmount.toFixed(2));
                } else {
                    // Reset summary cards
                    $('#totalExpenses').text('0');
                    $('#totalAmount').text('Rs.0.00');
                    $('#paidAmount').text('Rs.0.00');
                    $('#dueAmount').text('Rs.0.00');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                toastr.error('Failed to load expenses', 'Error');
            }
        });
    }

    // ==================== EXPENSE FORM FUNCTIONS ====================
    
    function initializeExpenseForm() {
        // Check if we're in edit mode
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('edit')) {
            expenseId = urlParams.get('edit');
            isEditMode = true;
            loadExpenseForEdit(expenseId);
            $('#pageTitle').text('Edit Expense');
            $('#submitBtn').html('<i class="feather-save"></i> Update Expense');
        } else {
            isEditMode = false;
            $('#pageTitle').text('Create New Expense');
            $('#submitBtn').html('<i class="feather-save"></i> Save Expense');
            loadParentCategories();
            addExpenseItem(); // Add first item by default
        }
        
        // Category change event
        $('#expense_parent_category_id').change(function() {
            var categoryId = $(this).val();
            loadSubCategories(categoryId, '#expense_sub_category_id');
        });

        // Add item button
        $('#addItemBtn').click(function() {
            addExpenseItem();
        });

        // Calculate totals when any amount field changes
        $(document).on('input', '#tax_amount, #discount_amount, #shipping_charges, .quantity-input, .unit-price-input', function() {
            if ($(this).hasClass('quantity-input') || $(this).hasClass('unit-price-input')) {
                var row = $(this).closest('.item-row');
                var quantity = parseFloat(row.find('.quantity-input').val()) || 0;
                var unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
                var total = quantity * unitPrice;
                row.find('.total-input').val(total.toFixed(2));
            }
            calculateTotals();
        });

        $(document).on('change', '#discount_type', function() {
            calculateTotals();
        });

        // Remove item
        $(document).on('click', '.remove-item-btn', function() {
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                calculateTotals();
            } else {
                toastr.warning('At least one item is required', 'Warning');
            }
        });

        // Form submission
        $('#expenseForm').submit(function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return false;
            }

            var formData = new FormData(this);
            var url = isEditMode ? '/expense-update/' + expenseId : '/expense-store';
            var method = 'POST';
            
            $('#submitBtn').prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i> ' + (isEditMode ? 'Updating...' : 'Saving...'));

            $.ajax({
                url: url,
                type: method,
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.status == 200) {
                        toastr.success(response.message, 'Success');
                        setTimeout(function() {
                            window.location.href = '/expense-list';
                        }, 1500);
                    } else {
                        toastr.error(response.message, 'Error');
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON?.errors;
                    if (errors) {
                        $('.text-danger').text('');
                        $.each(errors, function(field, messages) {
                            $('#' + field + '_error').text(messages[0]);
                        });
                        toastr.error('Please check the form for errors', 'Validation Error');
                    } else {
                        toastr.error('An error occurred while ' + (isEditMode ? 'updating' : 'saving') + ' expense', 'Error');
                    }
                },
                complete: function() {
                    $('#submitBtn').prop('disabled', false).html('<i class="feather-save"></i> ' + (isEditMode ? 'Update Expense' : 'Save Expense'));
                }
            });
        });
    }

    // Load expense for editing
    function loadExpenseForEdit(id) {
        $.ajax({
            url: '/expense-edit/' + id,
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.status == 200 && response.data) {
                    var expense = response.data;
                    
                    // Populate form fields
                    $('#expense_no').val(expense.expense_no);
                    // Format date for input field (needs Y-m-d format)
                    var dateValue = expense.date;
                    if (dateValue && dateValue.length > 10) {
                        dateValue = dateValue.split('T')[0]; // Remove time part if exists
                    }
                    $('#date').val(dateValue);
                    $('#reference_no').val(expense.reference_no);
                    $('#expense_parent_category_id').val(expense.expense_parent_category_id);
                    $('#expense_sub_category_id').val(expense.expense_sub_category_id);
                    $('#paid_to').val(expense.paid_to);
                    $('#payment_method').val(expense.payment_method);
                    $('#tax_amount').val(expense.tax_amount);
                    $('#discount_type').val(expense.discount_type);
                    $('#discount_amount').val(expense.discount_amount);
                    $('#shipping_charges').val(expense.shipping_charges);
                    $('#paid_amount').val(expense.paid_amount);
                    $('#note').val(expense.note);
                    
                    // Load categories and subcategories
                    loadParentCategories(expense.expense_parent_category_id);
                    loadLocations(expense.location_id);
                    if (expense.expense_parent_category_id) {
                        loadSubCategories(expense.expense_parent_category_id, '#expense_sub_category_id', expense.expense_sub_category_id);
                    }
                    
                    // Load expense items
                    $('#itemsContainer').empty();
                    itemCounter = 0;
                    if (expense.expense_items && expense.expense_items.length > 0) {
                        expense.expense_items.forEach(function(item) {
                            addExpenseItem(item);
                        });
                    } else {
                        addExpenseItem(); // Add one empty item
                    }
                    
                    calculateTotals();
                } else {
                    toastr.error('Expense not found', 'Error');
                    window.location.href = '/expense-list';
                }
            },
            error: function() {
                toastr.error('Failed to load expense details', 'Error');
                window.location.href = '/expense-list';
            }
        });
    }

    // ==================== SHARED FUNCTIONS ====================

    // Load parent categories
    function loadParentCategories(selectedId = null) {
        var categorySelect = $('#expense_parent_category_id');
        
        $.ajax({
            url: '/expense-parent-categories-dropdown',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status == 200 && response.data) {
                    categorySelect.html('<option value="">Select Category</option>');
                    response.data.forEach(function(category) {
                        var selected = selectedId && selectedId == category.id ? 'selected' : '';
                        categorySelect.append('<option value="' + category.id + '" ' + selected + '>' + category.expenseParentCatergoryName + '</option>');
                    });
                }
            },
            error: function() {
                console.error('Error loading parent categories');
                toastr.error('Failed to load expense categories');
            }
        });
    }

    // Load sub categories
    function loadSubCategories(categoryId, selector, selectedId = null) {
        var subCategorySelect = $(selector);
        subCategorySelect.html('<option value="">Select Sub Category</option>');

        if (categoryId) {
            $.ajax({
                url: '/expense-sub-categories/' + categoryId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200 && response.data) {
                        response.data.forEach(function(subCategory) {
                            var selected = selectedId && selectedId == subCategory.id ? 'selected' : '';
                            subCategorySelect.append('<option value="' + subCategory.id + '" ' + selected + '>' + subCategory.subExpenseCategoryname + '</option>');
                        });
                    }
                },
                error: function() {
                    console.error('Error loading sub categories');
                }
            });
        }
    }

    // Load locations
    function loadLocations(selectedId = null) {
        var locationSelect = $('#location_id');
        console.log('Loading locations...');
        
        $.ajax({
            url: '/expense-locations',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Location API response:', response);
                if (response.status === true && response.data) {
                    locationSelect.html('<option value="">Select Location</option>');
                    console.log('Processing ' + response.data.length + ' locations');
                    response.data.forEach(function(location) {
                        var selected = selectedId && selectedId == location.id ? 'selected' : '';
                        locationSelect.append('<option value="' + location.id + '" ' + selected + '>' + location.name + '</option>');
                    });
                    console.log('Locations loaded successfully');
                } else {
                    console.error('Invalid response format for locations:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading locations:', error);
                toastr.error('Failed to load locations');
            }
        });
    }

    // Load suppliers
    function loadSuppliers(selectedId = null) {
        var supplierSelect = $('#supplier_id');
        console.log('Loading suppliers...');
        
        $.ajax({
            url: '/expense-suppliers',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Supplier API response:', response);
                if (response.status === true && response.data) {
                    supplierSelect.html('<option value="">Select Supplier</option>');
                    console.log('Processing ' + response.data.length + ' suppliers');
                    response.data.forEach(function(supplier) {
                        var selected = selectedId && selectedId == supplier.id ? 'selected' : '';
                        var optionText = supplier.name + ' (' + supplier.mobile + ') - Balance: ' + supplier.balance;
                        supplierSelect.append('<option value="' + supplier.id + '" ' + selected + ' data-balance="' + supplier.balance + '">' + optionText + '</option>');
                    });
                    console.log('Suppliers loaded successfully');
                } else {
                    console.error('Invalid response format for suppliers:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading suppliers:', error);
                toastr.error('Failed to load suppliers');
            }
        });
    }

    // Add expense item
    function addExpenseItem(itemData = null) {
        var itemHtml = `
            <div class="item-row" data-index="${itemCounter}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="items[${itemCounter}][item_name]" value="${itemData ? itemData.item_name || '' : ''}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" class="form-control" name="items[${itemCounter}][description]" value="${itemData ? itemData.description || '' : ''}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control quantity-input" name="items[${itemCounter}][quantity]" step="1" min="1" value="${itemData ? itemData.quantity || 1 : 1}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Unit Price <span class="text-danger">*</span></label>
                            <input type="number" class="form-control unit-price-input" name="items[${itemCounter}][unit_price]" step="0.01" min="0" value="${itemData ? itemData.unit_price || 0 : 0}" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Total</label>
                            <input type="number" class="form-control total-input" name="items[${itemCounter}][total]" value="${itemData ? itemData.total || 0 : 0}" readonly>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm d-block remove-item-btn">
                                <i class="feather-trash-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#itemsContainer').append(itemHtml);
        itemCounter++;
    }

    // Calculate totals
    function calculateTotals() {
        var subtotal = 0;
        
        $('.item-row').each(function() {
            var total = parseFloat($(this).find('.total-input').val()) || 0;
            subtotal += total;
        });

        var taxAmount = parseFloat($('#tax_amount').val()) || 0;
        var discountAmount = parseFloat($('#discount_amount').val()) || 0;
        var discountType = $('#discount_type').val();
        var shippingCharges = parseFloat($('#shipping_charges').val()) || 0;

        var discountValue = 0;
        if (discountType === 'percentage') {
            discountValue = (subtotal * discountAmount) / 100;
        } else {
            discountValue = discountAmount;
        }

        var total = subtotal + taxAmount - discountValue + shippingCharges;

        $('#subtotalDisplay').text('Rs.' + subtotal.toFixed(2));
        $('#taxDisplay').text('Rs.' + taxAmount.toFixed(2));
        $('#discountDisplay').text('Rs.' + discountValue.toFixed(2));
        $('#shippingDisplay').text('Rs.' + shippingCharges.toFixed(2));
        $('#totalDisplay').text('Rs.' + total.toFixed(2));
        $('#total_amount').val(total.toFixed(2));
    }

    // Validate form
    function validateForm() {
        var isValid = true;
        $('.text-danger').text('');
        
        if (!$('#expense_parent_category_id').val()) {
            $('#expense_parent_category_id_error').text('Expense category is required');
            isValid = false;
        }

        if (!$('#supplier_id').val()) {
            $('#supplier_id_error').text('Supplier is required');
            isValid = false;
        }
        
        if (!$('#location_id').val()) {
            $('#location_id_error').text('Location is required');
            isValid = false;
        }
        
        if (!$('#payment_method').val()) {
            $('#payment_method_error').text('Payment method is required');
            isValid = false;
        }
        
        if ($('.item-row').length === 0) {
            $('#items_error').text('At least one expense item is required');
            isValid = false;
        }
        
        $('.item-row').each(function() {
            var itemName = $(this).find('input[name*="[item_name]"]').val();
            var quantity = $(this).find('input[name*="[quantity]"]').val();
            var unitPrice = $(this).find('input[name*="[unit_price]"]').val();
            
            if (!itemName || !quantity || !unitPrice) {
                $('#items_error').text('All item fields are required');
                isValid = false;
                return false;
            }
        });
        
        return isValid;
    }

    // View expense details
    function viewExpenseDetails(id) {
        $.ajax({
            url: '/expense-show/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status == 200 && response.data) {
                    var expense = response.data;
                    var detailsHtml = buildExpenseDetailsHtml(expense);
                    $('#expenseDetails').html(detailsHtml);
                    $('#viewExpenseModal').modal('show');
                } else {
                    toastr.error('Expense not found', 'Error');
                }
            },
            error: function() {
                toastr.error('Failed to load expense details', 'Error');
            }
        });
    }

    // Delete expense
    function deleteExpense(id) {
        $.ajax({
            url: '/expense-delete/' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            dataType: 'json',
            success: function(response) {
                $('#deleteModal').modal('hide');
                if (response.status == 200) {
                    toastr.success(response.message, 'Success');
                    loadExpenses();
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function() {
                $('#deleteModal').modal('hide');
                toastr.error('Failed to delete expense', 'Error');
            }
        });
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        switch(status) {
            case 'paid':
                return '<span class="badge bg-success status-badge">Paid</span>';
            case 'partial':
                return '<span class="badge bg-warning status-badge">Partial</span>';
            case 'pending':
                return '<span class="badge bg-danger status-badge">Pending</span>';
            default:
                return '<span class="badge bg-secondary status-badge">Unknown</span>';
        }
    }

    // Build expense details HTML
    function buildExpenseDetailsHtml(expense) {
        var html = '<div class="expense-details">';
        
        html += '<div class="row mb-3">';
        html += '<div class="col-md-6">';
        html += '<h6><strong>Expense Information</strong></h6>';
        html += '<p><strong>Expense No:</strong> ' + (expense.expense_no || 'N/A') + '</p>';
        html += '<p><strong>Date:</strong> ' + (expense.formatted_date || formatDate(expense.date) || 'N/A') + '</p>';
        html += '<p><strong>Reference No:</strong> ' + (expense.reference_no || 'N/A') + '</p>';
        html += '<p><strong>Status:</strong> ' + getStatusBadge(expense.payment_status) + '</p>';
        html += '</div>';
        
        html += '<div class="col-md-6">';
        html += '<h6><strong>Category Information</strong></h6>';
        html += '<p><strong>Category:</strong> ' + (expense.expense_parent_category ? expense.expense_parent_category.expenseParentCatergoryName : 'N/A') + '</p>';
        html += '<p><strong>Sub Category:</strong> ' + (expense.expense_sub_category ? expense.expense_sub_category.subExpenseCategoryname : 'N/A') + '</p>';
        html += '<p><strong>Paid To:</strong> ' + (expense.paid_to || 'N/A') + '</p>';
        html += '<p><strong>Location:</strong> ' + (expense.location ? expense.location.name : 'N/A') + '</p>';
        html += '</div>';
        html += '</div>';

        html += '<div class="row mb-3">';
        html += '<div class="col-md-12">';
        html += '<h6><strong>Amount Information</strong></h6>';
        html += '<div class="row">';
        html += '<div class="col-md-3"><p><strong>Total Amount:</strong> Rs.' + parseFloat(expense.total_amount || 0).toFixed(2) + '</p></div>';
        html += '<div class="col-md-3"><p><strong>Paid Amount:</strong> Rs.' + parseFloat(expense.paid_amount || 0).toFixed(2) + '</p></div>';
        html += '<div class="col-md-3"><p><strong>Due Amount:</strong> Rs.' + parseFloat(expense.due_amount || 0).toFixed(2) + '</p></div>';
        html += '<div class="col-md-3"><p><strong>Payment Method:</strong> ' + (expense.payment_method || 'N/A') + '</p></div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        if (expense.expense_items && expense.expense_items.length > 0) {
            html += '<div class="row mb-3">';
            html += '<div class="col-md-12">';
            html += '<h6><strong>Expense Items</strong></h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm">';
            html += '<thead><tr><th>Item</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>';
            html += '<tbody>';
            
            expense.expense_items.forEach(function(item) {
                html += '<tr>';
                html += '<td>' + (item.item_name || 'N/A') + '</td>';
                html += '<td>' + (item.description || 'N/A') + '</td>';
                html += '<td>' + (item.quantity || 0) + '</td>';
                html += '<td>Rs.' + parseFloat(item.unit_price || 0).toFixed(2) + '</td>';
                html += '<td>Rs.' + parseFloat(item.total || 0).toFixed(2) + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        if (expense.note) {
            html += '<div class="row mb-3">';
            html += '<div class="col-md-12">';
            html += '<h6><strong>Notes</strong></h6>';
            html += '<p>' + expense.note + '</p>';
            html += '</div>';
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    // Payment Settlement functionality
    $(document).on('click', '.payment_btn', function (e) {
        e.preventDefault();
        var expense_id = $(this).val();
        
        // Fetch expense details for payment
        $.ajax({
            url: '/expense-show/' + expense_id,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    var expense = response.data;
                    openPaymentModal(expense);
                } else {
                    toastr.error('Failed to fetch expense details');
                }
            },
            error: function() {
                toastr.error('Error fetching expense details');
            }
        });
    });

    // Payment History functionality
    $(document).on('click', '.payment_history_btn', function (e) {
        e.preventDefault();
        var expense_id = $(this).val();
        
        $.ajax({
            url: '/expense-payment-history/' + expense_id,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    showPaymentHistory(response.data);
                } else {
                    toastr.error('Failed to fetch payment history');
                }
            },
            error: function() {
                toastr.error('Error fetching payment history');
            }
        });
    });

    // Open payment modal
    function openPaymentModal(expense) {
        $('#paymentExpenseNo').text(expense.expense_no);
        $('#paymentTotalAmount').text('Rs.' + parseFloat(expense.total_amount).toFixed(2));
        $('#paymentPaidAmount').text('Rs.' + parseFloat(expense.paid_amount).toFixed(2));
        $('#paymentDueAmount').text('Rs.' + parseFloat(expense.due_amount).toFixed(2));
        $('#payment_expense_id').val(expense.id);
        $('#payment_amount').attr('max', expense.due_amount);
        $('#payment_amount').val('');
        $('#payment_method').val('');
        $('#payment_date').val('{{ date("Y-m-d") }}');
        $('#payment_reference').val('');
        $('#payment_note').val('');
        $('#paymentModal').modal('show');
    }

    // Show payment history modal
    function showPaymentHistory(data) {
        var expense = data.expense;
        var payments = data.payments;
        
        $('#historyExpenseNo').text(expense.expense_no);
        $('#historyTotalAmount').text('Rs.' + parseFloat(expense.total_amount).toFixed(2));
        $('#historyPaidAmount').text('Rs.' + parseFloat(expense.paid_amount).toFixed(2));
        $('#historyDueAmount').text('Rs.' + parseFloat(expense.due_amount).toFixed(2));
        $('#historyPaymentStatus').html('<span class="badge bg-' + getStatusColor(expense.payment_status) + '">' + expense.payment_status.toUpperCase() + '</span>');
        $('#historyLocationName').text(expense.location_name || 'N/A');
        $('#historyLocationName').text(expense.location_name || 'N/A');
        
        var paymentHtml = '';
        if (payments.length > 0) {
            payments.forEach(function(payment) {
                paymentHtml += '<tr>';
                paymentHtml += '<td>' + payment.payment_date + '</td>';
                paymentHtml += '<td>' + payment.payment_method + '</td>';
                paymentHtml += '<td>Rs.' + parseFloat(payment.amount).toFixed(2) + '</td>';
                paymentHtml += '<td>' + (payment.reference_no || 'N/A') + '</td>';
                paymentHtml += '<td>' + (payment.note || 'N/A') + '</td>';
                paymentHtml += '<td>' + payment.created_by + '</td>';
                paymentHtml += '<td>' + payment.created_at + '</td>';
                paymentHtml += '<td>';
                paymentHtml += '<button type="button" class="btn btn-outline-primary btn-sm me-1 edit_payment_btn" data-payment-id="' + payment.id + '" title="Edit Payment"><i class="feather-edit"></i></button>';
                paymentHtml += '<button type="button" class="btn btn-outline-danger btn-sm delete_payment_btn" data-payment-id="' + payment.id + '" title="Delete Payment"><i class="feather-trash-2"></i></button>';
                paymentHtml += '</td>';
                paymentHtml += '</tr>';
            });
        } else {
            paymentHtml = '<tr><td colspan="8" class="text-center">No payments found</td></tr>';
        }
        
        $('#paymentHistoryTable tbody').html(paymentHtml);
        $('#paymentHistoryModal').modal('show');
    }

    // Submit payment form
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var expense_id = $('#payment_expense_id').val();
        var formData = {
            payment_amount: $('#payment_amount').val(),
            payment_method: $('#payment_method').val(),
            payment_date: $('#payment_date').val(),
            payment_reference: $('#payment_reference').val(),
            payment_note: $('#payment_note').val(),
            _token: '{{ csrf_token() }}'
        };
        
        $.ajax({
            url: '/expense-add-payment/' + expense_id,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 200) {
                  toastr.success(response.message);
                    $('#paymentModal').modal('hide');
                    // Refresh the expense list to show updated payment status
                    setTimeout(function() {
                        refreshExpenseData();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('Error processing payment');
                }
            }
        });
    });

    function getStatusColor(status) {
        switch(status) {
            case 'paid': return 'success';
            case 'partial': return 'warning';
            case 'pending': return 'danger';
            default: return 'secondary';
        }
    }
});

    // Edit payment functionality
    $(document).on('click', '.edit_payment_btn', function (e) {
        e.preventDefault();
        var payment_id = $(this).data('payment-id');
        
        $.ajax({
            url: '/expense-payment/' + payment_id,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    var payment = response.data;
                    openEditPaymentModal(payment);
                } else {
                    toastr.error('Failed to fetch payment details');
                }
            },
            error: function() {
                toastr.error('Error fetching payment details');
            }
        });
    });

    // Delete payment functionality
    $(document).on('click', '.delete_payment_btn', function (e) {
        e.preventDefault();
        var payment_id = $(this).data('payment-id');
        
        swal({
            title: "Are you sure?",
            text: "Do you want to delete this payment? This action cannot be undone!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel!",
            closeOnConfirm: false,
            closeOnCancel: false
        },
        function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '/expense-payment/' + payment_id,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success(response.message);
                            // Refresh payment history modal and expense list
                            var expense_id = response.data.expense_id;
                            setTimeout(function() {
                                loadPaymentHistory(expense_id);
                                refreshExpenseData();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error("Failed to delete payment");
                    }
                });
            } else {
                swal("Cancelled", "The payment is safe :)", "error");
            }
        });
    });

    // Open edit payment modal
    function openEditPaymentModal(payment) {
        $('#editPaymentExpenseNo').text(payment.expense.expense_no);
        $('#editPaymentTotalAmount').text('Rs.' + parseFloat(payment.expense.total_amount).toFixed(2));
        $('#edit_payment_id').val(payment.id);
        $('#edit_expense_id').val(payment.expense_id);
        $('#edit_payment_amount').val(payment.amount);
        $('#edit_payment_method').val(payment.payment_method);
        $('#edit_payment_date').val(payment.payment_date);
        $('#edit_payment_reference').val(payment.reference_no);
        $('#edit_payment_note').val(payment.note);
        $('#editPaymentModal').modal('show');
    }

    // Submit edit payment form
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var payment_id = $('#edit_payment_id').val();
        var formData = {
            payment_amount: $('#edit_payment_amount').val(),
            payment_method: $('#edit_payment_method').val(),
            payment_date: $('#edit_payment_date').val(),
            payment_reference: $('#edit_payment_reference').val(),
            payment_note: $('#edit_payment_note').val(),
            _token: '{{ csrf_token() }}',
            _method: 'PUT'
        };
        
        $.ajax({
            url: '/expense-payment/' + payment_id,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 200) {
                    toastr.success(response.message);
                    $('#editPaymentModal').modal('hide');
                    // Get the expense ID from the response or form
                    var expense_id = response.data.expense_id;
                    
                    // Refresh both payment history and expense list
                    setTimeout(function() {
                        loadPaymentHistory(expense_id);
                        refreshExpenseData();
                    }, 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('Error updating payment');
                }
            }
        });
    });

    // Helper function to reload payment history
    function loadPaymentHistory(expense_id) {
        $.ajax({
            url: '/expense-payment-history/' + expense_id,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    // Update the payment history modal with fresh data
                    showPaymentHistory(response.data);
                } else {
                    toastr.error('Failed to refresh payment history');
                }
            },
            error: function() {
                toastr.error('Error refreshing payment history');
            }
        });
    }
    
    // Function to refresh expense list data
    function refreshExpenseData() {
        if (typeof loadExpenses === 'function') {
            loadExpenses(); // Refresh the main expense list
        }
    }
</script>