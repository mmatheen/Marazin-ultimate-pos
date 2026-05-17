<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
    var rolePermissionsByRoleId = {};
        showFetchData();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
            role_id: {
                required: true,
            },
        },
        messages: {
            role_id: {
                required: "Role Name is required",
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
    $('#addAndRoleAndPermissionUpdateForm').validate(addAndUpdateValidationOptions);

  // add form and update validation rules code end

  // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addAndRoleAndPermissionUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#addAndRoleAndPermissionUpdateForm').validate().resetForm();
            $('#addAndRoleAndPermissionUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndRoleAndPermissionUpdateForm').find('.is-validGreen').removeClass('is-validGreen');

             // Clear all checkboxes
            $('#addAndRoleAndPermissionUpdateForm').find('input[type="checkbox"]').prop('checked', false);
        }

       // Fetch and Display Data
       function showFetchData() {
    $.ajax({
        url: '/role-and-permission-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var table = $('#roleAndPermission').DataTable();
            table.clear().draw();
            rolePermissionsByRoleId = {};
            var counter = 1;
            response.values.forEach(function(item) {
                rolePermissionsByRoleId[item.role_id] = item.permissions;
                let row = $('<tr>');
                row.append('<td>' + counter + '</td>');
                row.append('<td>' + item.role_name + '</td>');
                
                // Display permission count and View All button
                let permissionCount = item.permissions.length;
                let permissionsHtml = '<div class="d-flex align-items-center justify-content-between">';
                permissionsHtml += '<span class="permission-count-badge">' + permissionCount + ' Permission' + (permissionCount !== 1 ? 's' : '') + '</span>';
                permissionsHtml += '<button type="button" class="btn btn-sm btn-outline-primary view-permissions-btn" data-role-id="' + item.role_id + '" data-role-name="' + item.role_name + '">';
                permissionsHtml += '<i class="feather-eye"></i> View All</button>';
                permissionsHtml += '</div>';
                
                row.append('<td>' + permissionsHtml + '</td>');
                row.append('<td>' + '@can("edit role-permission")<button type="button" value="' + item.role_id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                    '@can("delete role-permission")<button type="button" value="' + item.role_id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +'</td>');
                table.row.add(row).draw(false);
                counter++;
            });
        },
    });
}

    function getGroupSortOrder(groupName) {
        let match = String(groupName).match(/^(\d+)\./);
        return match ? parseInt(match[1], 10) : 9999;
    }

    function sortGroupNames(groupNames) {
        return groupNames.slice().sort(function(a, b) {
            let orderDiff = getGroupSortOrder(a) - getGroupSortOrder(b);
            if (orderDiff !== 0) {
                return orderDiff;
            }
            return a.localeCompare(b);
        });
    }

    function filterGroupedPermissions(groupedPermissions, searchTerm) {
        if (!searchTerm) {
            return groupedPermissions;
        }

        let filtered = {};
        Object.keys(groupedPermissions).forEach(function(groupName) {
            let groupMatches = groupName.toLowerCase().includes(searchTerm);
            let matchingPermissions = groupedPermissions[groupName].filter(function(permissionName) {
                return permissionName.toLowerCase().includes(searchTerm);
            });

            if (groupMatches) {
                filtered[groupName] = groupedPermissions[groupName].slice();
            } else if (matchingPermissions.length > 0) {
                filtered[groupName] = matchingPermissions;
            }
        });

        return filtered;
    }

    function renderPermissionsList(groupedPermissions) {
        let sortedGroups = sortGroupNames(Object.keys(groupedPermissions));

        if (sortedGroups.length === 0) {
            return '<p class="permissions-no-results text-muted">No permissions found matching your search.</p>';
        }

        let permissionsHtml = '<div class="permissions-view-simple">';

        sortedGroups.forEach(function(groupName) {
            let groupPermissions = groupedPermissions[groupName].slice().sort();

            permissionsHtml += '<section class="permission-group-block">';
            permissionsHtml += '<h6 class="permission-group-title">' + groupName + ' <span>(' + groupPermissions.length + ')</span></h6>';
            permissionsHtml += '<div class="permission-card-grid">';

            groupPermissions.forEach(function(permissionName) {
                permissionsHtml += '<div class="permission-mini-card"><span>' + permissionName + '</span></div>';
            });

            permissionsHtml += '</div></section>';
        });

        permissionsHtml += '</div>';
        return permissionsHtml;
    }

    function updatePermissionsListView() {
        let groupedPermissions = $('#viewPermissionsModal').data('groupedPermissions') || {};
        let searchTerm = $('#permissionsSearchInput').val().trim().toLowerCase();
        let filtered = filterGroupedPermissions(groupedPermissions, searchTerm);
        $('#permissionsListContainer').html(renderPermissionsList(filtered));
    }

    // Handle View All Permissions button click
    $(document).on('click', '.view-permissions-btn', function() {
        let roleId = $(this).data('role-id');
        let roleName = $(this).data('role-name');
        let permissions = rolePermissionsByRoleId[roleId] || [];

        $('#permissionsModalLabel').text(roleName);

        let groupedPermissions = {};
        permissions.forEach(function(permission) {
            let groupName = permission.group_name || 'Other';
            if (!groupedPermissions[groupName]) {
                groupedPermissions[groupName] = [];
            }
            groupedPermissions[groupName].push(permission.name);
        });

        $('#viewPermissionsModal').data('groupedPermissions', groupedPermissions);
        $('#permissionsSearchInput').val('');
        $('#permissionsListContainer').html(renderPermissionsList(groupedPermissions));
        $('#viewPermissionsModal').modal('show');

        setTimeout(function() {
            $('#permissionsSearchInput').trigger('focus');
        }, 300);
    });

    $(document).on('input', '#permissionsSearchInput', function() {
        updatePermissionsListView();
    });

    $('#viewPermissionsModal').on('hidden.bs.modal', function() {
        $('#permissionsSearchInput').val('');
        $('#permissionsListContainer').empty();
        $(this).removeData('groupedPermissions');
    });


        $(document).on('click', '.edit_btn', function() {
            var role_id = $(this).val(); // Get the ID from the button value
            
            // Make AJAX request to check permissions first
            $.ajax({
                url: `/get-role-permissions/${role_id}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 403 && response.show_toastr) {
                        // Show toastr error instead of JSON response
                        toastr.error(response.message, 'Permission Denied');
                        document.getElementsByClassName('errorSound')[0].play();
                    } else if (response.status === 200) {
                        // Redirect to the edit page for the specific role ID
                        window.location.href = `/role-and-permission-edit/${role_id}`;
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.show_toastr) {
                            toastr.error(response.message, 'Permission Denied');
                            document.getElementsByClassName('errorSound')[0].play();
                        }
                    } else {
                        toastr.error('An error occurred while checking permissions.', 'Error');
                        document.getElementsByClassName('errorSound')[0].play();
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndRoleAndPermissionUpdateForm').submit(function(e) {
            e.preventDefault();

             // Validate the form before submitting
            if (!$('#addAndRoleAndPermissionUpdateForm').valid()) {
                   document.getElementsByClassName('warningSound')[0].play(); //for sound
                        toastr.error('Invalid inputs, Check & try again!!','Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            const id = window.location.pathname.split('/').pop();
            console.log("id = " + id);
            let url = id && id !== 'group-role-and-permission' ? '/role-and-permission-update/' + id : '/role-and-permission-store';
            let type = 'post';

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
                            // $('#' + key + '_error').html(err_value);
                            // toastr.error(err_value[0], 'Validation Error');
                            toastr.error(err_value, 'Validation Error');
                            document.getElementsByClassName('errorSound')[0].play(); //for sound
                            resetFormAndValidation();
                        });
                    }
                    else if (response.status == 404) {
                        toastr.error(response.message, 'Error');
                        document.getElementsByClassName('errorSound')[0].play(); //for sound
                    } 
                    else if (response.status == 403) {
                        // Handle permission errors with toastr
                        if (response.show_toastr) {
                            toastr.error(response.message, 'Permission Denied');
                        } else {
                            toastr.error(response.message, 'Access Denied');
                        }
                        document.getElementsByClassName('errorSound')[0].play(); //for sound
                    } 
                    else {
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                        window.location.href = '/group-role-and-permission-view';
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 403) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.show_toastr) {
                            toastr.error(response.message, 'Permission Denied');
                        } else {
                            toastr.error(response.message, 'Access Denied');
                        }
                        document.getElementsByClassName('errorSound')[0].play();
                    } else {
                        toastr.error('An error occurred. Please try again.', 'Error');
                        document.getElementsByClassName('errorSound')[0].play();
                    }
                }
            });
        });


        // Delete Role & permissions
        $(document).on('click', '.delete_btn', function() {
            var role_id = $(this).val();
            console.log(role_id);
            $('#deleteModal').modal('show');
            $('#deleting_id').val(role_id);
            $('#deleteName').text('Delete Role & Pemissions');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var role_id = $('#deleting_id').val();
            $.ajax({
                url: 'role-and-permission-delete/' + role_id,
                type: 'delete',
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });
    });
</script>
