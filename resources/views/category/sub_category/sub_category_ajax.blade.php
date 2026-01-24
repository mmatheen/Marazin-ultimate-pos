<script type="text/javascript">
    $(document).ready(function() {
        // Get CSRF token at the top level - available for all pages
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        // Only initialize on sub category page, not on purchase/other pages
        if ($('#SubCategory').length) {
            console.log('✅ Initializing sub category page');
            showFetchData();
        } else {
            console.log('⏭️ Skipping sub category datatable (not on sub category page)');
        }

        // Note: fetchSubCategoryDropdown() will be called when modal opens, not on page load

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                subCategoryname: {
                    required: true,
                },
                main_category_id: {
                    required: true,
                },

            },
            messages: {

                subCategoryname: {
                    required: "Sub Category Name is required",
                },
                main_category_id: {
                    required: "Main Category is required",
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
        $('#addAndEditSubCategoryModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Re-initialize Select2 when modal is shown to fix typing/search functionality
        $('#addAndEditSubCategoryModal').on('shown.bs.modal', function() {
            // Re-initialize Select2 dropdowns in the modal
            $('#addAndEditSubCategoryModal .selectBox').select2({
                dropdownParent: $('#addAndEditSubCategoryModal')
            });
        });

        // Show Add Selling Price Group Modal
        $('#addSubCategoryButton').click(function() {
            $('#modalTitle').text('New Sub Category');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update

            // Fetch and populate main categories before showing the modal
            fetchMainCategoryDropdown();

            $('#addAndEditSubCategoryModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/sub-category-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#SubCategory').DataTable();
                    table.clear().draw();
                    var counter = 1;

                    // Check if response.message is an array before using forEach
                    if (Array.isArray(response.message)) {
                        response.message.forEach(function(item) {
                            let row = $('<tr>');
                            row.append('<td>' + counter + '</td>');
                            row.append('<td>' + item.main_category.mainCategoryName +
                                '</td>');
                            row.append('<td>' + item.subCategoryname + '</td>');
                            row.append('<td>' + item.subCategoryCode + '</td>');
                            row.append('<td>' + item.description + '</td>');
                            row.append('<td>' +
                                '@can('edit sub-category')<button type="button" value="' +
                                item.id +
                                '" class="sub_category_edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                                '@can('delete sub-category')<button type="button" value="' +
                                item.id +
                                '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                                '</td>');

                            table.row.add(row).draw(false);
                            counter++;
                        });
                    } else if (typeof response.message === 'string') {
                        // Handle case where response.message is a string like "No Records Found!"
                        console.log('No subcategories found: ', response.message);
                    }
                },
            });
        }

        // Show Edit Modal
        $(document).on('click', '.sub_category_edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Sub Category');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            // Fetch main categories first
            fetchMainCategoryDropdown();

            $.ajax({
                url: '/sub-category-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_subCategoryname').val(response.message.subCategoryname);

                        // Set main category value after a short delay to ensure dropdown is populated
                        setTimeout(function() {
                            $('#edit_main_category_id_sub').val(response.message.main_category_id);
                        }, 100);

                        $('#edit_subCategoryCode').val(response.message.subCategoryCode);
                        $('#edit_description').val(response.message.description);
                        $('#addAndEditSubCategoryModal').modal('show');
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
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? '/sub-category-update/' + id : '/sub-category-store';
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
                        });

                    } else {
                        $('#addAndEditSubCategoryModal').modal('hide');
                        fetchSubCategoryDropdown();
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


        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Main Category');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: '/sub-category-delete/' + id,
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
                        fetchSubCategoryDropdown();
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, 'Deleted');
                    }
                },
                error: function(xhr) {
                    // Check for SQLSTATE[23000] error code 1451 (foreign key constraint)
                    if (
                        xhr.responseJSON &&
                        (
                            (xhr.responseJSON.message && xhr.responseJSON.message.includes(
                                'SQLSTATE[23000]') && xhr.responseJSON.message.includes(
                                '1451')) ||
                            (xhr.responseJSON.exception && xhr.responseJSON.exception
                                .includes('Integrity constraint violation'))
                        )
                    ) {
                        toastr.error(
                            'This sub category cannot be deleted because it is associated with one or more products.',
                            'Delete Not Allowed');
                    } else {
                        toastr.error('An error occurred while deleting the sub category.',
                            'Error');
                    }
                }
            });
        });

        function fetchSubCategoryDropdown() {
            $.ajax({
                url: '/sub-category-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let dropdown = $('#edit_sub_category_id');
                    dropdown.empty().append('<option value="">Select Sub Category</option>');

                    // Check if response.message is an array before using $.each
                    if (Array.isArray(response.message)) {
                        $.each(response.message, function(index, item) {
                            dropdown.append(
                                `<option value="${item.id}">${item.subCategoryname}</option>`
                            );
                        });
                    } else if (typeof response.message === 'string') {
                        // Handle case where response.message is a string like "No Records Found!"
                        console.log('No subcategories found: ', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching dropdown data:", error);
                }
            });
        }

        // Function to fetch main categories for subcategory modal
        function fetchMainCategoryDropdown() {
            $.ajax({
                url: '/main-category-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let dropdown = $('#edit_main_category_id_sub');
                    dropdown.empty().append('<option value="" selected disabled>Please Select</option>');

                    // Check if response.message is an array before using $.each
                    if (Array.isArray(response.message)) {
                        $.each(response.message, function(index, item) {
                            dropdown.append(
                                `<option value="${item.id}">${item.mainCategoryName}</option>`
                            );
                        });
                        console.log('✅ Main categories loaded successfully for subcategory modal');
                    } else if (typeof response.message === 'string') {
                        console.log('No main categories found: ', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching main categories:", error);
                    toastr.error('Failed to load main categories', 'Error');
                }
            });
        }
    });
</script>
