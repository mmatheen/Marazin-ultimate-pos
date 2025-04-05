<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
            customerGroupName: {
                required: true,

            },
            priceCalculationType: {
                required: true,

            },

        },
        messages: {

            customerGroupName: {
                required: "Customer Group Name is required",
            },
            priceCalculationType: {
                required: "Price Calculation Type is required",
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
            $('#addAndEditCustomerGroupModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Show Add Warranty Modal
        $('#addCustomerGroupButton').click(function() {
            $('#modalTitle').text('New Customer Group');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditCustomerGroupModal').modal('show');
        });

        function showFetchData() {
    $.ajax({
        url: '/customer-group-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var table = $('#customerGroup').DataTable();
            table.clear().draw();
            var counter = 1;
            response.message.forEach(function(item) {
                let row = $('<tr>');
                row.append('<td>' + counter + '</td>');
                row.append('<td>' + item.customerGroupName + '</td>');
                row.append('<td>' + item.priceCalculationType + '</td>');
                // Check if selling_price_group is not null
                let sellingPriceGroupName = item.selling_price_group ? item.selling_price_group.name : '_ _';
                row.append('<td>' + sellingPriceGroupName + '</td>');
                row.append('<td>' + item.calculationPercentage + '</td>');
                row.append('<td>' + '@can("edit customer-group")<button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                    '@can("delete customer-group")<button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +'</td>');
                table.row.add(row).draw(false);
                counter++;
            });
        },
    });
}


       // Show Edit Modal
$(document).on('click', '.edit_btn', function() {
    var id = $(this).val();
    $('#modalTitle').text('Edit Customer Group');
    $('#modalButton').text('Update');
    $('#addAndUpdateForm')[0].reset();
    $('.text-danger').text('');
    $('#edit_id').val(id);

    $.ajax({
        url: 'customer-group-edit/' + id,
        type: 'get',
        success: function(response) {
            if (response.status == 404) {
                toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                toastr.error(response.message, 'Error');
            } else if (response.status == 200) {
                // Set common values
                $('#edit_customerGroupName').val(response.message.customerGroupName);
                $('#edit_priceCalculationType').val(response.message.priceCalculationType);

               // Trigger the change event to ensure the correct field is shown

                if (response.message.priceCalculationType === 'Percentage') {
                    $('#edit_calculationPercentage').val(response.message.calculationPercentage);
                } else if (response.message.priceCalculationType === 'Selling Price Group') {
                    $('#edit_selling_price_group_id').val(response.message.selling_price_group_id);
                }

                // Show the modal
                $('#addAndEditCustomerGroupModal').modal('show');
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
                   toastr.warning('Please fill in all the required fields.','Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'customer-group-update/' + id : 'customer-group-store';
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
                        });

                    } else {
                        $('#addAndEditCustomerGroupModal').modal('hide');
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


        // Delete Customer Group
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Customer Group');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'customer-group-delete/' + id,
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
