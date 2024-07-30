<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
            name: {
                required: true,

            },
            description: {
                required: true,

            },
            duration: {
                required: true,

            },
            duration_type: {
                required: true,

            },

        },
        messages: {

            name: {
                required: "name is required",
            },
            description: {
                required: "description is required",
            },
            duration: {
                required: "duration is required",
            },
            duration_type: {
                required: "duration type  is required",
            },

        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('text-danger');
            error.insertAfter(element);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
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
            $('#addAndUpdateForm').find('.is-invalid').removeClass('is-invalid');
            $('#addAndUpdateForm').find('.is-valid').removeClass('is-valid');
        }

        // Clear form and validation errors when the modal is hidden
            $('#addAndEditModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Show Add Warranty Modal
        $('#addWarrantyButton').click(function() {
            $('#modalTitle').text('Add Warranty');
            $('#modalButton').text('Save changes');
            $('#deleteName').text('Delete Warranty');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#addAndEditModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/warranty-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#warranty').DataTable();
                    table.clear().draw();
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + item.id + '</td>');
                        row.append('<td>' + item.name + '</td>');
                        row.append('<td>' + item.description + '</td>');
                        row.append('<td>' + item.duration + '</td>');
                        row.append('<td>' + item.duration_type + '</td>');
                        // let actionDropdown = `
                        //     <td class="text-center">
                        //       <div class="dropdown dropdown-action">
                        //             <button type="button" data-bs-toggle="dropdown" aria-expanded="false" class="btn btn-outline-info"><i class="fas fa-ellipsis-v"></i></button>
                        //         <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        //             <li><a class="dropdown-item edit_btn"  data-id="${item.id}">
                        //             <i class="far fa-edit me-2"></i>Edit
                        //             </a></li>
                        //             <li><a class="dropdown-item delete_btn" data-id="${item.id}">
                        //             <i class="far fa-trash-alt me-2"></i>Delete
                        //             </a></li>
                        //         </ul>
                        //      </div>
                        //     </td>`;
                        // row.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-rounded"><i class="feather-edit"></i></button></td>');
                        // row.append('<td><button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-rounded"> <i class="feather-trash-2 me-1"></i></button></td>');
                         row.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-rounded"><i class="feather-edit"></i></button> <button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-rounded"><i class="feather-trash-2 me-1"></i></button></td>');
                        // row.append(actionDropdown);
                        table.row.add(row).draw(false);
                    });
                },
            });
        }

            // Show Edit Modal
            $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Warranty');
            $('#modalButton').text('Edit');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'warranty-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_name').val(response.message.name);
                        $('#edit_description').val(response.message.description);
                        $('#edit_duration').val(response.message.duration);
                        $('#edit_duration_type').val(response.message.duration_type);
                        $('#addAndEditModal').modal('show');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndUpdateForm').submit(function(e) {
            e.preventDefault();

             // Validate the form before submitting
            if (!$('#addAndUpdateForm').valid()) {
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'warranty-update/' + id : 'warranty-store';
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
                        $('#addAndEditModal').modal('hide');
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
            $('#deleteName').text('Delete Warranty');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'warranty-delete/' + id,
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
