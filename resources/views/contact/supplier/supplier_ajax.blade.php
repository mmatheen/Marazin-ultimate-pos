<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();
        supplierGetAll();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {

            prefix: {
                required: true,

            },
            first_name: {
                required: true,

            },
            last_name: {
                required: true,

            },
            mobile_no: {
                required: true,

            },
            email: {
                required: true,

            },
            // contact_id: {
            //     required: true,

            // },
            // contact_type: {
            //     required: true,

            // },
            // date: {
            //     required: true,

            // },
            // assign_to: {
            //     required: true,

            // },
            opening_balance: {
                required: true,

            },

        },
        messages: {

            prefix: {
                required: "Prefix is required",
            },

            first_name: {
                required: "First Name is required",
            },
            last_name: {
                required: "Last Name  is required",
            },
            mobile_no: {
                required: "Mobile No  is required",
            },
            email: {
                required: "Email  is required",
            },
            // contact_id: {
            //     required: "Contact ID  is required",
            // },
            // contact_type: {
            //     required: "Contact Type  is required",
            // },
            // date: {
            //     required: "Date  is required",
            // },
            // assign_to: {
            //     required: "Assign To  is required",
            // },
            opening_balance: {
                required: "Opening Balance  is required",
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
            $('#addAndEditSupplierModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Show Add supplier model Modal
        $('#addSupplierButton').click(function() {
            $('#modalTitle').text('New Supplier');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditSupplierModal').modal('show');
        });

        function showFetchData() {
    $.ajax({
        url: '/supplier-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var table = $('#supplier').DataTable();
            table.clear().draw();
            response.message.forEach(function(item) {
                let row = $('<tr>');
                row.append('<td>' + item.id + '</td>'); // Supplier ID
                row.append('<td>' + item.prefix + '</td>');
                row.append('<td>' + item.first_name + '</td>');
                row.append('<td>' + item.last_name + '</td>');
                row.append('<td>' + item.full_name + '</td>');
                row.append('<td>' + item.mobile_no + '</td>');
                row.append('<td>' + item.email + '</td>');
                row.append('<td>' + item.address + '</td>');
                row.append('<td>' + (item.location_id || 'N/A') + '</td>');
                row.append('<td>Rs ' + item.opening_balance + '</td>');
                row.append('<td>Rs ' + item.current_balance + '</td>');
                row.append('<td>Rs ' + item.total_purchase_due + '</td>');
                row.append('<td>Rs ' + item.total_return_due + '</td>');
                row.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>');
                table.row.add(row).draw(false);
            });
        },
        error: function(error) {
            console.log("Error fetching data:", error);
        }
    });
}

            // Show Edit Modal
            $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Supplier');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'supplier-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_prefix').val(response.message.prefix);
                        $('#edit_first_name').val(response.message.first_name);
                        $('#edit_last_name').val(response.message.last_name);
                        $('#edit_mobile_no').val(response.message.mobile_no);
                        $('#edit_email').val(response.message.email);
                        // $('#edit_contact_id').val(response.message.contact_id);
                        // $('#edit_contact_type').val(response.message.contact_type);
                        // $('#edit_date').val(response.message.date);
                        // $('#edit_assign_to').val(response.message.assign_to);
                        $('#edit_opening_balance').val(response.message.opening_balance);
                        $('#addAndEditSupplierModal').modal('show');
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
            let url = id ? 'supplier-update/' + id : 'supplier-store';
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
                        $('#addAndEditSupplierModal').modal('hide');
                           // Clear validation error messages
                        showFetchData();
                        supplierGetAll();
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
            $('#deleteName').text('Delete Supplier');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'supplier-delete/' + id,
                type: 'delete',
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        supplierGetAll();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });


function supplierGetAll(){
$.ajax({
    url: '/supplier-get-all',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        if (data.status === 200) {
            const supplierSelect = $('#supplier-id');

            // Clear existing options before appending new ones
            supplierSelect.html('<option selected disabled>Supplier</option>');

            // Loop through the supplier data and create an option for each supplier
            data.message.forEach(function(supplier) {
                const option = $('<option></option>')
                    .val(supplier.id)
                    .text(`${supplier.first_name} ${supplier.last_name} (ID: ${supplier.id})`)
                    .data('details', supplier); // Store supplier details in data attribute

                supplierSelect.append(option);
            });
        } else {
            console.error('Failed to fetch supplier data:', data.message);
        }
    },
    error: function(xhr, status, error) {
        console.error('Error fetching supplier data:', error);
    }
});
}
    });
</script>
