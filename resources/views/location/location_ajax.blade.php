<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        populateLocationDropdown();

        // Function to toggle vehicle details visibility
        function toggleVehicleDetails() {
            const parentId = $('#edit_parent_id').val();
            const vehicleSection = $('#vehicleDetailsSection');
            const parentDetailsSection = $('#parentLocationDetails');
            
            if (parentId && parentId !== '') {
                // Sublocation - show vehicle details and make them required
                vehicleSection.slideDown(300);
                $('#edit_vehicle_number').prop('required', true);
                $('#edit_vehicle_type').prop('required', true);
                
                // Fetch and display parent location details
                fetchParentLocationDetails(parentId);
                parentDetailsSection.slideDown(300);
            } else {
                // Parent location - hide vehicle details and parent details
                vehicleSection.slideUp(300);
                parentDetailsSection.slideUp(300);
                $('#edit_vehicle_number').prop('required', false).val('');
                $('#edit_vehicle_type').prop('required', false).val('');
                // Clear any vehicle validation errors
                $('#vehicle_number_error').text('');
                $('#vehicle_type_error').text('');
            }
            
            // Revalidate the form
            if ($('#addAndLocationUpdateForm').length) {
                $('#addAndLocationUpdateForm').validate().resetForm();
            }
        }

        // Function to fetch parent location details and sublocations
        function fetchParentLocationDetails(parentId) {
            if (!parentId) return;
            
            $.ajax({
                url: '/location-hierarchy/' + parentId,
                type: 'GET',
                success: function(response) {
                    if (response.status && response.data) {
                        const parent = response.data;
                        
                        // Update parent details
                        $('#parentLocationName').text(parent.name + ' Details');
                        $('#parentAddress').text(parent.address || '-');
                        $('#parentCity').text(parent.city || '-');
                        $('#parentDistrict').text(parent.district || '-');
                        $('#parentTelephone').text(parent.telephone_no || '-');
                        
                        // Display existing sublocations
                        displaySublocations(parent.children || []);
                    }
                },
                error: function(xhr) {
                    console.log('Error fetching parent details:', xhr);
                    toastr.error('Failed to fetch parent location details');
                }
            });
        }

        // Function to display sublocations recursively
        function displaySublocations(sublocations) {
            const sublocationsList = $('#sublocationsList');
            const existingSublocations = $('#existingSublocations');
            
            if (sublocations && sublocations.length > 0) {
                sublocationsList.empty();
                
                sublocations.forEach(function(sublocation) {
                    const sublocationCard = createSublocationCard(sublocation);
                    sublocationsList.append(sublocationCard);
                });
                
                existingSublocations.slideDown(300);
            } else {
                existingSublocations.slideUp(300);
            }
        }

        // Function to create sublocation card HTML
        function createSublocationCard(sublocation) {
            const vehicleInfo = sublocation.vehicle_number 
                ? `<span class="sublocation-vehicle">� ${sublocation.vehicle_number}</span>`
                : '';
                
            return `
                <div class="sublocation-badge">
                    <strong>📍 ${sublocation.name}</strong><br>
                    <small>🏙️ ${sublocation.city || 'N/A'} • 📍 ${sublocation.district || 'N/A'}</small><br>
                    <small> 📞 ${sublocation.mobile || 'N/A'}</small>
                    ${vehicleInfo}
                </div>
            `;
        }

        // Event handler for parent location change
        $(document).on('change', '#edit_parent_id', function() {
            toggleVehicleDetails();
        });

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
                vehicle_number: {
                    required: function() {
                        return $('#edit_parent_id').val() !== '';
                    }
                },
                vehicle_type: {
                    required: function() {
                        return $('#edit_parent_id').val() !== '';
                    }
                }
            },
            messages: {
                name: {
                    required: "Name is required",
                },
                address: {
                    required: "Address is required",
                },
                province: {
                    required: "Province is required",
                },
                district: {
                    required: "District is required",
                },
                city: {
                    required: "City is required",
                },
                email: {
                    required: "Email is required",
                },
                mobile: {
                    required: "Mobile is required",
                },
                vehicle_number: {
                    required: "Vehicle number is required for sublocations",
                },
                vehicle_type: {
                    required: "Vehicle type is required for sublocations",
                }
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
            
            // Hide vehicle details section and reset vehicle fields
            $('#vehicleDetailsSection').hide();
            $('#edit_vehicle_number').val('').prop('required', false);
            $('#edit_vehicle_type').val('').prop('required', false);
            
            // Hide parent details section and reset parent info
            $('#parentLocationDetails').hide();
            $('#parentLocationName').text('Parent Location Details');
            $('#parentAddress, #parentCity, #parentDistrict, #parentTelephone').text('-');
            $('#existingSublocations').hide();
            $('#sublocationsList').empty();
            
            // Clear all error messages
            $('.text-danger').text('');
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditLocationModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Show Add Location Modal
        $('#addLocationButton').click(function() {
            $('#modalTitle').text('New Location');
            $('#modalButton').text('Save');
            $('#addAndLocationUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update
            
            // Reset vehicle details section
            $('#vehicleDetailsSection').hide();
            $('#edit_vehicle_number').prop('required', false);
            $('#edit_vehicle_type').prop('required', false);
            
            // Reset parent details section
            $('#parentLocationDetails').hide();
            
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
                        data: 'vehicle_number',
                        render: data => data || '—'
                    },
                    {
                        data: 'vehicle_type',
                        render: data => data || '—'
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
                        data: 'logo_url',
                        render: data => data ? `<img src="${data}" alt="Logo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">` : '—'
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
            
            // Clear logo preview
            $('#logo_preview').html('');

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
                        $('#edit_vehicle_number').val(d.vehicle_number || '');
                        $('#edit_vehicle_type').val(d.vehicle_type || '');
                        
                        // Display existing logo if available
                        if (d.logo_url) {
                            $('#logo_preview').html('<img src="' + d.logo_url + '" alt="Current Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">');
                        }
                        
                        // Show/hide vehicle details based on parent_id
                        toggleVehicleDetails();
                        
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
                    // Handle validation errors
                    if (response.status == 400 || response.status === false) {
                        // Handle validation errors
                        if (response.errors) {
                            $.each(response.errors, function(key, err_value) {
                                const errorMessage = Array.isArray(err_value) ? err_value[0] : err_value;
                                $('#' + key + '_error').html(errorMessage);
                                document.getElementsByClassName('errorSound')[0].play(); //for sound
                                
                                // Configure toastr for error messages
                                toastr.options = {
                                    "closeButton": true,
                                    "positionClass": "toast-top-right",
                                    "timeOut": "5000"
                                };
                                toastr.error(errorMessage, 'Validation Error');
                            });
                        } else {
                            document.getElementsByClassName('errorSound')[0].play();
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right",
                                "timeOut": "5000"
                            };
                            toastr.error(response.message || 'Validation failed', 'Error');
                        }
                    } else {
                        $('#addAndEditLocationModal').modal('hide');
                        // Clear validation error messages
                        showFetchData();
                        document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle HTTP error responses (422, 500, etc.)
                    document.getElementsByClassName('errorSound')[0].play();
                    
                    // Configure toastr options for all error messages
                    toastr.options = {
                        "closeButton": true,
                        "positionClass": "toast-top-right",
                        "timeOut": "5000"
                    };
                    
                    if (xhr.status === 422) {
                        // Laravel validation errors
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            $.each(response.errors, function(key, err_value) {
                                const errorMessage = Array.isArray(err_value) ? err_value[0] : err_value;
                                $('#' + key + '_error').html(errorMessage);
                                toastr.error(errorMessage, 'Validation Error');
                            });
                        } else if (response && response.message) {
                            toastr.error(response.message, 'Validation Error');
                        } else {
                            toastr.error('Validation failed. Please check your inputs.', 'Validation Error');
                        }
                    } else if (xhr.status === 500) {
                        toastr.error('Internal server error. Please try again.', 'Server Error');
                    } else if (xhr.status === 403) {
                        toastr.error('You do not have permission to perform this action.', 'Access Denied');
                    } else {
                        toastr.error('An error occurred. Please try again.', 'Error');
                    }
                }
            });
        });

        // it will Clear the serverside validation errors on input change
        // Clear validation error for specific fields on input change based on 'name' attribute
        $('#addAndLocationUpdateForm').on('input change', 'input, select', function() {
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
                },
                error: function(xhr, status, error) {
                    $('#deleteModal').modal('hide');
                    document.getElementsByClassName('errorSound')[0].play();
                    
                    toastr.options = {
                        "closeButton": true,
                        "positionClass": "toast-top-right",
                        "timeOut": "5000"
                    };
                    
                    if (xhr.status === 403) {
                        toastr.error('You do not have permission to delete this location.', 'Access Denied');
                    } else if (xhr.status === 404) {
                        toastr.error('Location not found.', 'Not Found');
                    } else if (xhr.status === 500) {
                        toastr.error('Internal server error. Please try again.', 'Server Error');
                    } else {
                        toastr.error('Failed to delete location. Please try again.', 'Delete Error');
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
