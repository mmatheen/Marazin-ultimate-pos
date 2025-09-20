@extends('layout.layout')
@section('title', 'Discounts Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Discounts Management</h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#createDiscountModal">
                            <i class="fas fa-plus"></i> Add New Discount
                        </button>
                        <div class="btn-group ml-2">
                            <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item export-btn" href="#" data-type="xlsx">Excel</a>
                                <a class="dropdown-item export-btn" href="#" data-type="csv">CSV</a>
                                <a class="dropdown-item export-btn" href="#" data-type="pdf">PDF</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label>Date From</label>
                            <input type="taxt" class="form-control datetimepicker" placeholder="DD-MM-YYYY" id="filter_from">
                        </div>
                        <div class="col-md-3">
                            <label>Date To</label>
                            <input type="taxt" class="form-control datetimepicker" placeholder="DD-MM-YYYY" id="filter_to">
                        </div>
                        <div class="col-md-3">
                            <label>Status</label>
                            <select class="form-control" id="filter_status">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary me-2" id="apply_filters">Apply</button>
                            <button class="btn btn-secondary ml-2" id="reset_filters">Reset</button>
                        </div>
                    </div>

                    <table id="discounts-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Discount Modal -->
<div class="modal fade" id="createDiscountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Discount</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createDiscountForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" class="form-control" name="name" required>
                                <div class="invalid-feedback" id="name-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                                <div class="invalid-feedback" id="description-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Type *</label>
                                <select class="form-control" name="type" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                                <div class="invalid-feedback" id="type-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="amount" required>
                                <div class="invalid-feedback" id="amount-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" name="start_date" required>
                                <div class="invalid-feedback" id="start_date-error"></div>
                            </div>
                            <div class="form-group">
                                <label>End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date">
                                <div class="invalid-feedback" id="end_date-error"></div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                    <label class="form-check-label">Active</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Apply to Products (Leave empty for all products)</label>
                                <select class="form-control select2" name="product_ids[]" multiple style="width: 100%;">
                                    @foreach(\App\Models\Product::all() as $product)
                                        <option value="{{ $product->id }}">{{ $product->product_name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="product_ids-error"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>

        <!-- Apply Discount Modal -->
        <div class="modal fade" id="applyDiscountModal" tabindex="-1" role="dialog" aria-labelledby="applyDiscountModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="applyDiscountModalLabel">Apply Discount to Selected Products</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="discountForm">
                            <div class="form-group">
                                <label for="discountName" class="form-label">Discount Name</label>
                                <input type="text" class="form-control" id="discountName" required>
                            </div>
                            <div class="form-group">
                                <label for="discountDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="discountDescription" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="discountType" class="form-label">Discount Type</label>
                                <select class="form-control" id="discountType" required>
                                    <option value="">Select Type</option>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discountAmount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="discountAmount" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" id="startDate" required>
                            </div>
                            <div class="form-group">
                                <label for="endDate" class="form-label">End Date (Optional)</label>
                                <input type="datetime-local" class="form-control" id="endDate">
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="isActive">
                                    <label class="form-check-label" for="isActive">Active</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveDiscountButton">Save Discount</button>
                    </div>
                </div>
            </div>
        </div>

<!-- Edit Discount Modal -->
<div class="modal fade" id="editDiscountModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Discount</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDiscountForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_discount_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                                <div class="invalid-feedback" id="edit_name-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                                <div class="invalid-feedback" id="edit_description-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Type *</label>
                                <select class="form-control" name="type" id="edit_type" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                                <div class="invalid-feedback" id="edit_type-error"></div>
                            </div>
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required>
                                <div class="invalid-feedback" id="edit_amount-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                                <div class="invalid-feedback" id="edit_start_date-error"></div>
                            </div>
                            <div class="form-group">
                                <label>End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date" id="edit_end_date">
                                <div class="invalid-feedback" id="edit_end_date-error"></div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                    <label class="form-check-label">Active</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Apply to Products (Leave empty for all products)</label>
                                <select class="form-control select2-edit" name="product_ids[]" id="edit_product_ids" multiple style="width: 100%;">
                                    @foreach(\App\Models\Product::all() as $product)
                                        <option value="{{ $product->id }}">{{ $product->product_name }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="edit_product_ids-error"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Products Modal -->
<div class="modal fade" id="productsModal" tabindex="-1" role="dialog" aria-labelledby="productsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productsModalLabel">Discounts Products</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: "Select products",
        allowClear: true
    });

    const today = new Date().toISOString().split('T')[0];
    $('#filter_from').val(today);

    // Function to clear validation errors
    function clearValidationErrors(formId) {
        $(formId + ' .form-control').removeClass('is-invalid');
        $(formId + ' .invalid-feedback').text('').hide();
    }

    // Function to display validation errors
    function displayValidationErrors(errors, formId) {
        clearValidationErrors(formId);
        $.each(errors, function(field, messages) {
            var fieldElement = $(formId + ' [name="' + field + '"]');
            var errorElement = $(formId + ' #' + (formId === '#editDiscountForm' ? 'edit_' : '') + field.replace('.', '_') + '-error');
            
            if (fieldElement.length) {
                fieldElement.addClass('is-invalid');
            }
            if (errorElement.length) {
                errorElement.text(messages[0]).show();
            }
        });
    }

    const table = $('#discounts-table').DataTable({
        ajax: {
            url: '/discounts/data',
            dataSrc: '',
            data: function (d) {
                d.from = $('#filter_from').val();
                d.to = $('#filter_to').val();
                d.status = $('#filter_status').val() === '' ? null : $('#filter_status').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'description' },
            {
                data: 'type',
                render: function(data) {
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            {
                data: 'amount',
                render: function(data, type, row) {
                    if (row.type === 'percentage') {
                        return data ? data + '%' : '-';
                    } else if (row.type === 'fixed') {
                        return data ? 'Rs. ' + data : '-';
                    }
                    return '-';
                }
            },
            {
                data: 'start_date',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : '-';
                }
            },
            {
                data: 'end_date',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : 'No end date';
                }
            },
            {
                data: 'is_active',
                render: function (data) {
                    return data ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                }
            },
            {
                data: 'products_count',
                render: function(data) {
                    return data || 0;
                }
            },
            {
                data: null,
                render: function (data) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary edit-discount" data-id="${data.id}">Edit</button>
                            <button class="btn btn-sm btn-danger delete-discount" data-id="${data.id}">Delete</button>
                            <button class="btn btn-sm btn-info view-products" data-id="${data.id}">Products</button>
                            <button class="btn btn-sm ${data.is_active ? 'btn-warning' : 'btn-success'} toggle-status" data-id="${data.id}">
                                ${data.is_active ? 'Deactivate' : 'Activate'}
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });

    // Filter buttons
    $('#apply_filters').click(function () {
        table.ajax.reload();
    });

    $('#reset_filters').click(function () {
        $('#filter_from').val(today);
        $('#filter_to').val('');
        $('#filter_status').val('');
        table.ajax.reload();
    });

    // Create Discount - Show Modal
    $('[data-target="#createDiscountModal"]').click(function() {
        $('#createDiscountForm')[0].reset();
        $('.select2').val(null).trigger('change');
        clearValidationErrors('#createDiscountForm');
        // Set today's date as default for start date
        $('#createDiscountForm input[name="start_date"]').val(today);
        $('#createDiscountModal').modal('show');
    });

    // Create Discount - Submit Form
    $('#createDiscountForm').submit(function(e) {
        e.preventDefault();
        clearValidationErrors('#createDiscountForm');

        // Get selected product IDs
        var productIds = $('#createDiscountForm select[name="product_ids[]"]').val();

        // Prepare form data
        var formData = $(this).serializeArray();
        if (productIds && productIds.length > 0) {
            formData.push({name: 'apply_to_all', value: false});
        } else {
            formData.push({name: 'apply_to_all', value: true});
        }

        $.ajax({
            url: "{{ route('discounts.store') }}",
            type: 'POST',
            data: $.param(formData),
            success: function(response) {
                $('#createDiscountModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.message || 'Discount created successfully');
                $('#createDiscountForm')[0].reset();
                $('.select2').val(null).trigger('change');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors, '#createDiscountForm');
                    // Also show toastr for first error
                    var firstError = Object.values(errors)[0][0];
                    toastr.error(firstError);
                } else {
                    toastr.error('An error occurred while creating the discount');
                }
            }
        });
    });

    // Edit Discount - Load Data
    $('#discounts-table').on('click', '.edit-discount', function() {
        var discountId = $(this).data('id');

        $.get("{{ route('discounts.index') }}/" + discountId + "/edit", function(data) {
            $('#edit_discount_id').val(data.id);
            $('#edit_name').val(data.name);
            $('#edit_description').val(data.description);
            $('#edit_type').val(data.type);
            $('#edit_amount').val(data.amount);
            $('#edit_start_date').val(data.start_date.split('T')[0]);
            $('#edit_end_date').val(data.end_date ? data.end_date.split('T')[0] : '');
            $('#edit_is_active').prop('checked', data.is_active);

            // Clear any existing validation errors
            clearValidationErrors('#editDiscountForm');

            // Initialize Select2 for edit modal
            $('.select2-edit').select2({
                placeholder: "Select products",
                allowClear: true
            });

            // Set selected products
            var productIds = data.products.map(product => product.id);
            $('#edit_product_ids').val(productIds).trigger('change');

            $('#editDiscountModal').modal('show');
        });
    });

    // Update Discount
    $('#editDiscountForm').submit(function(e) {
        e.preventDefault();
        clearValidationErrors('#editDiscountForm');
        var discountId = $('#edit_discount_id').val();

        $.ajax({
            url: "{{ route('discounts.index') }}/" + discountId,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#editDiscountModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.message || 'Discount updated successfully');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors, '#editDiscountForm');
                    // Also show toastr for first error
                    var firstError = Object.values(errors)[0][0];
                    toastr.error(firstError);
                } else {
                    toastr.error('An error occurred while updating the discount');
                }
            }
        });
    });

    // View Products
    $('#discounts-table').on('click', '.view-products', function() {
        var discountId = $(this).data('id');

        $.ajax({
            url: `/discounts/${discountId}/products`,
            type: 'GET',
            success: function(data) {
                var tbody = $('#productsTable tbody');
                tbody.empty();

                if (data.length > 0) {
                    $.each(data, function(index, product) {
                        tbody.append('<tr><td>' +  (index + 1)  + '</td><td>' + product.product_name +
                                    '</td><td>' + product.sku + '</td></tr>');
                    });


                }

                 else {
                    tbody.append('<tr><td colspan="3" class="text-center">No products associated</td></tr>');
                }

                $('#productsModal').modal('show');
            },

            error: function(xhr) {
                toastr.error('Error fetching products');
            }
        });
    });

    // Delete Discount
    $('#discounts-table').on('click', '.delete-discount', function() {
        var discountId = $(this).data('id');

        if (confirm('Are you sure you want to delete this discount?')) {
            $.ajax({
                url: "{{ route('discounts.index') }}/" + discountId,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    table.ajax.reload();
                    toastr.success(response.message || 'Discount deleted successfully');
                },
                error: function(xhr) {
                    toastr.error('Error deleting discount');
                }
            });
        }
    });

    // Toggle Status
    $('#discounts-table').on('click', '.toggle-status', function() {
        var discountId = $(this).data('id');
        var button = $(this);

        $.post("{{ route('discounts.index') }}/" + discountId + "/toggle-status", {
            _token: "{{ csrf_token() }}"
        }, function(response) {
            table.ajax.reload();
            toastr.success(response.message || 'Discount status updated successfully');
        }).fail(function() {
            toastr.error('Error updating discount status');
        });
    });

    // Clear validation errors when modals are shown
    $('#createDiscountModal').on('show.bs.modal', function() {
        clearValidationErrors('#createDiscountForm');
    });

    $('#editDiscountModal').on('show.bs.modal', function() {
        clearValidationErrors('#editDiscountForm');
    });

    // Clear validation errors when modals are hidden
    $('#createDiscountModal').on('hidden.bs.modal', function() {
        clearValidationErrors('#createDiscountForm');
        $('#createDiscountForm')[0].reset();
        $('.select2').val(null).trigger('change');
    });

    $('#editDiscountModal').on('hidden.bs.modal', function() {
        clearValidationErrors('#editDiscountForm');
        $('#editDiscountForm')[0].reset();
        $('.select2-edit').val(null).trigger('change');
    });

    // Additional event handlers for close buttons (backup)
    $(document).on('click', '[data-dismiss="modal"], [data-bs-dismiss="modal"]', function(e) {
        e.preventDefault();
        var modal = $(this).closest('.modal');
        console.log('Close button clicked for modal:', modal.attr('id'));
        
        // Try multiple ways to close the modal
        modal.modal('hide');
        
        // Backup method - manually remove modal classes and backdrop
        setTimeout(function() {
            if (modal.hasClass('show')) {
                modal.removeClass('show');
                modal.css('display', 'none');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        }, 100);
    });

    // Test Bootstrap functionality
    console.log('Bootstrap version:', typeof $.fn.modal !== 'undefined' ? 'Available' : 'Not Available');
    console.log('jQuery version:', $.fn.jquery);
    
    // Test modal opening (support both Bootstrap 4 and 5)
    $(document).on('click', '[data-toggle="modal"], [data-bs-toggle="modal"]', function(e) {
        e.preventDefault();
        var targetModal = $(this).attr('data-target') || $(this).attr('data-bs-target');
        console.log('Opening modal:', targetModal);
        $(targetModal).modal('show');
    });
});
    </script>

<style>
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .btn-group {
        white-space: nowrap;
    }
    .btn-group .btn {
        float: none;
    }
</style>
@endsection


