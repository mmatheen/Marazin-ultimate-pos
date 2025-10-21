<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        showFetchData();
        fetchCustomerData();
        fetchCities();

        // Check if current user is a sales rep
        var isSalesRep = @json(auth()->user()->hasRole('Sales Rep'));

        // Build validation rules conditionally
        var validationRules = {
            first_name: {
                required: true,
            },
            mobile_no: {
                required: true,
            },
            credit_limit: {
                required: true,
                number: true,
            },
            customer_type: {
                required: true,
            },
        };

        var validationMessages = {
            first_name: {
                required: "First Name is required",
            },
            mobile_no: {
                required: "Mobile No is required",
            },
            credit_limit: {
                required: "Credit Limit is required",
                number: "Credit Limit must be a number",
            },
            customer_type: {
                required: "Customer Type is required",
            },
        };

        // Add city validation only for sales reps
        if (isSalesRep) {
            validationRules.city_id = {
                required: true,
            };
            validationMessages.city_id = {
                required: "City is required for sales representatives",
            };
        }

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: validationRules,
            messages: validationMessages,
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
            // Remove info banner
            $('.city-info-banner').remove();
            // Clear custom city search
            if (window.setCityValue) {
                window.setCityValue('', '');
            }
            $('#city_dropdown').hide();
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditCustomerModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Custom city search is now handled by initializeCitySearch() function

        // Initialize Select2 for other dropdowns when modal opens (exclude city search)
        $('#addAndEditCustomerModal').on('shown.bs.modal', function() {
            $('#addAndEditCustomerModal .selectBox:not(.city-search-input)').select2({
                dropdownParent: $('#addAndEditCustomerModal'),
                minimumResultsForSearch: -1,
                width: "100%"
            });
        });

        // Show Add Customer Modal
        $('#addCustomerButton').click(function() {
            $('#modalTitle').text('New Customer');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text(''); // Clear all error messages
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update

            // Set default customer type to retailer
            $('#edit_customer_type').val('retailer').trigger('change');

            // Show helpful message for non-sales rep users
            if (!isSalesRep) {
                // Add a subtle info banner at the top of the modal
                if ($('.city-info-banner').length === 0) {
                    const infoBanner = `
                        <div class="alert alert-info alert-dismissible fade show city-info-banner" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>City Selection:</strong> Adding a city is optional but helps sales representatives filter customers by location.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    $('.modal-body .text-center').after(infoBanner);
                }
            }

            // Clear city search input for new customer
            if (window.setCityValue) {
                window.setCityValue('', '');
            }

            $('#addAndEditCustomerModal').modal('show');
        });

        function showFetchData() {
            $.ajax({
                url: '/customer-get-all',
                type: 'GET',
                dataType: 'json',
                xhrFields: {
                    withCredentials: true // 🔥 Required
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    var table = $('#customer').DataTable();
                    table.clear().draw();
                    var counter = 1;
                    response.message.forEach(function(item) {
                        let row = $('<tr>');
                        row.append('<td>' + counter + '</td>');
                        row.append('<td>' + item.prefix + '</td>');
                        row.append('<td>' + item.first_name + '</td>');
                        row.append('<td>' + item.last_name + '</td>');
                        row.append('<td>' + item.mobile_no + '</td>');
                        row.append('<td>' + item.email + '</td>');
                        row.append('<td>' + item.city_name + '</td>');
                        row.append('<td>' + (item.customer_type ? item.customer_type.charAt(
                                0).toUpperCase() + item.customer_type.slice(1) :
                            'Not Set') + '</td>');
                        row.append('<td>' + item.address + '</td>');
                        row.append('<td>' + item.opening_balance + '</td>');
                        row.append('<td>' + item.credit_limit + '</td>');
                        row.append('<td>' + item.total_sale_due + '</td>');
                        row.append('<td>' + item.total_return_due + '</td>');


                        row.append('<td>' +
                            '@can('view customer')<button type="button" value="' +
                            item.id +
                            '" class="ledger_btn btn btn-outline-primary btn-sm me-2"><i class="feather-book-open text-primary"></i> Ledger</button>@endcan' +
                            '@can('edit customer')<button type="button" value="' +
                            item.id +
                            '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button>@endcan' +
                            '@can('delete customer')<button type="button" value="' +
                            item.id +
                            '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i> Delete</button>@endcan' +
                            '</td>');
                        table.row.add(row).draw(false);
                        counter++;
                    });
                },
            });
        }

        //Fetch cities
        // Simple city search implementation
        var allCities = [];
        var selectedCityId = '';
        var citySearchInitialized = false;

        // Make allCities globally accessible
        window.allCities = allCities;

        function fetchCities() {
            $.ajax({
                url: '/api/cities',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status && response.data) {
                        allCities = response.data;
                        window.allCities = allCities; // Keep global reference updated
                        if (!citySearchInitialized) {
                            setupCitySearch();
                            citySearchInitialized = true;
                        }
                    }
                }
            });
        }

        function setupCitySearch() {
            const input = $('#city_search_input');
            const dropdown = $('#city_dropdown');
            const hiddenInput = $('#edit_city_id');
            let currentIndex = -1;

            // Search as user types
            input.on('input', function() {
                const query = $(this).val().trim();
                selectedCityId = '';
                hiddenInput.val('');
                currentIndex = -1;

                if (query === '') {
                    dropdown.hide();
                    return;
                }

                const matches = allCities.filter(city =>
                    city.name.toLowerCase().includes(query.toLowerCase())
                );

                if (matches.length > 0) {
                    showResults(matches);
                    currentIndex = 0; // Select first result by default
                    highlightOption();
                } else {
                    showNoResults();
                }
            });

            // Keyboard navigation
            input.on('keydown', function(e) {
                const options = $('.city-option');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentIndex < options.length - 1) {
                        currentIndex++;
                        highlightOption();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        currentIndex--;
                        highlightOption();
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex >= 0 && options.eq(currentIndex).length) {
                        selectCity(options.eq(currentIndex));
                    }
                } else if (e.key === 'Escape') {
                    dropdown.hide();
                    currentIndex = -1;
                }
            });

            // Clear text when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.city-search-container').length) {
                    dropdown.hide();
                    if (!selectedCityId && input.val().trim() !== '') {
                        input.val('');
                        hiddenInput.val('');
                    }
                }
            });

            function showResults(cities) {
                const content = cities.map(city =>
                    `<div class="city-option" data-id="${city.id}" data-name="${city.name}">${city.name}</div>`
                ).join('');

                $('.city-dropdown-content').html(content);
                dropdown.show();

                $('.city-option').on('click', function() {
                    selectCity($(this));
                });
            }

            function selectCity(option) {
                const cityId = option.data('id');
                const cityName = option.data('name');

                selectedCityId = cityId;
                input.val(cityName);
                hiddenInput.val(cityId);
                dropdown.hide();
                currentIndex = -1;
            }

            function highlightOption() {
                $('.city-option').removeClass('highlighted');
                if (currentIndex >= 0) {
                    $('.city-option').eq(currentIndex).addClass('highlighted');
                }
            }

            function showNoResults() {
                $('.city-dropdown-content').html('<div class="city-no-results">No cities found</div>');
                dropdown.show();
            }

            window.setCityValue = function(cityId, cityName) {
                selectedCityId = cityId || '';
                input.val(cityName || '');
                hiddenInput.val(cityId || '');
            };
        }


        // Show Edit Modal
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).val();
            $('#modalTitle').text('Edit Customer');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'customer-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200 && response.customer) {
                        $('#edit_prefix').val(response.customer.prefix || '').trigger(
                            'change');
                        $('#edit_first_name').val(response.customer.first_name || '');
                        $('#edit_last_name').val(response.customer.last_name || '');
                        $('#edit_mobile_no').val(response.customer.mobile_no || '');
                        $('#edit_email').val(response.customer.email || '');
                        $('#edit_address').val(response.customer.address || '');
                        $('#edit_opening_balance').val(response.customer.opening_balance ||
                            '');
                        $('#edit_credit_limit').val(response.customer.credit_limit || '');
                        // Set city value using custom function
                        const cityId = response.customer.city_id || '';
                        const cityName = response.customer.city_name || '';
                        if (window.setCityValue) {
                            window.setCityValue(cityId, cityName);
                        }

                        $('#edit_customer_type').val(response.customer.customer_type || '')
                            .trigger('change');

                        // Show modal
                        $('#addAndEditCustomerModal').modal('show');
                    } else {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Failed to load customer data', 'Error');
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
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'customer-update/' + id : 'customer-store';
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
                    if (response.status == 200) {
                        $('#addAndEditCustomerModal').modal('hide');
                        // Clear validation error messages
                        showFetchData();
                        fetchCustomerData();
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    } else if (response.status == 400) {
                        // Handle validation errors that come through success callback
                        if (response.errors) {
                            // Display field-specific validation errors
                            $.each(response.errors, function(key, err_value) {
                                $('#' + key + '_error').html(Array.isArray(
                                    err_value) ? err_value[0] : err_value);
                            });

                            // Show simple error toastr for validation errors
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };

                            // Show specific error message for mobile number duplicates
                            if (response.errors.mobile_no) {
                                toastr.error('Mobile number already exists!', 'Error');
                            } else if (response.errors.email) {
                                toastr.error('Email already exists!', 'Error');
                            } else {
                                toastr.error('Please fix the errors and try again.',
                                    'Error');
                            }
                        } else if (response.message) {
                            // Show generic error message
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };
                            toastr.error('Customer already exists!', 'Error');
                        }
                    } else {
                        // Handle any other status codes with clean message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Unable to create customer. Please try again.',
                            'Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error response:', xhr.status, xhr.responseJSON);
                    if (xhr.status === 400) {
                        // Handle validation errors
                        var response = xhr.responseJSON;
                        if (response && response.errors) {
                            // Display field-specific validation errors
                            $.each(response.errors, function(key, err_value) {
                                $('#' + key + '_error').html(Array.isArray(
                                    err_value) ? err_value[0] : err_value);
                            });

                            // Show clean error toastr for validation errors
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };

                            // Show specific error message for different validation errors
                            if (response.errors.mobile_no) {
                                toastr.error('Mobile number already exists!', 'Error');
                            } else if (response.errors.email) {
                                toastr.error('Email already exists!', 'Error');
                            } else {
                                toastr.error('Please fix the errors and try again.',
                                    'Error');
                            }
                        } else if (response && response.message) {
                            // Show simple generic error message
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };
                            toastr.error('Customer already exists!', 'Error');
                        }
                    } else if (xhr.status === 500) {
                        // Handle server errors with clean message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(
                            'Unable to create customer due to a server error. Please try again later.',
                            'Server Error');
                    } else {
                        // Handle other errors with simple message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Unable to create customer. Please try again.',
                            'Error');
                    }
                }
            });
        });


        // Delete Warranty
        $(document).on('click', '.delete_btn', function() {
            var id = $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete customer');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'customer-delete/' + id,
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

        // Navigate to Customer Ledger
        $(document).on('click', '.ledger_btn', function() {
            var customerId = $(this).val();
            // Navigate to customer ledger page with customer ID as parameter
            window.location.href = '/account-ledger?customer_id=' + customerId;
        });


        function fetchCustomerData() {
            return $.ajax({
                url: '/customer-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const customerSelect = $('#customer-id');
                    customerSelect.empty();

                    if (data && data.status === 200 && Array.isArray(data.message)) {
                        const sortedCustomers = data.message.sort((a, b) => {
                            if (a.first_name === 'Walk-in') return -1;
                            if (b.first_name === 'Walk-in') return 1;
                            return 0;
                        });

                        sortedCustomers.forEach(customer => {
                            const option = $('<option></option>');
                            option.val(customer.id);
                            if (customer.first_name === 'Walk-in') {
                                option.text(
                                    `${customer.first_name || ''} ${customer.last_name || ''}`
                                );
                                option.attr('data-customer-type',
                                    'retailer'); // Walk-in customer is always retailer
                            } else {
                                const customerType = customer.customer_type ?
                                    ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}` :
                                    '';
                                option.text(
                                    `${customer.first_name || ''} ${customer.last_name || ''}${customerType} (${customer.mobile_no || ''})`
                                );
                                option.attr('data-customer-type', customer.customer_type ||
                                    'retailer'); // Include customer type data attribute
                            }
                            option.data('due', customer.current_due ||
                                0); // Default due to 0
                            option.data('credit_limit', customer.credit_limit ||
                                0); // Add credit limit data
                            customerSelect.append(option);
                        });

                        // Always select Walking Customer by default
                        const walkingCustomer = sortedCustomers.find(customer => customer
                            .first_name === 'Walk-in');
                        if (walkingCustomer) {
                            customerSelect.val(walkingCustomer.id);
                            updateDueAmount(walkingCustomer.current_due || 0);
                            updateCreditLimit(walkingCustomer.credit_limit || 0, walkingCustomer
                                .current_due || 0, true); // true for isWalkIn
                        }
                    } else {
                        console.error('Failed to fetch customer data:', data ? data.message :
                            'No data received');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching customer data:', error);
                }
            });
        }


        function updateDueAmount(dueAmount) {
            // Ensure dueAmount is a valid number before calling toFixed
            dueAmount = isNaN(dueAmount) ? 0 : dueAmount;
            $('#total-due-amount').text(`Rs. ${dueAmount.toFixed(2)}`);
        }

        function updateCreditLimit(creditLimit, dueAmount = 0, isWalkIn = false) {
            const creditInfoContainer = $('.customer-credit-info');
            const creditLimitElement = $('#credit-limit-amount');
            const availableCreditElement = $('#available-credit-amount');

            if (isWalkIn) {
                // Hide entire credit info section for walk-in customers
                creditInfoContainer.hide();
            } else {
                // Show credit info section for other customers
                creditLimit = isNaN(creditLimit) ? 0 : parseFloat(creditLimit);
                dueAmount = isNaN(dueAmount) ? 0 : parseFloat(dueAmount);

                const remainingCredit = Math.max(creditLimit - dueAmount, 0);
                const isOverLimit = dueAmount > creditLimit;

                // Update credit limit display
                creditLimitElement.text(`Rs. ${creditLimit.toFixed(2)}`);

                // Update available credit display with appropriate styling
                if (isOverLimit) {
                    const overAmount = dueAmount - creditLimit;
                    availableCreditElement.html(
                        `<span class="text-danger">⚠️ Over by Rs. ${overAmount.toFixed(2)}</span>`);
                } else {
                    availableCreditElement.html(
                        `<span class="text-success">✓ Rs. ${remainingCredit.toFixed(2)}</span>`);
                }

                creditInfoContainer.show();
            }
        }

        $('#customer-id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const dueAmount = selectedOption.data('due') || 0;
            const creditLimit = selectedOption.data('credit_limit') || 0;
            const customerText = selectedOption.text().toLowerCase();
            const customerId = selectedOption.val();

            // Check if it's walk-in customer (ID = 1 or text contains 'walk-in')
            const isWalkIn = customerId === '1' || customerText.includes('walk-in');

            updateDueAmount(dueAmount);
            updateCreditLimit(creditLimit, dueAmount, isWalkIn);
        });



      
        window.customerFunctions = {
            fetchCustomerData: fetchCustomerData,
            // other functions NOT exposed unless added here
        };



    });
</script>
