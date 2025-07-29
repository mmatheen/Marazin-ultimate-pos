<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
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
        $('#addAndEditLocationModal').on('hidden.bs.modal', function() {
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

        let counter = 0;

        function showFetchData() {
            if ($.fn.DataTable.isDataTable('#location')) {
                $('#location').DataTable().destroy();
            }

            $('#location').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '/location-get-all',
                    type: 'GET',
                    dataSrc: function(res) {
                        return res.status ? res.data : [];
                    }
                },
                columns: [{
                        data: null,
                        render: () => ++counter
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'location_id'
                    },
                    {
                        data: 'parent',
                        render: data => data ? data.name : '—'
                    },
                    {
                        data: 'address'
                    },
                    {
                        data: 'province'
                    },
                    {
                        data: 'district'
                    },
                    {
                        data: 'city'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'mobile'
                    },
                    {
                        data: 'telephone_no'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: data => `
                        @can('edit location')
                        <button value="${data.id}" class='edit_btn btn btn-sm btn-outline-info me-2'><i class="feather-edit"></i> Edit</button>
                        @endcan
                        @can('delete location')
                        <button value="${data.id}" class='delete_btn btn btn-sm btn-outline-danger'><i class="feather-trash-2"></i> Delete</button>
                        @endcan
                `
                    }
                ]
            });
        }

        // Show Edit Modal
        $(document).on('click', '.edit_btn', function() {
            const id = $(this).val();

            $('#modalTitle').text('Edit Location');
            $('#modalButton').text('Update');
            $('#edit_id').val(id);

            $('#addAndEditLocationModal').modal('show');

            $.ajax({
                url: '/location-edit/' + id,
                type: 'GET',
                success: function(res) {
                    if (res.status && res.data) {
                        const d = res.data;
                        $('#edit_name').val(d.name);
                        $('#edit_location_id').val(d.location_id);
                        $('#edit_parent_id').val(d.parent_id);
                        $('#edit_address').val(d.address);
                        $('#edit_province').val(d.province).trigger('change');
                        $('#edit_district').val(d.district);
                        $('#edit_city').val(d.city);
                        $('#edit_email').val(d.email);
                        $('#edit_mobile').val(d.mobile);
                        $('#edit_telephone_no').val(d.telephone_no);
                        $('#addAndEditLocationModal').modal('show');
                    } else {
                        toastr.error(res.message || 'Not found');
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
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'location-update/' + id : 'location-store';
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
                            document.getElementsByClassName('errorSound')[0]
                                .play(); //for sound
                            toastr.error(err_value, 'Error');
                        });

                    } else {
                        $('#addAndEditLocationModal').modal('hide');
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
                        populateLocationDropdown();
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
        $('#edit_province').on('change', function() {
            var selectedProvince = $(this).val();
            console.log(selectedProvince);
            var districts = provinceDistricts[
                selectedProvince]; // Get districts for the selected province
            console.log(districts);
            // Clear the district dropdown
            $('#edit_district').html('<option selected disabled>Select District</option>');

            // Populate the district dropdown
            if (districts) {
                districts.forEach(function(district) {
                    $('#edit_district').append('<option value="' + district + '">' + district +
                        '</option>');
                });
            }
        });

        // Province to district mapping code finished

        // function populateLocationDropdown() {
        //     $.ajax({
        //         url: "/location-get-all", // Route URL
        //         type: "GET",
        //         dataType: "json",
        //         success: function(response) {
        //             if (response.status === 200) {
        //                 let dropdown = $(".locationDropdown");
        //                 dropdown.empty().append('<option value="">Select Location</option>');

        //                 $.each(response.message, function(index, location) {
        //                     dropdown.append('<option value="' + location.id + '">' +
        //                         location.name + '</option>');
        //                 });
        //             } else {
        //                 alert(response.message);
        //             }
        //         },
        //         error: function(xhr) {
        //             console.log("Error:", xhr);
        //         }
        //     });
        // }

        function populateLocationDropdown() {
            $.ajax({
                url: '/location-get-all',
                type: 'GET',
                success: function(res) {
                    if (res.status && Array.isArray(res.data)) {
                        const dropdown = $('#edit_parent_id');
                        dropdown.empty().append('<option value="">No Parent (Main)</option>');
                        res.data.forEach(loc => {
                            if (!loc.parent_id) { // Only main locations
                                dropdown.append(
                                    `<option value="${loc.id}">${loc.name}</option>`);
                            }
                        });
                    }
                }
            });
        }


    });
</script>
