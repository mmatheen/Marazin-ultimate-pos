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
                            <input type="date" class="form-control" id="filter_from">
                        </div>
                        <div class="col-md-3">
                            <label>Date To</label>
                            <input type="date" class="form-control" id="filter_to">
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
                            <button class="btn btn-primary" id="apply_filters">Apply</button>
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
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Type *</label>
                                <select class="form-control" name="type" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label>End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date">
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
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Type *</label>
                                <select class="form-control" name="type" id="edit_type" required>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" class="form-control" name="start_date" id="edit_start_date" required>
                            </div>
                            <div class="form-group">
                                <label>End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date" id="edit_end_date">
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
<div class="modal fade" id="productsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Associated Products</h5>
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

    // Initialize DataTable with filters
    var table = $('#discounts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/discounts/data',
            data: function(d) {
                d.from = $('#filter_from').val();
                d.to = $('#filter_to').val();
                d.status = $('#filter_status').val();
            },
            error: function(xhr, error, thrown) {
                console.error("Error occurred: ", error, thrown);
            }
        },
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
            { data: 'type', name: 'type' },
            { data: 'amount', name: 'amount' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'is_active', name: 'is_active' },
            { data: 'products_count', name: 'products_count' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        responsive: true,
        order: [[0, 'desc']]
    });

    // Apply filters
    $('#apply_filters').click(function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#reset_filters').click(function() {
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#filter_status').val('');
        table.ajax.reload();
    });

    // Export buttons
    $('.export-btn').click(function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var from = $('#filter_from').val();
        var to = $('#filter_to').val();
        
        window.location.href = "{{ route('discounts.export') }}?type=" + type + 
            "&from=" + from + "&to=" + to;
    });

    // Create Discount
    $('#createDiscountForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "{{ route('discounts.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#createDiscountModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.success);
                $('#createDiscountForm')[0].reset();
                $('.select2').val(null).trigger('change');
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value[0]);
                });
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
        var discountId = $('#edit_discount_id').val();
        
        $.ajax({
            url: "{{ route('discounts.index') }}/" + discountId,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#editDiscountModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.success);
            },
            error: function(xhr) {
                var errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value[0]);
                });
            }
        });
    });

    // View Products
    $('#discounts-table').on('click', '.view-products', function() {
        var discountId = $(this).data('id');
        
        $.get("{{ route('discounts.index') }}/" + discountId + "/products", function(data) {
            var tbody = $('#productsTable tbody');
            tbody.empty();
            
            if (data.length > 0) {
                $.each(data, function(index, product) {
                    tbody.append('<tr><td>' + product.id + '</td><td>' + product.product_name + 
                                 '</td><td>' + product.sku + '</td></tr>');
                });
            } else {
                tbody.append('<tr><td colspan="3" class="text-center">No products associated</td></tr>');
            }
            
            $('#productsModal').modal('show');
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
                    toastr.success(response.success);
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
            toastr.success(response.success);
        });
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


