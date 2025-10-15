<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: {
                variation_title_id: {
                    required: true,
                },
                variation_value: {
                    required: true,
                },

            },
            messages: {

                variation_title_id: {
                    required: "Variation Title is required",
                },

                variation_value: {
                    required: "Variation Name is required",
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
        $('#addAndEditVariationModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Re-initialize Select2 when modal is shown to fix typing/search functionality
        $('#addAndEditVariationModal').on('shown.bs.modal', function() {
            // Re-initialize Select2 dropdowns in the modal
            $('#addAndEditVariationModal .selectBox').select2({
                dropdownParent: $('#addAndEditVariationModal')
            });
        });

        // Show Add Selling Price Group Modal
        $('#addVariationButton').click(function() {
            $('#modalTitle').text('New Variation');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditVariationModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/variation-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#variation').DataTable();
                    table.clear().draw();

                    // Group by variation_title_id
                    var groupedData = {};

                    response.message.forEach(function(item) {
                        if (!groupedData[item.variation_title_id]) {
                            groupedData[item.variation_title_id] = {
                                title: item.variation_title.variation_title,
                                values: []
                            };
                        }
                        groupedData[item.variation_title_id].values.push(item
                            .variation_value);
                    });

                    // Display grouped data
                    var counter = 1;
                    $.each(groupedData, function(variation_title_id, data) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + data.title + '</td>');
                        row.append('<td>' + data.values.join(', ') + '</td>');
                        row.append('<td><button type="button" value="' +
                            variation_title_id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' +
                            variation_title_id +
                            '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>'
                            );
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        // Show Edit Modal
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            console.log(id);
            $('#modalTitle').text('Edit Variations');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: '/variation-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status === 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status === 200) {
                        var variations = response.message;

                        if (variations.length > 0) {
                            $('#edit_variations_container')
                        .empty(); // Clear existing inputs

                            // Set the selected variation title
                            $('#edit_variation_title').val(variations[0]
                            .variation_title_id);

                            // Loop through each variation and create input fields for editing
                            $.each(variations, function(index, variation) {
                                $('#edit_variations_container').append(`
                                    <div class="form-group mb-3">
                                        <input type="text" class="form-control" id="variation_value_${variation.id}" name="variation_values[${variation.id}]" value="${variation.variation_value}">
                                    </div>
                                `);
                            });

                            $('#addAndEditVariationModal').modal('show');
                        } else {
                            toastr.error('No data found', 'Error');
                        }
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
                toastr.error('Invalid inputs, Check & try again!!', 'Error');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? '/variation-update/' + id : '/variation-store';
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
                            document.getElementsByClassName('warningSound')[0]
                            .play(); //for sound
                            $('#' + key + '_error').html(err_value);
                            toastr.error(err_value, 'Error');
                        });

                    } else {
                        $('#addAndEditVariationModal').modal('hide');
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

        // it will Clear the serverside validation errors on input change
        // Clear validation error for specific fields on input change based on 'name' attribute
        $('#addAndUpdateForm').on('input change', 'input', function() {
            var fieldName = $(this).attr('name');
            $('#' + fieldName + '_error').html(''); // Clear specific field error message
        });

        // Delete Variation
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete Variation');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: '/variation-delete/' + id,
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
                }
            });
        });
    });
</script>
