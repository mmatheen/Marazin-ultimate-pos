<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                name_title: {
                    required: true,
                },
                full_name: {
                    required: true,
                },
                user_name: {
                    required: true,
                },
                roles: {
                    required: true,
                },
                location_id: {
                    required: true,
                },
                email: {
                    required: true,
                    email: true // Added email format validation
                },
                password: {
                    required: function() {
                        // Check if the hidden edit_id value is empty.
                        // It returns true if it is empty (indicating an add operation),
                        // and false if a value is available (indicating an update operation).
                        return $('#edit_id').val() === '';
                        // When updating the record, the password field will not be validated as required.
                    },
                    minlength: 6 // Minimum password length validation

                },

            },
            messages: {

                name_title: {
                    required: "Name Title is required",
                },

                full_name: {
                    required: "Full Name is required",
                },

                user_name: {
                    required: "User Name is required",
                },
                roles: {
                    required: "Role Name is required",
                },
                location_id: {
                    required: "Location Name is required",
                },
                required: "Email is required",
                password: {
                    required: "Password is required",
                    minlength: "Password must be at least 5 characters long",
                },


            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                // error message show after selectbox
                if (element.is("select")) {
                    error.addClass('text-danger');
                    // Insert the error after the closest parent div for better placement with select
                    error.insertAfter(element.closest('div'));
                }
                // error message show after checkbox
                else if (element.is(":checkbox")) {
                    error.addClass('text-danger');
                    // For checkboxes, place the error after the checkbox's parent container
                    error.insertAfter(element.closest('div').find('label').last());
                }
                // error message show after inputbox
                else {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to both forms
        $('#addAndUserUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end



        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            if ($('#addAndUserUpdateForm').length > 0 && $('#addAndUserUpdateForm')[0]) {
                $('#addAndUserUpdateForm')[0].reset();
            }
            // Reset the validation messages and states
            $('#addAndUserUpdateForm').validate().resetForm();
            $('#addAndUserUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndUserUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Re-initialize Select2 when modal is shown to fix typing/search functionality
        $('#addAndEditModal').on('shown.bs.modal', function() {
            // Re-initialize Select2 dropdowns in the modal
            $('#addAndEditModal .selectBox').select2({
                dropdownParent: $('#addAndEditModal')
            });
        });

        // it will Clear the serverside validation errors on input change
        // Clear validation error for specific fields on input change based on 'name' attribute
        $('#addAndUserUpdateForm').on('input change', 'input', function() {
            var fieldName = $(this).attr('name');
            $('#' + fieldName + '_error').html(''); // Clear specific field error message
        });

        // Show Add Warranty Modal
        $('#addButton').click(function() {
            $('#modalTitle').text('New User');
            $('#modalButton').text('Save');
            $('#addAndUserUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/user-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#user').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.name_title + '</td>');
                        row.append('<td>' + item.full_name + '</td>');
                        row.append('<td>' + item.user_name + '</td>');
                        row.append('<td><span class="badge rounded-pill bg-dark me-1">' +
                            item.role + '</span></td>');

                        // Build location display with better formatting
                        let locationHtml = '';
                        if (item.locations && item.locations.length > 0) {
                            const maxVisible = 2; // Show only first 2 locations
                            const visibleLocations = item.locations.slice(0, maxVisible);
                            const hiddenLocations = item.locations.slice(maxVisible);

                            // Show visible locations as badges
                            visibleLocations.forEach(function(location) {
                                locationHtml += '<span class="badge rounded-pill bg-dark me-1 mb-1">' + location + '</span>';
                            });

                            // If there are more locations, add a "View More" button
                            if (hiddenLocations.length > 0) {
                                const allLocations = item.locations.join(', ');
                                locationHtml += '<button type="button" class="badge rounded-pill bg-info border-0 view-locations-btn" ' +
                                    'data-locations="' + allLocations.replace(/"/g, '&quot;') + '" ' +
                                    'data-user="' + item.full_name + '" ' +
                                    'style="cursor:pointer;">+' + hiddenLocations.length + ' more</button>';
                            }
                        } else {
                            locationHtml = '<span class="badge rounded-pill bg-secondary">No Location</span>';
                        }

                        row.append('<td>' + locationHtml + '</td>');
                        row.append('<td>' + item.email + '</td>');
                        row.append('<td>' +
                            '@can('edit user')<button type="button" value="' +
                            item.id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can('delete user')<button type="button" value="' +
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
            $('#modalTitle').text('Edit User');
            $('#modalButton').text('Update');
            $('#addAndUserUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'user-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        // Populate locations first, then set values
                        populateLocationDropdown(function() {
                            $('#edit_name_title').val(response.message.name_title);
                            $('#edit_full_name').val(response.message.full_name);
                            $('#edit_user_name').val(response.message.user_name);
                            $('#edit_email').val(response.message.email);
                            $('#edit_role_name').val(response.message.role);

                            // For multiple locations select - set after dropdown is populated
                            $('#edit_location_id').val(response.message.location_ids).trigger(
                                'change');

                            $('#addAndEditModal').modal('show');
                        });
                    }
                }
            });
        });
        // Submit Add/Update Form
        $('#addAndUserUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#addAndUserUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'user-update/' + id : 'user-store';
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
                        $('#addAndEditModal').modal('hide');
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


        // Delete user
        $(document).off('click', '.delete_btn').on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete User');
        });

        $(document).off('click', '.confirm_user_delete_btn').on('click', '.confirm_user_delete_btn',
    function() {
            var id = $('#deleting_id').val();

            // Prevent multiple clicks
            if ($(this).data('processing')) {
                return false;
            }

            $(this).data('processing', true);

            $.ajax({
                url: 'user-delete/' + id,
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
                    $('.confirm_user_delete_btn').data('processing', false);

                    // Clear any existing toastr messages first
                    toastr.clear();

                    var errorMessage = 'An error occurred while deleting the user.';

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
                    $('.confirm_user_delete_btn').data('processing', false);
                }
            });
        });

        populateLocationDropdown();

        // Populate Location Dropdown - Show only locations accessible to logged-in user
        function populateLocationDropdown(callback) {
            $.ajax({
                url: '/location-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    const dropdown = $('#edit_location_id');
                    dropdown.empty().append('<option value="">Select Location</option>');

                    if (res.status && Array.isArray(res.data)) {
                        res.data.forEach(function(loc) {
                            let label = '';

                            // If it's a sub-location (vehicle), show parent info
                            if (loc.parent_id && loc.parent) {
                                label = `${loc.parent.name} → ${loc.name}`;
                                if (loc.vehicle_number) {
                                    label += ` (${loc.vehicle_number})`;
                                }
                                if (loc.vehicle_type) {
                                    label += ` - ${loc.vehicle_type}`;
                                }
                            } else {
                                // Main location
                                label = loc.name;
                            }

                            dropdown.append(`<option value="${loc.id}">${label}</option>`);
                        });

                        // Show message if no locations found
                        if (dropdown.find('option').length === 1) { // Only the "Select Location" option
                            dropdown.append('<option value="" disabled>No accessible locations</option>');
                        }

                        // Refresh Select2 to show updated options
                        dropdown.trigger('change.select2');

                        console.log(`Loaded ${dropdown.find('option').length - 1} accessible locations`);

                    } else {
                        dropdown.append('<option value="" disabled>No locations available</option>');
                    }

                    // Execute callback if provided
                    if (typeof callback === 'function') {
                        callback();
                    }
                },
                error: function(xhr) {
                    console.error("Failed to load locations:", xhr.responseJSON?.message);
                    $('#edit_location_id').html('<option value="" disabled>Failed to load locations</option>');
                    toastr.error('Could not load locations.');

                    // Execute callback even on error
                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }

        // Refresh location dropdown when modal is shown for add mode
        $('#addAndEditModal').on('shown.bs.modal', function() {
            // Only refresh if it's add mode (not edit mode)
            if (!$('#edit_id').val()) {
                populateLocationDropdown();
            }
        });

        // Handle "View More" locations button click
        $(document).on('click', '.view-locations-btn', function(e) {
            e.preventDefault();
            const locations = $(this).data('locations');
            const userName = $(this).data('user');

            // Create a simple bulleted list
            const locationsList = '• ' + locations.split(', ').join('\n• ');

            // Display locations in a clean format
            swal({
                title: userName + ' - Locations',
                text: locationsList,
                button: 'Close'
            });
        });
    });
</script>
