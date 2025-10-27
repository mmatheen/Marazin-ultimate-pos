<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
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
            var counter = 1;
            response.values.forEach(function(item) {
                let row = $('<tr>');
                row.append('<td>' + counter + '</td>');
                row.append('<td>' + item.role_name + '</td>');
                
                // Display permission count and View All button
                let permissionCount = item.permissions.length;
                let permissionsHtml = '<div class="d-flex align-items-center justify-content-between">';
                permissionsHtml += '<span class="permission-count-badge">' + permissionCount + ' Permission' + (permissionCount !== 1 ? 's' : '') + '</span>';
                permissionsHtml += '<button type="button" class="btn btn-sm btn-outline-primary view-permissions-btn" data-role-id="' + item.role_id + '" data-role-name="' + item.role_name + '" data-permissions=\'' + JSON.stringify(item.permissions) + '\'>';
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

    // Handle View All Permissions button click
    $(document).on('click', '.view-permissions-btn', function() {
        let roleName = $(this).data('role-name');
        let permissions = $(this).data('permissions');
        
        // Set modal title
        $('#permissionsModalLabel').html('<i class="feather-shield"></i> ' + roleName);
        
        // Group permissions by their actual group structure
        let groupedPermissions = {};
        
        permissions.forEach(function(permission) {
            let permName = permission.name;
            
            // Extract group from permission name patterns
            // Pattern 1: "create user" -> group: "User Management"
            // Pattern 2: "view product" -> group: "Product Management"
            let groupName = 'Other';
            
            // Define group mapping based on keywords
            if (permName.includes('user') && !permName.includes('customer')) {
                groupName = 'User Management';
            } else if (permName.includes('role')) {
                groupName = 'Role & Permission Management';
            } else if (permName.includes('supplier')) {
                groupName = 'Supplier Management';
            } else if (permName.includes('customer')) {
                groupName = 'Customer Management';
            } else if (permName.includes('product')) {
                groupName = 'Product Management';
            } else if (permName.includes('unit')) {
                groupName = 'Unit Management';
            } else if (permName.includes('brand')) {
                groupName = 'Brand Management';
            } else if (permName.includes('batch')) {
                groupName = 'Batch Management';
            } else if (permName.includes('category') || permName.includes('sub-category')) {
                groupName = 'Category Management';
            } else if (permName.includes('warranty')) {
                groupName = 'Warranty Management';
            } else if (permName.includes('variation')) {
                groupName = 'Variation Management';
            } else if (permName.includes('sale') && !permName.includes('wholesale')) {
                groupName = 'Sales Management';
            } else if (permName.includes('purchase')) {
                groupName = 'Purchase Management';
            } else if (permName.includes('payment')) {
                groupName = 'Payment Management';
            } else if (permName.includes('expense')) {
                groupName = 'Expense Management';
            } else if (permName.includes('parent-expense') || permName.includes('child-expense')) {
                groupName = 'Expense Category';
            } else if (permName.includes('stock')) {
                groupName = 'Stock Management';
            } else if (permName.includes('location')) {
                groupName = 'Location Management';
            } else if (permName.includes('discount')) {
                groupName = 'Discount Management';
            } else if (permName.includes('quotation')) {
                groupName = 'Quotation Management';
            } else if (permName.includes('invoice')) {
                groupName = 'Invoice Management';
            } else if (permName.includes('report')) {
                groupName = 'Reports';
            } else if (permName.includes('settings') || permName.includes('business-settings') || permName.includes('tax-settings')) {
                groupName = 'Settings';
            } else if (permName.includes('route')) {
                groupName = 'Route Management';
            } else if (permName.includes('vehicle')) {
                groupName = 'Vehicle Management';
            } else if (permName.includes('database')) {
                groupName = 'Database Management';
            } else if (permName.includes('master admin')) {
                groupName = 'System Administration';
            } else if (permName.includes('sales-rep')) {
                groupName = 'Sales Rep Management';
            } else if (permName.includes('cities')) {
                groupName = 'City Management';
            } else if (permName.includes('currencies')) {
                groupName = 'Currency Management';
            } else if (permName.includes('barcode') || permName.includes('label')) {
                groupName = 'Label & Barcode';
            } else if (permName.includes('super admin')) {
                groupName = 'Super Admin';
            }
            
            if (!groupedPermissions[groupName]) {
                groupedPermissions[groupName] = [];
            }
            groupedPermissions[groupName].push(permName);
        });
        
        // Sort groups alphabetically
        let sortedGroups = Object.keys(groupedPermissions).sort();
        
        // Build the permissions HTML grouped by category with beautiful UI
        let permissionsHtml = '<div class="permissions-grid">';
        
        sortedGroups.forEach(function(groupName, groupIndex) {
            let iconClass = getGroupIcon(groupName);
            let colorClass = 'color-' + (groupIndex % 6 + 1);
            
            permissionsHtml += '<div class="permission-card ' + colorClass + '">';
            permissionsHtml += '<div class="permission-card-header">';
            permissionsHtml += '<div class="header-left">';
            permissionsHtml += '<i class="' + iconClass + '"></i>';
            permissionsHtml += '<span class="group-title">' + groupName + '</span>';
            permissionsHtml += '</div>';
            permissionsHtml += '<span class="badge-count">' + groupedPermissions[groupName].length + '</span>';
            permissionsHtml += '</div>';
            permissionsHtml += '<div class="permission-card-body">';
            
            groupedPermissions[groupName].forEach(function(permissionName) {
                let actionIcon = getActionIcon(permissionName);
                permissionsHtml += '<div class="permission-pill">';
                permissionsHtml += '<i class="' + actionIcon + '"></i>';
                permissionsHtml += '<span>' + permissionName + '</span>';
                permissionsHtml += '</div>';
            });
            
            permissionsHtml += '</div>';
            permissionsHtml += '</div>';
        });
        
        permissionsHtml += '</div>';
        
        $('#permissionsModalBody').html(permissionsHtml);
        
        // Show the modal
        $('#viewPermissionsModal').modal('show');
    });
    
    // Helper function to get icon based on group name
    function getGroupIcon(groupName) {
        const iconMap = {
            'User Management': 'feather-users',
            'Role & Permission Management': 'feather-shield',
            'Supplier Management': 'feather-truck',
            'Customer Management': 'feather-user-check',
            'Product Management': 'feather-package',
            'Sales Management': 'feather-shopping-cart',
            'Purchase Management': 'feather-shopping-bag',
            'Stock Management': 'feather-layers',
            'Payment Management': 'feather-credit-card',
            'Expense Management': 'feather-trending-down',
            'Reports': 'feather-bar-chart-2',
            'Settings': 'feather-settings',
            'Location Management': 'feather-map-pin',
            'Invoice Management': 'feather-file-text',
            'Database Management': 'feather-database',
            'System Administration': 'feather-shield'
        };
        return iconMap[groupName] || 'feather-folder';
    }
    
    // Helper function to get icon based on action
    function getActionIcon(permissionName) {
        if (permissionName.includes('create') || permissionName.includes('add')) {
            return 'feather-plus-circle';
        } else if (permissionName.includes('edit') || permissionName.includes('update')) {
            return 'feather-edit-2';
        } else if (permissionName.includes('delete') || permissionName.includes('remove')) {
            return 'feather-trash-2';
        } else if (permissionName.includes('view') || permissionName.includes('show')) {
            return 'feather-eye';
        } else if (permissionName.includes('import')) {
            return 'feather-download';
        } else if (permissionName.includes('export')) {
            return 'feather-upload';
        } else if (permissionName.includes('manage')) {
            return 'feather-tool';
        } else if (permissionName.includes('assign')) {
            return 'feather-check-circle';
        }
        return 'feather-check';
    }


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
