<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        populateUnitDropdown();

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                name: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Name is required",
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                error.insertAfter(element);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to both forms
        $('#unitAddAndUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#unitAddAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#unitAddAndUpdateForm').validate().resetForm();
            $('#unitAddAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#unitAddAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditUnitModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Show Add Warranty Modal
        $('#addUnitButton').click(function() {
            $('#modalTitle').text('New Unit');
            $('#modalButton').text('Save');
            $('#unitAddAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditUnitModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/unit-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#unit').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.name + '</td>');
                        row.append('<td>' + item.short_name + '</td>');
                        row.append('<td>' + item.allow_decimal + '</td>');
                        row.append('<td>' +
                            '@can('edit unit')<button type="button" value="' +
                            item.id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can('delete unit')<button type="button" value="' +
                            item.id +
                            '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                            '</td>');
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        // Show Edit Modal
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Unit');
            $('#modalButton').text('Update');
            $('#unitAddAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'unit-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_name').val(response.message.name);
                        $('#edit_short_name').val(response.message.short_name);
                        $('#edit_allow_decimal').val(response.message.allow_decimal);
                        $('#addAndEditUnitModal').modal('show'); //allow_decimal
                    }
                }
            });
        });

        // Submit Add/Update Form
        $('#unitAddAndUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#unitAddAndUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'unit-update/' + id : 'unit-store';
            let type = id ? 'post' : 'post';

            $.ajax({
                url: url,
                type: type,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                        populateUnitDropdown();

                    } else {

                        let newUnitId = response.newUnitId ||
                        id; // Get new brand ID or use existing ID
                        populateUnitDropdown(newUnitId);
                        $('#addAndEditUnitModal').modal('hide');
                        // Clear validation error messages
                        showFetchData();
                        document.getElementsByClassName('successSound')[0]
                    .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });

        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Unit');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'unit-delete/' + id,
                type: 'delete',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.status == 200) {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        document.getElementsByClassName('successSound')[0]
                    .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, 'Deleted');
                    } else {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message || 'An error occurred', 'Error');
                        populateUnitDropdown();
                    }
                },
                error: function(xhr) {

                    // Check for SQLSTATE[23000] error code 1451 (foreign key constraint)
                    if (
                        xhr.responseJSON &&
                        (
                            (xhr.responseJSON.message && xhr.responseJSON.message.includes(
                                'SQLSTATE[23000]') && xhr.responseJSON.message.includes(
                                '1451')) ||
                            (xhr.responseJSON.exception && xhr.responseJSON.exception
                                .includes('Integrity constraint violation'))
                        )
                    ) {
                        toastr.error(
                            'This unit cannot be deleted because it is associated with one or more products.',
                            'Delete Not Allowed');
                    } else {
                        toastr.error('An error occurred while deleting the unit.',
                            'Error');
                    }
                }


            });
        });

        function populateUnitDropdown(selectedId = null) {
            $.ajax({
                url: '/get-unit',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    let unitSelect = $('#edit_unit_id');
                    unitSelect.empty(); // Clear existing options
                    unitSelect.append(
                    '<option selected disabled>Unit</option>'); // Add default option
                    $.each(data, function(key, value) {
                        unitSelect.append('<option value="' + value.id + '">' + value.name +
                            '</option>');
                    });

                    setTimeout(() => {
                        if (selectedId) {
                            unitSelect.val(selectedId).trigger('change');
                        }
                    }, 300);
                },
                error: function(xhr, status, error) {
                    console.error('Failed to fetch unit data:', error); // Log any errors
                }
            });
        }
    });
</script>
