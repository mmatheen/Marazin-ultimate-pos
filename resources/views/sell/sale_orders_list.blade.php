@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Sale Orders List</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('list-sale') }}">Sell</a></li>
                                <li class="breadcrumb-item active">Sale Orders</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-table comman-shadow">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h3 class="page-title">All Sale Orders</h3>
                                    </div>
                                    <div class="col-auto text-end float-end ms-auto download-grp">
                                        <a href="{{ route('pos-create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Create New Order
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="mb-3">
                                <button class="btn btn-sm btn-outline-primary filter-status" data-status="all">
                                    All Orders
                                </button>
                                <button class="btn btn-sm btn-outline-warning filter-status" data-status="pending">
                                    Pending
                                </button>
                                <button class="btn btn-sm btn-outline-info filter-status" data-status="confirmed">
                                    Confirmed
                                </button>
                                <button class="btn btn-sm btn-outline-secondary filter-status" data-status="in_progress">
                                    In Progress
                                </button>
                                <button class="btn btn-sm btn-outline-success filter-status" data-status="completed">
                                    Completed
                                </button>
                                <button class="btn btn-sm btn-outline-danger filter-status" data-status="cancelled">
                                    Cancelled
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="saleOrdersTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Actions</th>
                                            <th>Order Date</th>
                                            <th>Order Number</th>
                                            <th>Customer</th>
                                            <th>Mobile</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Expected Delivery</th>
                                            <th>Order Total</th>
                                            <th>Items</th>
                                            <th>Sales Rep</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be populated by DataTables -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">Sale Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Order Information:</h5>
                            <p id="orderDetails"></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Customer Information:</h5>
                            <p id="customerDetails"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-4">
                            <h5>Products:</h5>
                            <table class="table table-bordered" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Product rows will be inserted here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-4">
                            <h5>Amount Details:</h5>
                            <table class="table" id="amountDetailsTable">
                                <tbody>
                                    <!-- Amount details will be inserted here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 CSS (for modern Swal.fire() syntax) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- SweetAlert2 JS (for modern Swal.fire() syntax) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let currentFilter = 'all';

            // Initialize DataTable
            var table = $('#saleOrdersTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '/sales',
                    type: 'GET',
                    dataSrc: function(json) {
                        // Filter only 'sale_order' transaction type
                        if (json.sales && Array.isArray(json.sales)) {
                            let saleOrders = json.sales.filter(function(item) {
                                return item.transaction_type === 'sale_order';
                            });
                            
                            // Apply status filter
                            if (currentFilter !== 'all') {
                                saleOrders = saleOrders.filter(function(item) {
                                    return item.order_status === currentFilter;
                                });
                            }
                            
                            return saleOrders;
                        }
                        return [];
                    }
                },
                order: [[1, 'desc']], // Order by date descending
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = `
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="feather-menu"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><button type="button" value="${row.id}" class="view-details dropdown-item"><i class="feather-eye text-info"></i> View</button></li>
                            `;
                            
                            // Only show convert button if not completed or cancelled
                            if (row.order_status !== 'completed' && row.order_status !== 'cancelled') {
                                actions += `
                                        <li><button type="button" value="${row.id}" class="change-status dropdown-item"><i class="feather-refresh-cw text-primary"></i> Change Status</button></li>
                                        <li><button type="button" value="${row.id}" class="convert-invoice dropdown-item"><i class="feather-file-text text-success"></i> Convert to Invoice</button></li>
                                        <li><button type="button" value="${row.id}" class="edit_btn dropdown-item"><i class="feather-edit text-info"></i> Edit</button></li>
                                        <li><button type="button" value="${row.id}" class="cancel-order dropdown-item"><i class="feather-x text-warning"></i> Cancel Order</button></li>
                                `;
                            }
                            
                            if (row.order_status !== 'completed') {
                                actions += `
                                        <li><button type="button" value="${row.id}" class="delete_btn dropdown-item"><i class="feather-trash-2 text-danger"></i> Delete</button></li>
                                `;
                            }
                            
                            actions += `
                                    </ul>
                                </div>
                            `;
                            return actions;
                        }
                    },
                    {
                        data: 'order_date',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'order_number',
                        render: function(data) {
                            return `<strong>${data}</strong>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return row.customer ? (row.customer.first_name + ' ' + row.customer.last_name) : '';
                        }
                    },
                    {
                        data: 'customer.mobile_no',
                        defaultContent: ''
                    },
                    {
                        data: 'location.name',
                        defaultContent: ''
                    },
                    {
                        data: 'order_status',
                        render: function(data) {
                            const badges = {
                                'pending': '<span class="badge bg-warning">Pending</span>',
                                'confirmed': '<span class="badge bg-info">Confirmed</span>',
                                'in_progress': '<span class="badge bg-primary">In Progress</span>',
                                'ready': '<span class="badge bg-success">Ready</span>',
                                'delivered': '<span class="badge bg-success">Delivered</span>',
                                'completed': '<span class="badge bg-dark">Completed</span>',
                                'cancelled': '<span class="badge bg-danger">Cancelled</span>',
                                'on_hold': '<span class="badge bg-secondary">On Hold</span>'
                            };
                            return badges[data] || '<span class="badge bg-secondary">' + data + '</span>';
                        }
                    },
                    {
                        data: 'expected_delivery_date',
                        render: function(data) {
                            if (!data) return '-';
                            
                            // Check if delivery date is past
                            const deliveryDate = new Date(data);
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);
                            
                            if (deliveryDate < today) {
                                return `<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${data}</span>`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'final_total',
                        render: function(data) {
                            return 'Rs. ' + parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'products',
                        render: function(data) {
                            return data ? data.length : 0;
                        }
                    },
                    {
                        data: 'user.user_name',
                        defaultContent: ''
                    },
                    {
                        data: 'order_notes',
                        render: function(data) {
                            if (!data) return '-';
                            if (data.length > 30) {
                                return '<span data-bs-toggle="tooltip" title="' + data + '">' + 
                                       data.substring(0, 30) + '...</span>';
                            }
                            return data;
                        }
                    }
                ],
                drawCallback: function() {
                    // Initialize tooltips after table draw
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });

            // Filter button click handlers
            $('.filter-status').on('click', function() {
                currentFilter = $(this).data('status');
                
                // Update button states
                $('.filter-status').removeClass('active');
                $(this).addClass('active');
                
                // Reload table
                table.ajax.reload();
            });

            // Set initial active filter
            $('.filter-status[data-status="all"]').addClass('active');

            // View Details Handler
            $('#saleOrdersTable tbody').on('click', '.view-details', function() {
                var saleId = $(this).val();
                var data = table.row($(this).parents('tr')).data();
                
                // Populate modal with sale order details
                let orderInfo = `
                    <strong>Order Number:</strong> ${data.order_number}<br>
                    <strong>Order Date:</strong> ${data.order_date || '-'}<br>
                    <strong>Expected Delivery:</strong> ${data.expected_delivery_date || '-'}<br>
                    <strong>Status:</strong> ${data.order_status}<br>
                    <strong>Location:</strong> ${data.location ? data.location.name : ''}<br>
                    <strong>Sales Rep:</strong> ${data.user ? data.user.user_name : ''}<br>
                    <strong>Notes:</strong> ${data.order_notes || 'No notes'}
                `;
                
                let customerInfo = `
                    <strong>Name:</strong> ${data.customer ? (data.customer.first_name + ' ' + data.customer.last_name) : ''}<br>
                    <strong>Mobile:</strong> ${data.customer ? data.customer.mobile_no : ''}<br>
                    <strong>Email:</strong> ${data.customer ? (data.customer.email || '-') : ''}<br>
                    <strong>Address:</strong> ${data.customer ? (data.customer.address || '-') : ''}
                `;
                
                $('#orderDetails').html(orderInfo);
                $('#customerDetails').html(customerInfo);
                
                // Populate products table
                let productsHtml = '';
                if (data.products && data.products.length > 0) {
                    data.products.forEach(function(item, index) {
                        productsHtml += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${item.product ? item.product.product_name : ''}</td>
                                <td>${item.product ? item.product.sku : ''}</td>
                                <td>${item.quantity}</td>
                                <td>Rs. ${parseFloat(item.price).toFixed(2)}</td>
                                <td>Rs. ${(item.quantity * item.price).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }
                $('#productsTable tbody').html(productsHtml);
                
                // Populate amount details
                let amountHtml = `
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-end">Rs. ${parseFloat(data.subtotal || 0).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td><strong>Discount (${data.discount_type}):</strong></td>
                        <td class="text-end">Rs. ${parseFloat(data.discount_amount || 0).toFixed(2)}</td>
                    </tr>
                    <tr>
                        <td><strong>Final Total:</strong></td>
                        <td class="text-end"><strong>Rs. ${parseFloat(data.final_total).toFixed(2)}</strong></td>
                    </tr>
                `;
                $('#amountDetailsTable tbody').html(amountHtml);
                
                // Show modal
                $('#viewDetailsModal').modal('show');
            });

            // Change Status Handler
            $('#saleOrdersTable tbody').on('click', '.change-status', function() {
                var saleId = $(this).val();
                var data = table.row($(this).parents('tr')).data();
                
                Swal.fire({
                    title: 'Change Order Status',
                    html: `
                        <p>Change status for Sale Order: <strong>${data.order_number}</strong></p>
                        <div class="text-start mt-3">
                            <label class="form-label">Current Status: <span class="badge bg-info">${data.order_status}</span></label>
                            <select id="newStatus" class="form-select">
                                <option value="">-- Select New Status --</option>
                                <option value="pending" ${data.order_status === 'pending' ? 'disabled' : ''}>Pending</option>
                                <option value="confirmed" ${data.order_status === 'confirmed' ? 'disabled' : ''}>Confirmed</option>
                                <option value="in_progress" ${data.order_status === 'in_progress' ? 'disabled' : ''}>In Progress</option>
                                <option value="ready" ${data.order_status === 'ready' ? 'disabled' : ''}>Ready</option>
                                <option value="delivered" ${data.order_status === 'delivered' ? 'disabled' : ''}>Delivered</option>
                                <option value="on_hold" ${data.order_status === 'on_hold' ? 'disabled' : ''}>On Hold</option>
                            </select>
                            <label class="form-label mt-3">Status Note (Optional):</label>
                            <textarea id="statusNote" class="form-control" rows="2" placeholder="Add note about status change..."></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Update Status',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const newStatus = document.getElementById('newStatus').value;
                        const statusNote = document.getElementById('statusNote').value;
                        
                        if (!newStatus) {
                            Swal.showValidationMessage('Please select a new status');
                            return false;
                        }
                        
                        return { newStatus, statusNote };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { newStatus, statusNote } = result.value;
                        
                        // Prepare note
                        let updatedNotes = data.order_notes || '';
                        if (statusNote) {
                            const timestamp = new Date().toLocaleString();
                            updatedNotes += `\n\n[${timestamp}] Status changed to: ${newStatus}\nNote: ${statusNote}`;
                        }
                        
                        // Update status via AJAX
                        $.ajax({
                            url: `/sales/update/${saleId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: JSON.stringify({
                                order_status: newStatus,
                                order_notes: updatedNotes
                            }),
                            contentType: 'application/json',
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Status Updated!',
                                    text: `Order status changed to: ${newStatus}`,
                                    timer: 2000
                                });
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', 'Failed to update status', 'error');
                            }
                        });
                    }
                });
            });

            // Convert to Invoice Handler
            $('#saleOrdersTable tbody').on('click', '.convert-invoice', function() {
                var saleId = $(this).val();
                var data = table.row($(this).parents('tr')).data();
                
                Swal.fire({
                    title: 'Convert to Invoice?',
                    html: `
                        <p>Converting Sale Order <strong>${data.order_number}</strong> to Invoice will:</p>
                        <ul class="text-start">
                            <li>Create a new invoice</li>
                            <li>Reduce stock quantities</li>
                            <li>Mark this order as completed</li>
                            <li>Redirect to payment page</li>
                        </ul>
                        <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Convert to Invoice!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Converting...',
                            text: 'Please wait while we convert the sale order to invoice',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Send AJAX request
                        $.ajax({
                            url: `/sale-orders/convert-to-invoice/${saleId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: `Sale Order converted to Invoice <strong>${response.invoice.invoice_no}</strong>`,
                                    icon: 'success',
                                    timer: 2000
                                }).then(() => {
                                    // Redirect to invoice edit page for payment
                                    window.location.href = response.redirect_url;
                                });
                            },
                            error: function(xhr) {
                                let errorMsg = 'Failed to convert sale order';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    }
                });
            });

            // Edit Handler
            $('#saleOrdersTable tbody').on('click', '.edit_btn', function() {
                var saleId = $(this).val();
                window.location.href = `/sales/edit/${saleId}`;
            });

            // Cancel Order Handler
            $('#saleOrdersTable tbody').on('click', '.cancel-order', function() {
                var saleId = $(this).val();
                var data = table.row($(this).parents('tr')).data();
                
                Swal.fire({
                    title: 'Cancel Order?',
                    text: `Are you sure you want to cancel Sale Order ${data.order_number}?`,
                    icon: 'warning',
                    input: 'textarea',
                    inputPlaceholder: 'Enter cancellation reason...',
                    inputAttributes: {
                        'aria-label': 'Enter cancellation reason'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Cancel Order!',
                    preConfirm: (reason) => {
                        if (!reason) {
                            Swal.showValidationMessage('Please provide a cancellation reason')
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Update order status to cancelled
                        $.ajax({
                            url: `/sales/update/${saleId}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: JSON.stringify({
                                order_status: 'cancelled',
                                order_notes: (data.order_notes || '') + '\n\nCancelled: ' + result.value
                            }),
                            contentType: 'application/json',
                            success: function(response) {
                                toastr.success('Sale Order cancelled successfully');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error('Failed to cancel order');
                            }
                        });
                    }
                });
            });

            // Delete Handler
            $('#saleOrdersTable tbody').on('click', '.delete_btn', function() {
                var saleId = $(this).val();
                var data = table.row($(this).parents('tr')).data();
                
                Swal.fire({
                    title: 'Delete Sale Order?',
                    text: `Are you sure you want to delete Sale Order ${data.order_number}? This action cannot be undone!`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/sales/delete/${saleId}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                toastr.success('Sale Order deleted successfully');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error('Failed to delete sale order');
                            }
                        });
                    }
                });
            });
        });
    </script>

    <style>
        .filter-status.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
    </style>
@endsection
