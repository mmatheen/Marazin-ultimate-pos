<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        populateRoleDropdown();

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                name: {
                    required: true,
                },

            },
            messages: {

                name: {
                    required: "Role Name is required",
                },

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
        $('#addAndRoleUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            if ($('#addAndRoleUpdateForm').length) {
                $('#addAndRoleUpdateForm')[0].reset();
            }
            // Reset the validation messages and states
            $('#addAndRoleUpdateForm').validate().resetForm();
            $('#addAndRoleUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndRoleUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditRoleModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // it will Clear the serverside validation errors on input change
        // Clear validation error for specific fields on input change based on 'name' attribute
        $('#addAndRoleUpdateForm').on('input change', 'input', function() {
            var fieldName = $(this).attr('name');
            $('#' + fieldName + '_error').html(''); // Clear specific field error message
        });

        // Show Add Warranty Modal
        $('#addRoleButton').click(function() {
            $('#modalTitle').text('New Role');
            $('#modalButton').text('Save');
            $('#addAndRoleUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditRoleModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/role-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#role').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.name + '</td>');
                        row.append('<td>' +
                            '@can("edit role")<button type="button" value="' +
                            item.id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can("delete role")<button type="button" value="' +
                            item.id +
                            '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                            '</td>');
                        // row.append(actionDropdown);
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Role');
            $('#modalButton').text('Update');
            $('#addAndRoleUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'role-edit/' + id,
                type: 'get',
                success: function(response) {
                if (response.status === 200) {
                    // toastr.success(response.message);
                       $('#edit_name').val(response.message.name);
                        $('#edit_key').val(response.message.key); 
                        $('#addAndEditRoleModal').modal('show');
                } else if (response.status === 403) {
                    toastr.error(response.message);
                } else {
                    toastr.error('Unexpected error occurred.');
                }
            },
            error: function(xhr) {
                // If backend returns 403 as HTTP status
                if (xhr.status === 403 && xhr.responseJSON && xhr.responseJSON.message) {
                    toastr.error(xhr.responseJSON.message);
                } else {
                    toastr.error('Server error!');
                }
            }
            });
        });


        // Submit Add/Update Form
        $('#addAndRoleUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#addAndRoleUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Error');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'role-update/' + id : 'role-store';
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
                            toastr.error(err_value, 'Validation Error');
                            document.getElementsByClassName('errorSound')[0]
                        .play(); //for sound
                        });

                    } else {
                        $('#addAndEditRoleModal').modal('hide');
                        // Clear validation error messages
                        showFetchData();
                        populateRoleDropdown();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                        
                        // If it's a new role creation and response indicates redirect to permissions
                        if (!id && response.redirect_to_permissions) {
                            // Show success message first, then redirect after a short delay
                            setTimeout(function() {
                                toastr.info('Redirecting to assign permissions to the new role...', 'Next Step');
                                setTimeout(function() {
                                    window.location.href = '/group-role-and-permission';
                                }, 1500);
                            }, 2000);
                        }
                    }
                }
            });
        });

        function populateRoleDropdown() {
            $.ajax({
                url: "{{ route('role.dropdown') }}", // Route URL
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.status === 200) {
                        let dropdown = $(".roleDropdown");
                        dropdown.empty().append('<option value="">Select Role</option>');

                        $.each(response.roles, function(index, role) {
                            dropdown.append('<option value="' + role.name + '">' + role
                                .name + '</option>');
                        });
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.log("Error:", xhr);
                }
            });
        }



        // Delete Role
        $(document).off('click', '.delete_btn').on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Role');
        });

        $(document).off('click', '.confirm_delete_btn').on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            
            // Prevent multiple clicks
            if ($(this).data('processing')) {
                return false;
            }
            
            $(this).data('processing', true);
            
            $.ajax({
                url: 'role-delete/' + id,
                type: 'delete',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        populateRoleDropdown();
                        document.getElementsByClassName('successSound')[0]
                    .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, 'Deleted');
                    }
                },
                error: function(xhr, status, error) {
                    $('#deleteModal').modal('hide');
                    
                    // Reset processing flag
                    $('.confirm_delete_btn').data('processing', false);
                    
                    // Clear any existing toastr messages first
                    toastr.clear();
                    
                    var errorMessage = 'An error occurred while deleting the role.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    toastr.options = {
                        "closeButton": true,
                        "positionClass": "toast-top-right",
                        "timeOut": 5000
                    };
                    
                    if (xhr.status === 403) {
                        toastr.warning(errorMessage, 'Access Denied');
                    } else if (xhr.status === 404) {
                        toastr.error(errorMessage, 'Not Found');
                    } else {
                        toastr.error(errorMessage, 'Error');
                    }
                },
                complete: function() {
                    // Reset processing flag when request completes
                    $('.confirm_delete_btn').data('processing', false);
                }
            });
        });
    });
</script>
