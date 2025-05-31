<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();
        populateBrandDropdown();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
            name: {
                required: true,
            },
        },
        messages: {

            name: {
                required: "Brand Name is required",
            },

        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('text-danger');
            error.insertAfter(element);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalidRed').removeClass('is-validGreen');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalidRed').addClass('is-validGreen');
        }

    };

    // Apply validation to both forms
    $('#brandAddAndUpdateForm').validate(addAndUpdateValidationOptions);

  // add form and update validation rules code end

  // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#brandAddAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#brandAddAndUpdateForm').validate().resetForm();
            $('#brandAddAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#brandAddAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
            $('#addEditBrandModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Show Add Warranty Modal
        $('#addBrandButton').click(function() {
            $('#modalTitle').text('New Brand');
            $('#modalButton').text('Save');
            $('#brandAddAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addEditBrandModal').modal('show');
        });


        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/brand-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#brand').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter  + '</td>');
                        row.append('<td>' + item.name + '</td>');
                        row.append('<td>' + item.description + '</td>');
                        row.append('<td>' + '@can("edit brand")<button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can("delete brand")<button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +'</td>');
                        // row.append(actionDropdown);
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

            // Show Edit Modal
            $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Brand');
            $('#modalButton').text('Update');
            $('#brandAddAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'brand-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_name').val(response.message.name);
                        $('#edit_description').val(response.message.description);
                        $('#addEditBrandModal').modal('show');
                    }
                }
            });
        });

            // Submit Add/Update Form
$('#brandAddAndUpdateForm').submit(function(e) {
    e.preventDefault();

    // Validate the form before submitting
    if (!$('#brandAddAndUpdateForm').valid()) {
        document.getElementsByClassName('warningSound')[0].play(); // For sound
        toastr.options = {
            "closeButton": true,
            "positionClass": "toast-top-right"
        };
        toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
        return; // Return if form is not valid
    }

    let formData = new FormData(this);
    let id = $('#edit_id').val(); // For edit
    let url = id ? 'brand-update/' + id : 'brand-store';
    let type = 'post';

    $.ajax({
        url: url,
        type: type,
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(response) {
            if (response.status == 400) {
                $.each(response.errors, function(key, err_value) {
                    $('#' + key + '_error').html(err_value);
                });
            } else {
                let newBrandId = response.newBrandId || id; // Get new brand ID or use existing ID

                $('#addEditBrandModal').modal('hide');
                showFetchData(); // Refresh data list

                // Repopulate dropdown and ensure the new brand is selected
                populateBrandDropdown(newBrandId);

                document.getElementsByClassName('successSound')[0].play(); // For sound
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

// Function to Populate the Dropdown and Select the Newly Added Brand
function populateBrandDropdown(selectedId = null) {
    $.ajax({
        url: '/get-brand',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let brandSelect = $('#edit_brand_id');
            brandSelect.empty(); // Clear existing options
            brandSelect.append('<option selected disabled> Select Brand</option>'); // Add default option
            $.each(data, function(key, value) {
                brandSelect.append('<option value="' + value.id + '">' + value.name + '</option>');
            });

            // Ensure the new brand is selected **after** options are added
            setTimeout(() => {
                if (selectedId) {
                    brandSelect.val(selectedId).trigger('change'); // Select new brand
                }
            }, 300); // Short delay to ensure options are loaded before selecting
        },
        error: function(xhr, status, error) {
            console.error('Failed to fetch brand data:', error); // Log any errors
        }
    });
}



        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Brand');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'brand-delete/' + id,
                type: 'delete',
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                        populateBrandDropdown();
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, 'Deleted');
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
                            'This brand cannot be deleted because it is associated with one or more products.',
                            'Delete Not Allowed');
                    } else {
                        toastr.error('An error occurred while deleting the brand.',
                            'Error');
                    }
                }
            });
        });


    });

</script>
