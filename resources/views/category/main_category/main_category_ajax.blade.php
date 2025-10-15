<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        populateItemMainCategoryDropdown();

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                mainCategoryName: {
                    required: true,
                },

            },
            messages: {

                mainCategoryName: {
                    required: "Main Category Name is required",
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
        $('#mainCategoryAddAndUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#mainCategoryAddAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#mainCategoryAddAndUpdateForm').validate().resetForm();
            $('#mainCategoryAddAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#mainCategoryAddAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditMainCategoryModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Re-initialize Select2 when modal is shown to fix typing/search functionality
        $('#addAndEditMainCategoryModal').on('shown.bs.modal', function() {
            // Re-initialize Select2 dropdowns in the modal
            $('#addAndEditMainCategoryModal .selectBox').select2({
                dropdownParent: $('#addAndEditMainCategoryModal')
            });
        });

        // Show Add Selling Price Group Modal
        $('#addMainCategoryButton').click(function() {
            $('#modalTitle').text('New Main Category');
            $('#modalButton').text('Save');
            $('#mainCategoryAddAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditMainCategoryModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/main-category-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#mainCategory').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.mainCategoryName + '</td>');
                        row.append('<td>' + item.description + '</td>');
                        row.append('<td>' +
                            '@can('edit main-category')<button type="button" value="' +
                            item.id +
                            '" class="main_edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can('delete main-category')<button type="button" value="' +
                            item.id +
                            '" class="main_delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                            '</td>');
                        // row.append(actionDropdown);
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        // Show Edit Modal
        $(document).on('click', '.main_edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Selling Price Group');
            $('#modalButton').text('Update');
            $('#mainCategoryAddAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: '/main-category-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {
                        $('#edit_mainCategoryName').val(response.message.mainCategoryName);
                        $('#edit_description').val(response.message.description);
                        $('#addAndEditMainCategoryModal').modal('show');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#mainCategoryAddAndUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#mainCategoryAddAndUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // For sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // For edit
            let url = id ? '/main-category-update/' + id : '/main-category-store';
            let type = 'post';

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
                    } else if (response.status == 403) {
                        document.getElementsByClassName('errorSound')[0].play(); //for sound
                        toastr.error(response.message, 'Error');
                    } else {
                        let newCategoryId = response
                            .newCategoryId; // Get the new category ID from response

                        $('#addAndEditMainCategoryModal').modal('hide');
                        showFetchData(); // Refresh data list

                        // Repopulate dropdown and ensure the new category is selected
                        populateItemMainCategoryDropdown(newCategoryId);

                        document.getElementsByClassName('successSound')[0]
                            .play(); // For sound
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

        // Function to Populate the Dropdown and Select the Newly Added Category
        function populateItemMainCategoryDropdown(selectedId = null) {
            $.ajax({
                url: '/main-category-get-all',
                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        const MainSelect1 = $('#edit_main_category_id_sub');
                        const MainSelect2 = $('#edit_main_category_id');

                        // Clear existing options
                        MainSelect1.html('');
                        MainSelect2.html('');

                        const mainCategories = response.message;

                        if (mainCategories && mainCategories.length > 0) {
                            // Add the default option
                            MainSelect1.append('<option disabled>Main Category</option>');
                            MainSelect2.append('<option disabled>Main Category</option>');

                            // Populate both dropdowns with data
                            mainCategories.forEach(mainCategory => {
                                const option =
                                    `<option value="${mainCategory.id}">${mainCategory.mainCategoryName}</option>`;
                                MainSelect1.append(option);
                                MainSelect2.append(option);
                            });

                            // Ensure the new category is selected **after** options are added
                            setTimeout(() => {
                                if (selectedId) {
                                    MainSelect1.val(selectedId).trigger(
                                        'change'); // Select new category
                                    MainSelect2.val(selectedId).trigger(
                                        'change'); // Select new category
                                }
                            }, 300); // Short delay to ensure options are loaded before selecting
                        } else {
                            // If no records are found, show appropriate message
                            MainSelect1.append(
                                '<option disabled>No item Main Categories available</option>');
                            MainSelect2.append(
                                '<option disabled>No item Main Categories available</option>');
                        }
                    }
                },
                error: function(error) {
                    console.log("Error:", error);
                }
            });
        }


        // Delete Warranty
        $(document).on('click', '.main_delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Main Category');
        });

        $(document).on('click', '.confirm_main_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: '/main-category-delete/' + id,
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
                        populateItemMainCategoryDropdown();
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
                            'This category cannot be deleted because it is associated with one or more products.',
                            'Delete Not Allowed');
                    } else {
                        toastr.error('An error occurred while deleting the category.',
                            'Error');
                    }
                }
            });
        });





    });
</script>
