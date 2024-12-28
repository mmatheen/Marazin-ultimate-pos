<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
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
            $('#addAndEditMainCategoryModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
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
                        row.append('<td>' + counter  + '</td>');
                        row.append('<td>' + item.mainCategoryName + '</td>');
                        row.append('<td>' + item.description + '</td>');
                         row.append('<td><button type="button" value="' + item.id + '" class="main_edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' + item.id + '" class="main_delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>');
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
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
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
                   document.getElementsByClassName('warningSound')[0].play(); //for sound
                   toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                       toastr.warning('Invalid inputs, Check & try again!!','Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? '/main-category-update/' + id : '/main-category-store';
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
                        $('#addAndEditMainCategoryModal').modal('hide');
                           // Clear validation error messages
                        showFetchData();
                        populateItemMainCategoryDropdown();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });


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
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        populateItemMainCategoryDropdown();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });

        function populateItemMainCategoryDropdown() {
            // Fetch unit details to select box code start
            $.ajax({
                url: 'main-category-get-all', // Replace with your endpoint URL
                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        const MainSelect = $('#edit_main_category_id');
                        MainSelect.empty(); // Clear existing options
                        // Access itemMainCategories and subcategories from response data
                        const mainCategories = response.message;

                        if (mainCategories && mainCategories.length > 0) {

                            // If there are itemMainCategories or subcategories, add the default options and populate with data
                            MainSelect.append('<option selected disabled>Main Category</option>');
                            mainCategories.forEach(mainCategory => {
                                MainSelect.append(
                                    `<option value="${mainCategory.id}">${mainCategory.mainCategoryName}</option>`);
                            });
                        } else {
                            // If no records are found, show appropriate message
                            itemMainSelect.append(
                                '<option selected disabled>No item Main Categories available</option>');

                        }
                    }
                },
                error: function(error) {
                    console.log("Error:", error);
                }
            });
            // Fetch unit details to select box code start
        }


    });
</script>
