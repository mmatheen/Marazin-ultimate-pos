@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Tax Rates</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item">Products</li>
                            <li class="breadcrumb-item active">Tax Rates</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card card-table">
                <div class="card-body">
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col-auto text-end float-end ms-auto download-grp">
                                @can('edit business-settings')
                                    <button type="button" class="btn btn-outline-info" id="addTaxRateButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="taxRateTable" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Rate %</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addEditTaxRateModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3 id="modalTitle">New Tax Rate</h3>
                    </div>
                    <form id="taxRateAddAndUpdateForm">
                        @csrf
                        <input type="hidden" id="edit_id" name="edit_id">

                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="edit_name" name="name" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="edit_rate" class="form-label">Rate % <span class="text-danger">*</span></label>
                            <input type="number" id="edit_rate" name="rate" min="0" max="100" step="0.01" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="edit_is_active" class="form-label">Status</label>
                            <select id="edit_is_active" name="is_active" class="form-control form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="modal-btn delete-action">
                            <div class="row">
                                <div class="col-6">
                                    <button type="submit" id="modalButton" class="btn btn-primary paid-continue-btn w-100">Save</button>
                                </div>
                                <div class="col-6">
                                    <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3 id="deleteName">Delete Tax Rate</h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="deleting_id">
                            <div class="col-6">
                                <button type="button" class="confirm_delete_btn btn btn-primary paid-continue-btn w-100">Delete</button>
                            </div>
                            <div class="col-6">
                                <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn w-100">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            if (!$('#taxRateTable').length) {
                return;
            }

            var table = $('#taxRateTable').DataTable();

            var addAndUpdateValidationOptions = {
                rules: {
                    name: { required: true },
                    rate: { required: true, number: true, min: 0, max: 100 }
                },
                messages: {
                    name: { required: 'Tax rate name is required' },
                    rate: { required: 'Tax rate percent is required' }
                },
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                },
                highlight: function (element) {
                    $(element).addClass('is-invalidRed').removeClass('is-validGreen');
                },
                unhighlight: function (element) {
                    $(element).removeClass('is-invalidRed').addClass('is-validGreen');
                }
            };

            $('#taxRateAddAndUpdateForm').validate(addAndUpdateValidationOptions);

            function resetFormAndValidation() {
                $('#taxRateAddAndUpdateForm')[0].reset();
                $('#taxRateAddAndUpdateForm').validate().resetForm();
                $('#taxRateAddAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
                $('#taxRateAddAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
                $('#edit_id').val('');
                $('#edit_is_active').val('1');
            }

            $('#addEditTaxRateModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

            function showFetchData() {
                $.ajax({
                    url: '/tax-rates-get-all',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        table.clear().draw();
                        var counter = 1;

                        (response.message || []).forEach(function (item) {
                            var statusBadge = item.is_active
                                ? '<span class="badge bg-success">Active</span>'
                                : '<span class="badge bg-secondary">Inactive</span>';

                            var actionHtml =
                                '@can("edit business-settings")<button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                                '@can("edit business-settings")<button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan';

                            var row = $('<tr>');
                            row.append('<td>' + counter + '</td>');
                            row.append('<td>' + item.name + '</td>');
                            row.append('<td>' + (parseFloat(item.rate || 0).toFixed(2)) + '</td>');
                            row.append('<td>' + statusBadge + '</td>');
                            row.append('<td>' + actionHtml + '</td>');
                            table.row.add(row).draw(false);
                            counter++;
                        });
                    }
                });
            }

            showFetchData();

            $('#addTaxRateButton').on('click', function () {
                $('#modalTitle').text('New Tax Rate');
                $('#modalButton').text('Save');
                resetFormAndValidation();
                $('#addEditTaxRateModal').modal('show');
            });

            $(document).on('click', '.edit_btn', function () {
                var id = $(this).val();
                $('#modalTitle').text('Edit Tax Rate');
                $('#modalButton').text('Update');

                $.ajax({
                    url: '/tax-rates-edit/' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response.status !== 200) {
                            toastr.error(response.message || 'Unable to load tax rate.', 'Error');
                            return;
                        }

                        var taxRate = response.message;
                        $('#edit_id').val(taxRate.id);
                        $('#edit_name').val(taxRate.name);
                        $('#edit_rate').val(parseFloat(taxRate.rate || 0).toFixed(2));
                        $('#edit_is_active').val(taxRate.is_active ? '1' : '0');
                        $('#addEditTaxRateModal').modal('show');
                    },
                    error: function () {
                        toastr.error('Unable to load tax rate.', 'Error');
                    }
                });
            });

            $('#taxRateAddAndUpdateForm').on('submit', function (e) {
                e.preventDefault();

                if (!$('#taxRateAddAndUpdateForm').valid()) {
                    toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
                    return;
                }

                var id = $('#edit_id').val();
                var formData = new FormData(this);
                var url = id ? '/tax-rates/' + id : '/tax-rates';

                if (id) {
                    formData.append('_method', 'PUT');
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function (response) {
                        $('#addEditTaxRateModal').modal('hide');
                        showFetchData();
                        toastr.success(response.message || 'Tax rate saved successfully.', id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    },
                    error: function (xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            $.each(xhr.responseJSON.errors, function (key, messages) {
                                if (messages && messages.length) {
                                    toastr.error(messages[0], 'Error');
                                }
                            });
                            return;
                        }
                        toastr.error(xhr.responseJSON?.message || 'Failed to save tax rate.', 'Error');
                    }
                });
            });

            $(document).on('click', '.delete_btn', function () {
                var id = $(this).val();
                $('#deleting_id').val(id);
                $('#deleteModal').modal('show');
            });

            $(document).on('click', '.confirm_delete_btn', function () {
                var id = $('#deleting_id').val();

                $.ajax({
                    url: '/tax-rates/' + id,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    dataType: 'json',
                    success: function (response) {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        toastr.success(response.message || 'Tax rate deleted successfully.', 'Deleted');
                    },
                    error: function (xhr) {
                        $('#deleteModal').modal('hide');
                        toastr.error(xhr.responseJSON?.message || 'Failed to delete tax rate.', 'Error');
                    }
                });
            });
        });
    </script>
</div>
@endsection
