<script type="text/javascript">
    $(document).ready(function () {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');  //for crf token
        showFetchData();
        populateLocationDropdown();

    // add form and update validation rules code start
              var addAndUpdateValidationOptions = {
        rules: {
              name: {
                required: true,
            },
            address: {
                required: true,
            },
            province: {
                required: true,
            },
            district: {
                required: true,
            },
            city: {
                required: true,
            },
            email: {
                required: true,

            },
            mobile: {
                required: true,
            },
            telephone_no: {
                required: true,
            },
        },
        messages: {

            name: {
                required: "Name is required",
            },
            address: {
                required: "Address  is required",
            },
            province: {
                required: "Province  is required",
            },
            district: {
                required: "District  is required",
            },
            city: {
                required: "City  is required",
            },
            email: {
                required: "Email  is required",
            },
            mobile: {
                required: "Mobile  is required",
            },
            telephone_no: {
                required: "Telephone No  is required",
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
    $('#addAndLocationUpdateForm').validate(addAndUpdateValidationOptions);

  // add form and update validation rules code end

  // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields only if the form exists
            var form = $('#addAndLocationUpdateForm')[0];
            if (form) {
                form.reset();
            }
            // Reset the validation messages and states
            if ($('#addAndLocationUpdateForm').length) {
                $('#addAndLocationUpdateForm').validate().resetForm();
                $('#addAndLocationUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
                $('#addAndLocationUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
            }
            // Clear the district dropdown
            $('#edit_district').html('<option selected disabled>Select District</option>');
        }

        // Clear form and validation errors when the modal is hidden
            $('#addAndEditLocationModal').on('hidden.bs.modal', function () {
                resetFormAndValidation();
            });

        // Show Add Warranty Modal
        $('#addLocationButton').click(function() {
            $('#modalTitle').text('New Location');
            $('#modalButton').text('Save');
            $('#addAndLocationUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            $('#addAndEditLocationModal').modal('show');
        });

        // Fetch and Display Data
        function showFetchData() {
            $.ajax({
                url: '/location-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var table = $('#location').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter  + '</td>');
                        row.append('<td>' + item.name + '</td>');
                        row.append('<td>' + item.location_id + '</td>');
                        row.append('<td>' + item.address + '</td>');
                        row.append('<td>' + item.province + '</td>');
                        row.append('<td>' + item.district + '</td>');
                        row.append('<td>' + item.city + '</td>');
                        row.append('<td>' + item.email + '</td>');
                        row.append('<td>' + item.mobile + '</td>');
                        row.append('<td>' + item.telephone_no + '</td>');
                        
                        // Logo column
                        let logoColumn = '<td>';
                        if (item.logo_image) {
                            logoColumn += '<img src="/' + item.logo_image + '" alt="Logo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">';
                        } else {
                            logoColumn += '<span class="text-muted">No logo</span>';
                        }
                        logoColumn += '</td>';
                        row.append(logoColumn);
                        
                         row.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>');
                        // row.append(actionDropdown);
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

            // Show Edit Modal
            $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Location');
            $('#modalButton').text('Update');
            if ($('#addAndLocationUpdateForm').length) {
                $('#addAndLocationUpdateForm')[0].reset();
            }
            $('.text-danger').text('');
            $('#edit_id').val(id);
            
            // Clear logo preview
            $('#logo_preview').html('');

            $.ajax({
                url: 'location-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200) {

                        $('#edit_name').val(response.message.name);
                        $('#edit_location_id').val(response.message.location_id);
                        $('#edit_address').val(response.message.address);
                        $('#edit_province').val(response.message.province).trigger('change'); // when click edit location the provices is not again loaded to give the trigger function
                        $('#edit_district').val(response.message.district);
                        $('#edit_city').val(response.message.city);
                        $('#edit_email').val(response.message.email);
                        $('#edit_mobile').val(response.message.mobile);
                        $('#edit_telephone_no').val(response.message.telephone_no);
                        
                        // Show current logo if exists
                        if (response.message.logo_image) {
                            $('#logo_preview').html('<img src="/' + response.message.logo_image + '" alt="Current Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">');
                        }
                        
                        $('#addAndEditLocationModal').modal('show');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndLocationUpdateForm').submit(function(e) {
            e.preventDefault();

             // Validate the form before submitting
            if (!$('#addAndLocationUpdateForm').valid()) {
                   document.getElementsByClassName('warningSound')[0].play(); //for sound
                   toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error('Invalid inputs, Check & try again!!','Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'location-update/' + id : 'location-store';
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
                            document.getElementsByClassName('errorSound')[0].play(); //for sound
                            toastr.error(err_value,'Error');
                        });

                    } else {
                        $('#addAndEditLocationModal').modal('hide');
                           // Clear validation error messages
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    }
                }
            });
        });

           // it will Clear the serverside validation errors on input change
        // Clear validation error for specific fields on input change based on 'name' attribute
        $('#addAndLocationUpdateForm').on('input change', 'input', function() {
            var fieldName = $(this).attr('name');
            $('#' + fieldName + '_error').html(''); // Clear specific field error message
        });

        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete location');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'location-delete/' + id,
                type: 'delete',
                headers: {'X-CSRF-TOKEN': csrfToken},
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        showFetchData();
                        populateLocationDropdown();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {"closeButton": true,"positionClass": "toast-top-right"};
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });


    // Province to district mapping code start
    var provinceDistricts = {
        'Western': ['Colombo', 'Gampaha', 'Kalutara'],
        'Central': ['Kandy', 'Matale', 'Nuwara Eliya'],
        'Southern': ['Galle', 'Matara', 'Hambantota'],
        'North Western': ['Kurunegala', 'Puttalam'],
        'North Central': ['Anuradhapura', 'Polonnaruwa'],
        'Northern': ['Jaffna', 'Kilinochchi', 'Mullaitivu'],
        'Eastern': ['Ampara', 'Batticaloa', 'Trincomalee'],
        'Uva': ['Badulla', 'Monaragala'],
        'Sabaragamuwa': ['Kegalle', 'Ratnapura']
    };

    // When province changes
    $('#edit_province').on('change', function () {
        var selectedProvince = $(this).val();
        console.log(selectedProvince);
        var districts = provinceDistricts[selectedProvince]; // Get districts for the selected province
        console.log(districts);
        // Clear the district dropdown
        $('#edit_district').html('<option selected disabled>Select District</option>');

        // Populate the district dropdown
        if (districts) {
            districts.forEach(function (district) {
                $('#edit_district').append('<option value="' + district + '">' + district + '</option>');
            });
        }
    });

    // Province to district mapping code finished

    function populateLocationDropdown() {
    $.ajax({
        url: "/location-get-all", // Route URL
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.status === 200) {
                let dropdown = $(".locationDropdown");
                dropdown.empty().append('<option value="">Select Location</option>');

                $.each(response.message, function(index, location) {
                    dropdown.append('<option value="' + location.id + '">' + location.name + '</option>');
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

    // Logo image preview functionality
    $('#edit_logo_image').on('change', function() {
        const file = this.files[0];
        const preview = $('#logo_preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.html('<img src="' + e.target.result + '" alt="Logo Preview" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">');
            };
            reader.readAsDataURL(file);
        } else {
            preview.html('');
        }
    });

    });
</script>
