@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Sales Rep Targets</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Sales Rep Targets</li>
                                <li class="breadcrumb-item active">List</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Row -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <button type="button" class="btn btn-outline-info" id="addTargetButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="targetsTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Sales Rep</th>
                                        <th>Target Month</th>
                                        <th>Target Amount</th>
                                        <th>Achieved Amount</th>
                                        <th>Achievement %</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="addAndEditTargetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle">Add Sales Target</h5>
                    </div>
                    <form id="targetAddUpdateForm">
                        <input type="hidden" name="id" id="target_id">

                        <div class="mb-3">
                            <label>Sales Representative <span class="text-danger">*</span></label>
                            <select name="sales_rep_id" id="sales_rep_id" class="form-control">
                                <option value="">Select Sales Rep</option>
                            </select>
                            <span class="text-danger" id="sales_rep_id_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Target Month <span class="text-danger">*</span></label>
                            <input type="date" name="target_month" id="target_month" class="form-control">
                            <span class="text-danger" id="target_month_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Target Amount <span class="text-danger">*</span></label>
                            <input type="number" name="target_amount" id="target_amount" class="form-control"
                                placeholder="0.00" step="0.01" min="0">
                            <span class="text-danger" id="target_amount_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Achieved Amount</label>
                            <input type="number" name="achieved_amount" id="achieved_amount" class="form-control"
                                placeholder="0.00" step="0.01" min="0">
                            <span class="text-danger" id="achieved_amount_error"></span>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" id="saveBtn" class="btn btn-outline-primary">Save</button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Sales Target</h3>
                        <p>Are you sure you want to delete this sales target?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="delete_target_id">
                            <div class="col-6">
                                <button type="button" class="btn btn-primary confirm-delete-btn"
                                    style="width: 100%;">Delete</button>
                            </div>
                            <div class="col-6">
                                <a href="#" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // --- Prevent DataTable Reinitialization ---
            if ($.fn.DataTable.isDataTable('#targetsTable')) {
                $('#targetsTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            const table = $('#targetsTable').DataTable({
                processing: false,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/sales-rep-targets') }}",
                    type: "GET",
                    dataSrc: "data",
                    error: function(xhr) {
                        console.log('Error loading sales targets:', xhr);
                        // Don't show toastr error, let table show "No data available"
                        return [];
                    }
                },
                language: {
                    emptyTable: "No sales targets found",
                    zeroRecords: "No sales targets found",
                    loadingRecords: "",
                    processing: ""
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'sales_rep.user.user_name',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'target_month',
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString() : '—';
                        }
                    },
                    {
                        data: 'target_amount',
                        render: function(data) {
                            return data ? parseFloat(data).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) : '0.00';
                        }
                    },
                    {
                        data: 'achieved_amount',
                        render: function(data) {
                            return data ? parseFloat(data).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) : '0.00';
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            const target = parseFloat(data.target_amount) || 0;
                            const achieved = parseFloat(data.achieved_amount) || 0;
                            const percentage = target > 0 ? ((achieved / target) * 100).toFixed(1) :
                                '0.0';
                            const badgeClass = percentage >= 100 ? 'badge-success' : percentage >=
                                75 ? 'badge-warning' : 'badge-danger';
                            return `<span class="badge ${badgeClass}">${percentage}%</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `
                            <button class='btn btn-sm btn-info editBtn' data-id='${data.id}'>Edit</button>
                            <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}'>Delete</button>
                        `;
                        }
                    }
                ]
            });

            // Load dropdown data
            function loadDropdownData() {
                // Load sales reps
                $.get('/api/sales-reps', function(response) {
                    if (response.data) {
                        $('#sales_rep_id').empty().append('<option value="">Select Sales Rep</option>');
                        response.data.forEach(salesRep => {
                            // Use user.user_name if available, fallback to user.name or email
                            let repName = salesRep.user.user_name || salesRep.user.user_name || salesRep.user.email;
                            let vehicle = salesRep.vehicle ? ` (${salesRep.vehicle.vehicle_number})` : '';
                            let location = salesRep.location ? ` - ${salesRep.location.name}` : '';
                            let route = salesRep.route ? ` [${salesRep.route.name}]` : '';
                            $('#sales_rep_id').append(
                                `<option value="${salesRep.id}">${repName}${vehicle}${location}${route}</option>`
                            );
                        });
                    }
                });
            }

            // --- Open Add Modal ---
            $('#addTargetButton').on('click', function() {
                $('#targetAddUpdateForm')[0].reset();
                $('#target_id').val('');
                $('#modalTitle').text('Add Sales Target');
                $('#saveBtn').text('Save');
                $('.text-danger').text('');

                // Set default month to current month
                const today = new Date();
                const currentMonth = today.toISOString().substr(0, 7) + '-01';
                $('#target_month').val(currentMonth);

                loadDropdownData();
                $('#addAndEditTargetModal').modal('show');
            });

            // --- Save or Update Target ---
            $('#targetAddUpdateForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#target_id').val();
                const url = id ? `/api/sales-rep-targets/${id}` : `/api/sales-rep-targets`;
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addAndEditTargetModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Sales target saved successfully.');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        const message = xhr.responseJSON?.message || 'An error occurred.';

                        $('#sales_rep_id_error').text(errors.sales_rep_id || '');
                        $('#target_month_error').text(errors.target_month || '');
                        $('#target_amount_error').text(errors.target_amount || '');
                        $('#achieved_amount_error').text(errors.achieved_amount || '');

                        toastr.error(message);
                    }
                });
            });

            // --- Edit Target ---
            $('#targetsTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                loadDropdownData();

                setTimeout(() => {
                    $.get(`/api/sales-rep-targets/${id}`, function(response) {
                        if (response.status === true && response.data) {
                            const data = response.data;

                            $('#target_id').val(data.id);
                            $('#sales_rep_id').val(data.sales_rep_id);
                            $('#target_month').val(data.target_month);
                            $('#target_amount').val(data.target_amount);
                            $('#achieved_amount').val(data.achieved_amount);

                            $('#modalTitle').text('Edit Sales Target');
                            $('#saveBtn').text('Update');
                            $('#addAndEditTargetModal').modal('show');

                            // Clear previous errors
                            $('.text-danger').text('');
                        } else {
                            toastr.error('Failed to load sales target data.');
                        }
                    }).fail(function() {
                        toastr.error('Sales target not found or server error.');
                    });
                }, 500);
            });

            // --- Open Delete Modal ---
            $('#targetsTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_target_id').val(id);
                $('#deleteModal').modal('show');
            });

            // --- Confirm Delete ---
            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_target_id').val();

                $.ajax({
                    url: `/api/sales-rep-targets/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message ||
                        'Sales target deleted successfully.');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to delete sales target.';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
