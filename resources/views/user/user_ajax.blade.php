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
                        row.append('<td><span class="badge rounded-pill bg-dark me-1">' +
                            item.locations.join(', ') + '</span></td>');
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
                        $('#edit_name_title').val(response.message.name_title);
                        $('#edit_full_name').val(response.message.full_name);
                        $('#edit_user_name').val(response.message.user_name);
                        $('#edit_email').val(response.message.email);
                        $('#edit_role_name').val(response.message.role);

                        // For multiple locations select
                        $('#edit_location_id').val(response.message.location_ids).trigger(
                            'change');

                        $('#addAndEditModal').modal('show');
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

        // Populate Location Dropdown Based on User Role & Vehicle Type
        function populateLocationDropdown(selectedRole = null) {
            $.ajax({
                url: '/location-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    const dropdown = $('#edit_location_id');
                    dropdown.empty().append('<option value="">Select Location</option>');

                    if (res.status && Array.isArray(res.data)) {
                        // Get the selected role or current role value
                        const currentRole = selectedRole || $('#edit_role_name').val();
                        
                        // Check if this is a sales rep role using helper function
                        const roleData = window.rolesData ? window.rolesData[currentRole] : null;
                        const isSalesRepRoleSelected = isSalesRepRole(currentRole, roleData);

                        console.log('Populating locations for role:', currentRole, 'isSalesRep:', isSalesRepRoleSelected);

                        res.data.forEach(function(loc) {
                            let shouldInclude = false;
                            let label = '';
                            
                            if (isSalesRepRoleSelected) {
                                // For Sales Rep: Only show sub-locations (vehicles) - locations with parent_id
                                if (loc.parent_id && loc.vehicle_type) {
                                    shouldInclude = true;
                                    // Show vehicle info: Parent → Vehicle (Type: vehicle_type)
                                    label = `${loc.parent?.name || 'Location'} → ${loc.name}`;
                                    if (loc.vehicle_number) {
                                        label += ` (${loc.vehicle_number})`;
                                    }
                                    if (loc.vehicle_type) {
                                        label += ` - ${loc.vehicle_type}`;
                                    }
                                }
                            } else {
                                // For Other Roles: Show main locations (parent locations without parent_id)
                                if (!loc.parent_id) {
                                    shouldInclude = true;
                                    label = loc.name;
                                    
                                    // Count child locations for context
                                    const childCount = res.data.filter(child => child.parent_id === loc.id).length;
                                    if (childCount > 0) {
                                        label += ` (${childCount} vehicles)`;
                                    }
                                }
                            }

                            if (shouldInclude) {
                                dropdown.append(`<option value="${loc.id}" data-location-type="${loc.parent_id ? 'sub' : 'main'}" data-vehicle-type="${loc.vehicle_type || ''}">${label}</option>`);
                            }
                        });

                        // Show appropriate message if no locations found
                        if (dropdown.find('option').length === 1) { // Only the "Select Location" option
                            const messageText = isSalesRepRoleSelected ? 
                                'No vehicles/sub-locations available for Sales Rep' : 
                                'No main locations available';
                            dropdown.append(`<option value="" disabled>${messageText}</option>`);
                        }

                        // Refresh Select2 to show updated options
                        dropdown.trigger('change.select2');
                        
                        console.log(`Loaded ${dropdown.find('option').length - 1} locations for role type: ${isSalesRepRoleSelected ? 'Sales Rep' : 'Regular User'}`);
                        
                    } else {
                        dropdown.append('<option value="" disabled>No locations available</option>');
                    }
                },
                error: function(xhr) {
                    console.error("Failed to load locations:", xhr.responseJSON?.message);
                    $('#edit_location_id').html('<option value="" disabled>Failed to load locations</option>');
                    toastr.error('Could not load locations.');
                }
            });
        }

        // Store role data globally for better role detection
        window.rolesData = {};

        // Helper function to accurately detect if a role is Sales Rep
        function isSalesRepRole(roleName, roleData = null) {
            if (!roleName) return false;
            
            // Check by role key first (most accurate)
            if (roleData && roleData.key) {
                if (roleData.key.toLowerCase().includes('sales_rep') || 
                    roleData.key.toLowerCase().includes('sale_rep')) {
                    return true;
                }
            }
            
            // Fallback to role name pattern matching
            const roleNameLower = roleName.toLowerCase();
            return roleNameLower.includes('sales rep') || 
                   roleNameLower.includes('salesrep') || 
                   roleNameLower === 'sales rep' ||
                   (roleNameLower.includes('sales') && roleNameLower.includes('rep'));
        }

        // Add event listener to role dropdown to update locations when role changes
        $(document).on('change', '#edit_role_name', function() {
            const selectedRole = $(this).val();
            console.log('Role changed to:', selectedRole);
            
            // Clear current location selection
            $('#edit_location_id').val(null).trigger('change');
            
            // Repopulate location dropdown based on new role
            populateLocationDropdown(selectedRole);
            
            // Show informative message and update info text
            if (selectedRole) {
                const roleData = window.rolesData[selectedRole];
                const isSalesRep = isSalesRepRole(selectedRole, roleData);
                
                // Update info message below dropdown
                const infoDiv = $('#location-role-info');
                if (isSalesRep) {
                    infoDiv.html('<i class="fas fa-info-circle text-warning"></i> <strong>Sales Rep Role:</strong> You can only select vehicles/sub-locations, not main locations.').show();
                    toastr.info('Location list updated to show vehicles/sub-locations only for Sales Rep role.', 'Role Changed');
                } else {
                    infoDiv.html('<i class="fas fa-info-circle text-info"></i> <strong>Regular Role:</strong> You can only select main locations, not individual vehicles.').show();
                    toastr.info('Location list updated to show main locations for this role.', 'Role Changed');
                }
            } else {
                $('#location-role-info').hide();
            }
        });

        // Also trigger on modal show to ensure correct locations are loaded
        $('#addAndEditModal').on('shown.bs.modal', function() {
            const currentRole = $('#edit_role_name').val();
            if (currentRole) {
                populateLocationDropdown(currentRole);
            }
        });

    });
</script>
