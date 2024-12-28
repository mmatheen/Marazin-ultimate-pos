<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
            location_id: {
                required: true,

            },
            product_id: {
                required: true,

            },

            quantity: {
                required: true,

            },
            unit_cost: {
                required: true,

            },
            lot_no: {
                required: true,

            },
            expiry_date: {
                required: true,

            },

        },
        messages: {

            location_id: {
                required: "Location Name is required",
            },
            product_id: {
                required: "Product Name is required",
            },

            quantity: {
                required: "Quantity is required",
            },
            unit_cost: {
                required: "Unit Cost  is required",
            },
            lot_no: {
                required: "Lot No  is required",
            },
            expiry_date: {
                required: "Expiry Date  is required",
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
    $('#addAndUpdateForm').validate(addAndUpdateValidationOptions);

  // add form and update validation rules code end

  // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#addAndUpdateForm').validate().resetForm();
            $('#addAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
            $('#addAndEditOpeningStockModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Event listener to clear the file error message when a file is selected
            $('input[name="file"]').on('change', function() {
                if (this.files.length > 0) {
                    $('#file_error').html(''); // Clear the error message
                }
            });

        // Show Add Warranty Modal
        $('#addOpeningStockButton').click(function() {
            $('#modalTitle').text('New Opening Stock');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditOpeningStockModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/import-opening-stock-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#openingStock').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter  + '</td>');
                        row.append('<td>' + item.sku + '</td>');
                        row.append('<td>' + item.location.name + '</td>');
                        row.append('<td>' + item.product.product_name + '</td>');
                        row.append('<td>' + item.quantity + '</td>');
                        row.append('<td>' + item.unit_cost + '</td>');
                        row.append('<td>' + item.batch_id + '</td>');
                        row.append('<td>' + item.expiry_date + '</td>');
                         row.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>');
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
            $('#modalTitle').text('Edit Opening Stock');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'import-opening-stock-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_sku').val(response.message.sku);
                        $('#edit_location_id').val(response.message.location_id);
                        $('#edit_product_id').val(response.message.product_id);
                        $('#edit_quantity').val(response.message.quantity);
                        $('#edit_unit_cost').val(response.message.unit_cost);
                        $('#edit_batch_id').val(response.message.lot_no);
                        $('#edit_expiry_date').val(response.message.expiry_date);
                        $('#addAndEditOpeningStockModal').modal('show');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndUpdateForm').submit(function(e) {
            e.preventDefault();

             // Validate the form before submitting
            if (!$('#addAndUpdateForm').valid()) {
                   document.getElementsByClassName('warningSound')[0].play(); //for sound
                   toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error('Invalid inputs, Check & try again!!','Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'import-opening-stock-update/' + id : 'import-opening-stock-store';
            let type = id ? 'post' : 'post';

            $.ajax({
                url: url,
                type: type,
                headers: {'X-CSRF-TOKEN': csrfToken},
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                            document.getElementsByClassName('errorSound')[0].play(); //for sound
                            toastr.error(err_value,'Error');
                        });

                    } else {
                        $('#addAndEditOpeningStockModal').modal('hide');
                           // Clear validation error messages
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
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
            $('#deleteName').text('Delete Opening Stock');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'import-opening-stock-delete/' + id,
                type: 'delete',
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });
    });
</script>



{{-- impoprt lead file code start --}}
<script>
    $(document).on('submit', '#importOpeningStockForm', function(e) {
        e.preventDefault();
        let formData = new FormData($('#importOpeningStockForm')[0]);
        let fileInput = $('input[name="file"]')[0];

        // Check if a file is selected
        if (fileInput.files.length === 0) {
            $('#file_error').html('Please select the excel format file.');
            document.getElementsByClassName('errorSound')[0].play(); //for sound
            toastr.error('Please select the excel format file' ,'Error');
            return;
        } else {
            $('#file_error').html('');
        }

        $.ajax({
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percentComplete = e.loaded / e.total * 100;
                        $('.progress').show();
                        $('.progress-bar').css('width', percentComplete + '%');
                        $('.progress-bar').attr('aria-valuenow', percentComplete);
                        $('.progress-bar').text(Math.round(percentComplete) + '%'); // Display the percentage
                    }
                }, false);
                return xhr;
            },
            url: 'import-opening-stck-excel-store',
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function() {
                $('.progress-bar').css('width', '0%').text('0%');
                $('.progress').show();
            },
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value); // Assuming there's only one file input with id 'leadFile'
                        document.getElementsByClassName('errorSound')[0].play(); //for sound
                        toastr.error(err_value,'Error');

                    });
                    $('.progress').hide(); // Hide progress bar on validation error
                } else if (response.status == 200) {
                    $("#importOpeningStockForm")[0].reset();
                    document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.success(response.message, 'Uploaded');
                    $('.progress').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                document.getElementsByClassName('errorSound')[0].play(); //for sound
                toastr.error('An error occurred while processing the request.');
                $('.progress').hide(); // Hide progress bar on request error

            },
            complete: function() {
                $("#importOpeningStockForm")[0].reset();
                $('.progress').hide(); // Ensure progress bar is hidden after completion
            }
        });
    });
</script>
