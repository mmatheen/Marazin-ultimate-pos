<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        const passwordChangeRequiresCurrentPassword = @json($passwordChangeRequiresCurrentPassword ?? true);
        showFetchData();

        $.validator.addMethod('profileImageType', function(value, element) {
            if (!element.files || element.files.length === 0) {
                return true;
            }

            var file = element.files[0];
            var fileName = (file.name || '').toLowerCase();
            var extension = fileName.split('.').pop();
            var allowedExtensions = ['jpg', 'jpeg', 'png'];
            var allowedMimeTypes = ['image/jpeg', 'image/png'];

            return allowedExtensions.indexOf(extension) !== -1 || allowedMimeTypes.indexOf(file.type) !== -1;
        }, 'Only JPG and PNG images are allowed.');

        $.validator.addMethod('fileSizeMax', function(value, element, maxBytes) {
            if (!element.files || element.files.length === 0) {
                return true;
            }

            return element.files[0].size <= maxBytes;
        }, 'File size must be within the allowed limit.');

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
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
                        return $('#edit_id').val() === '' && !$('#userCreatePasswordFields').hasClass('d-none');
                    },
                    minlength: 6
                },
                profile_image: {
                    profileImageType: true,
                    fileSizeMax: 5 * 1024 * 1024
                },

            },
            messages: {

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
                profile_image: {
                    profileImageType: 'Only JPG and PNG images are allowed.',
                    fileSizeMax: 'Profile photo must be 5MB or less.'
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

        $('#edit_profile_image').on('change', function() {
            $(this).valid();
        });

        // add form and update validation rules code end



        // Function to reset form and validation errors
        function resetFormAndValidation() {
            var $form = $('#addAndUserUpdateForm');
            if ($form.length > 0 && $form[0]) {
                $form[0].reset();
            }
            var validator = $form.data('validator');
            if (validator) {
                validator.resetForm();
            }
            $form.find('.is-invalidRed').removeClass('is-invalidRed');
            $form.find('.is-validGreen').removeClass('is-validGreen');
        }

        function populateRoleDropdown() {
            $.ajax({
                url: '/user-select-box-dropdown',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        let dropdown = $('.roleDropdown');
                        dropdown.empty().append('<option value="">Select Role</option>');

                        $.each(response.roles, function(index, role) {
                            const roleKey = role.key || '';
                            dropdown.append(
                                '<option value="' + role.name + '" data-key="' + roleKey + '">' + role.name + '</option>'
                            );
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load roles:', xhr.responseJSON?.message || xhr.statusText);
                }
            });
        }

        populateRoleDropdown();

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
            setUserFormPasswordMode('create');
            cachedAccessibleLocations = [];
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

        function setUserFormPasswordMode(mode) {
            const isCreate = mode === 'create';
            if (isCreate) {
                $('#userCreatePasswordFields').removeClass('d-none');
            } else {
                $('#userCreatePasswordFields').addClass('d-none');
                $('#edit_password').val('');
                $('#edit_confirm_password').val('');
            }
        }

        const changePasswordValidationRules = {
            password: {
                required: true,
                minlength: 6
            },
            password_confirmation: {
                required: true,
                equalTo: '#change_password_new'
            }
        };

        const changePasswordValidationMessages = {
            password: {
                required: 'Password is required',
                minlength: 'Password must be at least 5 characters long'
            },
            password_confirmation: {
                required: 'Please confirm the password',
                equalTo: 'Passwords do not match'
            }
        };

        if (passwordChangeRequiresCurrentPassword) {
            changePasswordValidationRules.current_password = {
                required: true,
                minlength: 1
            };
            changePasswordValidationMessages.current_password = {
                required: 'Your current password is required'
            };
        }

        $('#changePasswordForm').validate({
            rules: changePasswordValidationRules,
            messages: changePasswordValidationMessages,
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                const field = element.attr('name');
                if (field === 'current_password') {
                    $('#change_password_current_error').html(error.text());
                } else if (field === 'password') {
                    $('#change_password_error').html(error.text());
                } else if (field === 'password_confirmation') {
                    $('#change_password_confirm_error').html(error.text());
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        });

        $('#changePasswordModal').on('hidden.bs.modal', function() {
            const $form = $('#changePasswordForm');
            $form[0].reset();
            const validator = $form.data('validator');
            if (validator) {
                validator.resetForm();
            }
            $('#change_password_current_error, #change_password_error, #change_password_confirm_error').html('');
            $form.find('.is-invalidRed, .is-validGreen').removeClass('is-invalidRed is-validGreen');
        });

        $(document).on('click', '#changePasswordModal .toggle-password5', function() {
            $(this).toggleClass('feather-eye feather-eye-off');
            const input = $(this).closest('.form-group').find('#change_password_current');
            input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        });

        $(document).on('click', '#changePasswordModal .toggle-password3', function() {
            $(this).toggleClass('feather-eye feather-eye-off');
            const input = $(this).closest('.form-group').find('#change_password_new');
            input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        });

        $(document).on('click', '#changePasswordModal .toggle-password4', function() {
            $(this).toggleClass('feather-eye feather-eye-off');
            const input = $(this).closest('.form-group').find('#change_password_confirm');
            input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
        });

        // Show Add User Modal
        $('#addButton').click(function() {
            $('#modalTitle').text('New User');
            $('#modalButton').text('Save');
            $('#addAndUserUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val('');
            setUserFormPasswordMode('create');
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

                        const profileImageUrl = item.profile_image_url || '/assets/img/profiles/default-avatar.svg';
                        const fullName = item.full_name || 'User';
                        const safeFullName = $('<div>').text(fullName).html();
                        row.append('<td>' + counter + '</td>');
                        row.append('<td><a href="#" class="profile-image-preview-link" data-image-url="' + profileImageUrl +
                            '" data-full-name="' + safeFullName +
                            '" title="View profile image"><img src="' + profileImageUrl +
                            '" alt="User Image" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.src=\'/assets/img/profiles/default-avatar.svg\'"></a></td>');
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
                            '@can('change user password')<button type="button" value="' +
                            item.id +
                            '" data-user-name="' + safeFullName +
                            '" class="change_password_btn btn btn-outline-warning btn-sm me-2"><i class="fas fa-key me-1"></i> Password</button>@endcan' +
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

        $('#user').on('click', '.change_password_btn', function() {
            const id = $(this).val();
            const userName = $(this).data('user-name') || 'User';
            $('#change_password_user_id').val(id);
            $('#changePasswordUserName').text(userName);
            $('#change_password_current_error, #change_password_error, #change_password_confirm_error').html('');
            $('#changePasswordForm')[0].reset();
            if (passwordChangeRequiresCurrentPassword) {
                $('#changePasswordCurrentWrap').removeClass('d-none');
            }
            $('#changePasswordModal').modal('show');
        });

        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            if (!$(this).valid()) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error('Invalid inputs, check and try again.', 'Warning');
                return;
            }

            const id = $('#change_password_user_id').val();
            const formData = new FormData(this);

            $.ajax({
                url: 'user-change-password/' + id,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 400 && response.errors) {
                        $.each(response.errors, function(key, messages) {
                            const msg = Array.isArray(messages) ? messages[0] : messages;
                            if (key === 'current_password') {
                                $('#change_password_current_error').html(msg);
                            } else if (key === 'password') {
                                $('#change_password_error').html(msg);
                            } else if (key === 'password_confirmation') {
                                $('#change_password_confirm_error').html(msg);
                            }
                            toastr.error(msg, 'Validation Error');
                        });
                        document.getElementsByClassName('errorSound')[0].play();
                        return;
                    }

                    if (response.status === 200) {
                        $('#changePasswordModal').modal('hide');
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Success');

                        if (response.force_logout && response.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1200);
                        }
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to update password.';
                    toastr.error(message, xhr.status === 403 ? 'Access Denied' : 'Error');
                    document.getElementsByClassName('errorSound')[0].play();
                }
            });
        });

        // Show Edit Modal (scoped to user table only — avoids conflict with role page)
        $('#user').on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit User');
            $('#modalButton').text('Update');
            resetFormAndValidation();
            $('.text-danger').text('');
            $('#edit_id').val(id);
            setUserFormPasswordMode('edit');

            $.ajax({
                url: 'user-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 403) {
                        toastr.error(response.message || 'You do not have permission to edit this user.', 'Access Denied');
                    } else if (response.status == 200) {
                        $('#edit_role_name').val(response.message.role);
                        populateLocationDropdown(function() {
                            $('#edit_full_name').val(response.message.full_name);
                            $('#edit_user_name').val(response.message.user_name);
                            $('#edit_email').val(response.message.email);
                            $('#addAndEditModal').modal('show');
                        }, response.message.location_ids);
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.message || 'Failed to load user details.';
                    toastr.error(message, xhr.status === 403 ? 'Access Denied' : 'Error');
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
            if ($('#edit_id').val() !== '') {
                formData.delete('password');
                formData.delete('password_confirmation');
            }
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


        // Delete user (scoped to user table only)
        $('#user').off('click', '.delete_btn').on('click', '.delete_btn', function() {
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

        const SALES_REP_ROLE_KEY = 'sales_rep';
        let cachedAccessibleLocations = [];

        function isSalesRepRoleSelected() {
            const roleKey = $('#edit_role_name option:selected').data('key');
            return roleKey === SALES_REP_ROLE_KEY;
        }

        function buildLocationLabel(loc) {
            if (loc.parent_id && loc.parent) {
                let label = `${loc.parent.name} → ${loc.name}`;
                if (loc.vehicle_number) {
                    label += ` (${loc.vehicle_number})`;
                }
                if (loc.vehicle_type) {
                    label += ` - ${loc.vehicle_type}`;
                }
                return label;
            }

            return loc.name;
        }

        function renderLocationDropdown(selectedIds) {
            const dropdown = $('#edit_location_id');
            const parentsOnly = isSalesRepRoleSelected();
            let previousSelected = selectedIds || dropdown.val() || [];

            if (parentsOnly && previousSelected.length && cachedAccessibleLocations.length) {
                const mappedParentIds = [];
                (Array.isArray(previousSelected) ? previousSelected : [previousSelected]).forEach(function(id) {
                    const loc = cachedAccessibleLocations.find(function(item) {
                        return String(item.id) === String(id);
                    });
                    if (loc && loc.parent_id) {
                        mappedParentIds.push(String(loc.parent_id));
                    } else if (loc) {
                        mappedParentIds.push(String(loc.id));
                    }
                });
                previousSelected = [...new Set(mappedParentIds)];
            }

            dropdown.empty().append('<option value="">Select Location</option>');

            if (!cachedAccessibleLocations.length) {
                dropdown.append('<option value="" disabled>No locations available</option>');
                dropdown.trigger('change.select2');
                return;
            }

            cachedAccessibleLocations.forEach(function(loc) {
                dropdown.append(`<option value="${loc.id}">${buildLocationLabel(loc)}</option>`);
            });

            if (dropdown.find('option').length === 1) {
                dropdown.append('<option value="" disabled>No accessible locations</option>');
            }

            const validSelected = (Array.isArray(previousSelected) ? previousSelected : [previousSelected])
                .filter(function(id) {
                    return id && dropdown.find(`option[value="${id}"]`).length;
                });

            dropdown.val(validSelected.length ? validSelected : null).trigger('change.select2');
        }

        function populateLocationDropdown(callback, selectedIds) {
            if (cachedAccessibleLocations.length) {
                renderLocationDropdown(selectedIds);
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            const locationUrl = isSalesRepRoleSelected()
                ? '/location-get-all?parents_only=1'
                : '/location-get-all';

            $.ajax({
                url: locationUrl,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    cachedAccessibleLocations = (res.status && Array.isArray(res.data)) ? res.data : [];
                    renderLocationDropdown(selectedIds);

                    if (typeof callback === 'function') {
                        callback();
                    }
                },
                error: function(xhr) {
                    console.error("Failed to load locations:", xhr.responseJSON?.message);
                    cachedAccessibleLocations = [];
                    $('#edit_location_id').html('<option value="" disabled>Failed to load locations</option>');
                    toastr.error('Could not load locations.');

                    if (typeof callback === 'function') {
                        callback();
                    }
                }
            });
        }

        $('#edit_role_name').on('change', function() {
            cachedAccessibleLocations = [];
            populateLocationDropdown();
        });

        populateLocationDropdown();

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

            // Set modal title
            $('#locationsModalTitle').text(userName + ' - Locations');

            // Split locations into array
            const locationArray = locations.split(', ');

            // Create grid layout (3 per row)
            let locationsList = '<div class="container-fluid"><div class="row g-2">';
            locationArray.forEach(function(loc) {
                locationsList += '<div class="col-md-4 col-sm-6 col-12">' +
                    '<div class="p-2 bg-light rounded text-center" style="font-size: 0.875rem; min-height: 45px; display: flex; align-items: center; justify-content: center; word-wrap: break-word; overflow-wrap: break-word;">' +
                    loc +
                    '</div></div>';
            });
            locationsList += '</div></div>';

            // Populate modal content
            $('#locationsModalContent').html(locationsList);

            // Show the modal
            $('#locationsModal').modal('show');
        });

        // Handle profile image preview click
        $(document).on('click', '.profile-image-preview-link', function(e) {
            e.preventDefault();

            const imageUrl = $(this).data('image-url') || '/assets/img/profiles/default-avatar.svg';
            const fullName = $(this).data('full-name') || 'User';

            $('#profileImageModalName').text(fullName);
            $('#profileImageModalPreview')
                .attr('src', imageUrl)
                .attr('alt', fullName + ' Profile Image')
                .off('error')
                .on('error', function() {
                    $(this).attr('src', '/assets/img/profiles/default-avatar.svg');
                });

            $('#profileImageModal').modal('show');
        });
    });
</script>
