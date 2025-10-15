<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        // Province-District mapping for Sri Lanka
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

        // City validation rules for reusable modal
        var cityValidationOptions = {
            rules: {
                name: {
                    required: true,
                },
                district: {
                    required: true,
                },
                province: {
                    required: true,
                },
            },
            messages: {
                name: {
                    required: "City Name is required",
                },
                district: {
                    required: "District is required",
                },
                province: {
                    required: "Province is required",
                },
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                if (element.attr('name') === 'name') {
                    error.insertAfter(element).attr('id', 'city_name_error');
                } else if (element.attr('name') === 'district') {
                    error.insertAfter(element).attr('id', 'city_district_error');
                } else if (element.attr('name') === 'province') {
                    error.insertAfter(element).attr('id', 'city_province_error');
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to city form (for reusable modal)
        $('#cityAddAndUpdateForm').validate(cityValidationOptions);

        // Function to populate districts based on selected province (reusable)
        function populateDistricts(provinceValue, selectedDistrict = '') {
            const districtSelect = $('#edit_city_district');
            districtSelect.empty();
            districtSelect.append('<option value="">Select District</option>');

            if (provinceValue && provinceDistricts[provinceValue]) {
                provinceDistricts[provinceValue].forEach(function(district) {
                    const selected = district === selectedDistrict ? 'selected' : '';
                    districtSelect.append(
                        `<option value="${district}" ${selected}>${district}</option>`);
                });
                districtSelect.prop('disabled', false);
            } else {
                districtSelect.prop('disabled', true);
            }
        }

        // Handle province change in reusable modal
        $(document).on('change', '#edit_city_province', function() {
            populateDistricts($(this).val());
        });

        // Function to reset city form and validation errors
        function resetCityFormAndValidation() {
            $('#cityAddAndUpdateForm')[0].reset();
            $('#cityAddAndUpdateForm').validate().resetForm();
            $('#cityAddAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#cityAddAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
            $('#edit_city_district').prop('disabled', true);
            $('.text-danger').text('');
        }

        // Clear city form when modal is hidden
        $('#addAndEditCityModal').on('hidden.bs.modal', function() {
            resetCityFormAndValidation();
        });

        // Re-initialize Select2 when city modal is shown to fix typing/search functionality
        $('#addAndEditCityModal').on('shown.bs.modal', function() {
            // Re-initialize Select2 dropdowns in the city modal
            $('#addAndEditCityModal .selectBox').select2({
                dropdownParent: $('#addAndEditCityModal')
            });
        });

        // Show Add City Modal (for reusable modal)
        $(document).on('click', '#addCityButton', function() {
            $('#cityModalTitle').text('New City');
            $('#cityModalButton').text('Save');
            resetCityFormAndValidation();
            $('#edit_city_id').val('');
            $('#addAndEditCityModal').modal('show');
        });

        // Function to populate city edit form (for cities management page)
        function populateCityEditForm(data) {
            $('#edit_city_id').val(data.id);
            $('#edit_city_name').val(data.name);
            $('#edit_city_province').val(data.province || '').trigger('change');

            // Wait for province change to populate districts, then set district
            setTimeout(() => {
                $('#edit_city_district').val(data.district || '');
            }, 100);

            $('#cityModalTitle').text('Edit City');
            $('#cityModalButton').text('Update');
            $('.text-danger').text('');
        }

        // Make function globally available
        window.populateCityEditForm = populateCityEditForm;

        // Submit City Add/Update Form (for reusable modal)
        $('#cityAddAndUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#cityAddAndUpdateForm').valid()) {
                if (document.getElementsByClassName('warningSound')[0]) {
                    document.getElementsByClassName('warningSound')[0].play();
                }
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.warning('Invalid inputs, Check & try again!!', 'Warning');
                return;
            }

            let formData = new FormData(this);
            let id = $('#edit_city_id').val();
            let url = id ? '/api/cities/' + id : '/api/cities';
            let type = id ? 'PUT' : 'POST';

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
                    if (response.status === false && response.errors) {
                        $.each(response.errors, function(key, err_value) {
                            $('#city_' + key + '_error').html(Array.isArray(
                                err_value) ? err_value[0] : err_value);
                        });
                    } else if (response.status === true) {
                        let newCityId = response.data.id;

                        $('#addAndEditCityModal').modal('hide');

                        // Refresh the cities dropdown if it exists (for customer modal)
                        if (typeof fetchCities === 'function') {
                            fetchCities(newCityId);
                        }

                        // Refresh cities table if it exists (for cities management page)
                        if ($.fn.DataTable.isDataTable('#citiesTable')) {
                            $('#citiesTable').DataTable().ajax.reload();
                        }

                        if (document.getElementsByClassName('successSound')[0]) {
                            document.getElementsByClassName('successSound')[0].play();
                        }
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');

                        resetCityFormAndValidation();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('City Error response:', xhr.status, xhr.responseJSON);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, err_value) {
                            $('#city_' + key + '_error').html(Array.isArray(
                                err_value) ? err_value[0] : err_value);
                        });
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Please fix the errors and try again.', 'Error');
                    } else {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Unable to create city. Please try again.', 'Error');
                    }
                }
            });
        });

        // Function to fetch and populate cities dropdown (reusable)
        function fetchCities(selectedCityId = null) {
            $.ajax({
                url: '/api/cities',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status && response.data) {
                        // Check if we're using the custom city search
                        if ($('#city_search_input').length > 0) {
                            // Update the allCities array for custom search
                            if (window.allCities) {
                                window.allCities = response.data;
                            }

                            // If a new city was just created, select it
                            if (selectedCityId && window.setCityValue) {
                                const newCity = response.data.find(city => city.id ==
                                    selectedCityId);
                                if (newCity) {
                                    window.setCityValue(newCity.id, newCity.name);
                                }
                            }
                            return;
                        }

                        // Original Select2 dropdown logic for other pages
                        var citySelect = $('#edit_city_id');
                        if (citySelect.length && citySelect.is('select')) {
                            // Destroy existing Select2 if it exists
                            if (citySelect.hasClass('select2-hidden-accessible')) {
                                citySelect.select2('destroy');
                            }

                            citySelect.empty();
                            citySelect.append('<option value="">Select City</option>');

                            // Sort cities alphabetically
                            const sortedCities = response.data.sort((a, b) => a.name.localeCompare(b
                                .name));

                            sortedCities.forEach(function(city) {
                                const displayText = city.district && city.province ?
                                    `${city.name} (${city.district}, ${city.province})` :
                                    city.name;
                                citySelect.append(
                                    `<option value="${city.id}">${displayText}</option>`
                                );
                            });

                            // Re-initialize Select2
                            citySelect.select2();

                            // Select the newly added city if provided
                            if (selectedCityId) {
                                setTimeout(() => {
                                    citySelect.val(selectedCityId).trigger('change');
                                }, 100);
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching cities:', error);
                }
            });
        }

        // Make fetchCities function globally available
        window.fetchCities = fetchCities;
        window.populateDistricts = populateDistricts;
        window.provinceDistricts = provinceDistricts;
    });
</script>
