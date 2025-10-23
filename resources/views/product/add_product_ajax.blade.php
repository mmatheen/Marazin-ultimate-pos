<script type="text/javascript">
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token
        var isSubmitting = false; // Flag to prevent double submission

        var addAndUpdateValidationOptions = {
            rules: {

                product_name: {
                    required: true
                , },

                unit_id: {
                    required: true
                , }
                , brand_id: {
                    required: true
                , }
                , main_category_id: {
                    required: true
                , }
                , sub_category_id: {
                    required: true
                , }
                , "locations[]": {
                    required: true
                , }

                , retail_price: {
                    required: true
                , }
                , whole_sale_price: {
                    required: true
                , }
                , special_price: {
                    required: true
                , }
                , original_price: {
                    required: true
                , },

            }
            , messages: {

                product_name: {
                    required: "Product Name is required"
                , }
                , unit_id: {
                    required: "Product Unit  is required"
                , }
                , brand_id: {
                    required: "Product Brand is required"
                , }
                , main_category_id: {
                    required: "Main Category  is required"
                , }
                , sub_category_id: {
                    required: "Sub Category  is required"
                , }
                , "locations[]": {
                    required: "Business Location  is required"
                , }

                , retail_price: {
                    required: "Retail Price is required"
                , }
                , whole_sale_price: {
                    required: "Whole Sale Price is required"
                , }
                , special_price: {
                    required: "Special Price is required"
                , }
                , original_price: {
                    required: "Cost Price is required"
                , },

            }
            , errorElement: 'span',
            errorPlacement: function(error, element) {

                // error message show after selectbox
                if (element.is("select")) {
                    error.addClass('text-danger');
                    // Insert the error after the closest parent div for better placement with select
                    error.insertAfter(element.closest('div'));
                }
                // error message show after checkbox

                else if (element.is(":checkbox")) {
                    error.addClass('text-danger');
                    // For checkboxes, place the error after the checkbox's parent container
                    error.insertAfter(element.closest('div').find('label').last());
                }
                // error message show after inputbox
                else {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                }
            },

            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            }
            , unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to forms
        $('#addForm').validate(addAndUpdateValidationOptions);

        // Add real-time validation feedback to re-enable buttons when errors are fixed
        function checkFormValidityAndEnableButtons() {
            // Custom validation check including locations array
            let isFormValid = true;
            
            // Check required fields manually
            const requiredFields = [
                'input[name="product_name"]',
                'select[name="unit_id"]', 
                'select[name="brand_id"]',
                'select[name="main_category_id"]',
                'select[name="sub_category_id"]',
                'input[name="retail_price"]',
                'input[name="whole_sale_price"]',
                'input[name="special_price"]',
                'input[name="original_price"]'
            ];
            
            // Check each required field
            requiredFields.forEach(function(selector) {
                const field = $(selector);
                if (field.length && (!field.val() || field.val() === '')) {
                    isFormValid = false;
                }
            });
            
            // Special check for locations array
            const selectedLocations = $('select[name="locations[]"]').val();
            if (!selectedLocations || selectedLocations.length === 0) {
                isFormValid = false;
            }
            
            const buttons = $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct');
            
            if (isFormValid && !isSubmitting) {
                buttons.prop('disabled', false);
                buttons.removeClass('btn-secondary').addClass('btn-primary');
            } else if (!isSubmitting) {
                // Don't disable if user is actively filling the form
                // buttons.prop('disabled', true);
            }
        }

        // Ensure buttons are enabled by default on page load
        const allButtons = $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct');
        allButtons.prop('disabled', false);
        
        // Monitor key fields for changes to re-enable buttons
        $('#addForm').on('change keyup blur input', 'input, select, textarea', function() {
            // Small delay to let validation complete
            setTimeout(checkFormValidityAndEnableButtons, 50);
        });

        // Monitor location field specifically with multiple event types
        $(document).on('change select2:select select2:unselect', 'select[name="locations[]"], #edit_location_id', function() {
            setTimeout(checkFormValidityAndEnableButtons, 50);
        });

        // Aggressive button re-enabling - check every time any field changes
        $('#addForm').on('input change keyup mouseup', function() {
            if (!isSubmitting) {
                setTimeout(function() {
                    allButtons.prop('disabled', false);
                }, 100);
            }
        });

        // Force enable buttons when clicking on any form field (in case they get stuck)
        $('#addForm').on('click focus', 'input, select, textarea', function() {
            if (!isSubmitting) {
                allButtons.prop('disabled', false);
            }
        });

        // add form and update validation rules code end

        // Real-time SKU uniqueness validation
        $('#edit_sku').on('blur change', function() {
            const sku = $(this).val().trim();
            const productId = $('#product_id').val(); // Get product ID if editing
            const errorSpan = $('#sku_error');
            
            // Only validate if SKU is provided
            if (sku === '') {
                errorSpan.html(''); // Clear error
                $(this).removeClass('is-invalidRed').removeClass('is-validGreen');
                return;
            }
            
            // Check for duplicate SKU via AJAX
            $.ajax({
                url: '/product/check-sku',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: {
                    sku: sku,
                    product_id: productId // Exclude current product if editing
                },
                dataType: 'json',
                success: function(response) {
                    if (response.exists) {
                        errorSpan.html('SKU already exists! Please use a different SKU.');
                        $('#edit_sku').addClass('is-invalidRed').removeClass('is-validGreen');
                    } else {
                        errorSpan.html('');
                        $('#edit_sku').removeClass('is-invalidRed').addClass('is-validGreen');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking SKU:', error);
                    errorSpan.html('Error validating SKU');
                }
            });
        });

        // show the image when add and edit
        $(".show-picture").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    // Update the src attribute of the img tag with the data URL of the selected image
                    $("#selectedImage").attr("src", e.target.result);
                    $("#selectedImage").show(); // Show the image
                };

                reader.readAsDataURL(input.files[0]);
            }
        });
        // show the image when add and edit image code end



        // sub category details show using main category id code start

        // Clear and reset the Sub Category dropdown Default
        $('#edit_sub_category_id').empty().append('<option value="" selected disabled>Select Sub Category</option>');

        // Get value of main_category_id
        $('#edit_main_category_id').change(function() {
            var main_category_id = $(this).val();

            // Clear and reset the sub category dropdown
            $('#edit_sub_category_id').empty().append('<option value="" selected disabled>Select Sub Category</option>');

            $.ajax({
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id
                , type: 'get'
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 200) {
                        // Populate the batch_no options
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append('<option value="' + subCategory.id + '">' + subCategory.subCategoryname + '</option>');
                        });

                    }
                }
                , error: function(xhr, status, error) {
                    // Handle AJAX error silently or show user-friendly message
                }
            });
        });
        // sub category details show using main category id code end



        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addForm')[0].reset();
            
            // Reset the validation messages and states
            $('#addForm').validate().resetForm();
            $('#addForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addForm').find('.is-validGreen').removeClass('is-validGreen');
            
            // Reset the image to the default
            $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png');
            
            // Reset all dropdown selections to default "Select" options with proper values
            resetAllDropdowns();
            
            // Clear any custom error messages
            $('.text-danger').html('');
            $('#sku_error').html('');
            
            // Clear all input fields explicitly
            $('input[type="text"], input[type="number"], textarea').val('');
            
            // Clear checkboxes and radio buttons
            $('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            
            // Re-enable all buttons
            $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct').prop('disabled', false);
            
            // Focus on first field for better UX
            setTimeout(function() {
                $('input[name="product_name"]').focus();
            }, 100);
        }

        // Function to reset all dropdowns to default state
        function resetAllDropdowns() {
            // List of all dropdown selectors
            const dropdowns = [
                '#edit_brand_id',
                '#edit_main_category_id', 
                '#edit_sub_category_id',
                '#edit_unit_id',
                '#edit_location_id'
            ];
            
            // Reset each dropdown
            dropdowns.forEach(function(selector) {
                const $dropdown = $(selector);
                
                // Check if it's a Select2 dropdown
                if ($dropdown.hasClass('select2-hidden-accessible')) {
                    // For Select2 dropdowns, clear selection and trigger change
                    $dropdown.val(null).trigger('change');
                } else {
                    // For regular dropdowns, reset to first option
                    $dropdown.prop('selectedIndex', 0);
                }
            });
            
            // Special handling for locations array (multiple Select2) - Enhanced Reset
            const $locationSelectors = [
                'select[name="locations[]"]', 
                '.multiple-location', 
                '#edit_location_id',
                '.location-select'
            ];
            
            $locationSelectors.forEach(function(selector) {
                const $locationSelect = $(selector);
                if ($locationSelect.length) {
                    // Clear the value first
                    $locationSelect.val(null);
                    
                    // If it's a Select2 dropdown, handle it specially
                    if ($locationSelect.hasClass('select2-hidden-accessible')) {
                        // Clear selection and trigger change
                        $locationSelect.trigger('change');
                        
                        // Force clear the visual display
                        const select2Container = $locationSelect.next('.select2-container');
                        if (select2Container.length) {
                            select2Container.find('.select2-selection__choice').remove();
                            select2Container.find('.select2-selection__rendered').html(
                                '<span class="select2-selection__placeholder">Select Location</span>'
                            );
                        }
                        
                        // Additional cleanup - destroy and reinitialize if needed
                        try {
                            $locationSelect.select2('destroy');
                            $locationSelect.select2({
                                placeholder: 'Select Location',
                                allowClear: true,
                                width: '100%'
                            });
                        } catch (e) {
                            // If Select2 not initialized, just clear normally
                            $locationSelect.prop('selectedIndex', 0);
                        }
                    } else {
                        // For regular dropdowns
                        $locationSelect.prop('selectedIndex', 0);
                    }
                }
            });
            
            // Trigger change event on main category to reset dependent sub-category
            setTimeout(function() {
                $('#edit_main_category_id').trigger('change');
            }, 50);
            
            // Additional aggressive cleanup for any remaining Select2 artifacts
            setTimeout(function() {
                $('.select2-selection__choice').remove();
                $('.select2-selection__rendered').each(function() {
                    const $this = $(this);
                    if ($this.find('.select2-selection__placeholder').length === 0) {
                        $this.html('<span class="select2-selection__placeholder">Select Location</span>');
                    }
                });
                
                // Force clear any visible selected items in location dropdowns
                $('select[name="locations[]"]').each(function() {
                    $(this).val(null).trigger('change');
                });
            }, 200);
        }

        // Submit the only product only
        $('#onlySaveProductButton').click(function(e) {
            e.preventDefault(); // Prevent default form submission

            // Prevent double submission
            if (isSubmitting) {
                toastr.warning('Product is already being saved, please wait...', 'Please Wait');
                return;
            }

            // Custom validation check
            let isValid = true;
            let errorMessages = [];
            
            // Check required fields
            if (!$('input[name="product_name"]').val()) {
                errorMessages.push('Product Name is required');
                isValid = false;
            }
            if (!$('select[name="unit_id"]').val()) {
                errorMessages.push('Unit is required');
                isValid = false;
            }
            if (!$('select[name="brand_id"]').val()) {
                errorMessages.push('Brand is required');
                isValid = false;
            }
            if (!$('select[name="main_category_id"]').val()) {
                errorMessages.push('Main Category is required');
                isValid = false;
            }
            if (!$('select[name="sub_category_id"]').val()) {
                errorMessages.push('Sub Category is required');
                isValid = false;
            }
            
            // Check locations array
            const locations = $('select[name="locations[]"]').val();
            if (!locations || locations.length === 0) {
                errorMessages.push('Business Location is required');
                isValid = false;
            }
            
            if (!$('input[name="retail_price"]').val()) {
                errorMessages.push('Retail Price is required');
                isValid = false;
            }
            if (!$('input[name="whole_sale_price"]').val()) {
                errorMessages.push('Whole Sale Price is required');
                isValid = false;
            }
            if (!$('input[name="special_price"]').val()) {
                errorMessages.push('Special Price is required');
                isValid = false;
            }
            if (!$('input[name="original_price"]').val()) {
                errorMessages.push('Cost Price is required');
                isValid = false;
            }
            
            if (!isValid) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error(errorMessages.join('<br>'), 'Please fill required fields', {
                    allowHtml: true
                });
                
                // NEVER disable buttons for validation errors - let user try again immediately
                $(this).prop('disabled', false);
                $('#SaveProductButtonAndAnother').prop('disabled', false);
                $('#openingStockAndProduct').prop('disabled', false);
                return; // Return if form is not valid - buttons remain enabled
            }

            // Only disable button and set flags AFTER validation passes
            isSubmitting = true; // Set flag to prevent double submission
            $(this).prop('disabled', true); // Disable button

            // Gather the form data
            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Log FormData to the console
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            $.ajax({
                url: '/product/store'

                , type: 'POST'
                , headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
                , data: formData
                , contentType: false
                , processData: false
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 400) {
                        // Clear previous error messages
                        $('.text-danger').html('');
                        
                        // Show validation errors
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                        
                        // Re-enable button and reset submitting flag for validation errors
                        isSubmitting = false;
                        $('#onlySaveProductButton').prop('disabled', false);
                        
                        document.getElementsByClassName('warningSound')[0].play(); // for sound
                        toastr.error('Please fix the validation errors and try again', 'Validation Error');
                    } else {
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                    }
                }
                , error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    var errorMsg = 'An error occurred while saving the product.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, 'Error');
                }
                , complete: function() {
                    // Only reset flags if not already handled in success (for validation errors)
                    if (isSubmitting) {
                        isSubmitting = false; // Reset flag
                        $('#onlySaveProductButton').prop('disabled', false); // Re-enable button
                    }
                }
            });
        });


      // Fetch main category, sub category, location, unit, brand details to select box code start
            $.ajax({
                url: '/api/initial-product-details', // Replace with your endpoint URL

                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {

                        const brandSelect = $('#edit_brand_id');
                        const mainCategorySelect = $('#edit_main_category_id');
                        const subCategorySelect = $('#edit_sub_category_id');
                        const unitSelect = $('#edit_unit_id');
                        const locationSelect = $('#edit_location_id');


                        brandSelect.empty(); // Clear existing options
                        mainCategorySelect.empty(); // Clear existing options
                        subCategorySelect.empty(); // Clear existing options
                        unitSelect.empty(); // Clear existing options
                        locationSelect.empty(); // Clear existing options

                        // Access brands and subcategories from response data
                        const brands = response.message.brands;
                        const mainCategories = response.message.mainCategories;
                        const subCategories = response.message.subCategories;
                        const units = response.message.units;
                        const locations = response.message.locations;

                        if ((brands && brands.length > 0) || (mainCategories && mainCategories.length > 0) || (subCategories && subCategories.length > 0) || (units && units.length > 0) || (locations && locations.length > 0)) {

                            // If there are brands or subcategories, add the default options and populate with data
                            brandSelect.append('<option value="" selected disabled>Select Product Brand</option>');
                            mainCategorySelect.append('<option value="" selected disabled>Select Main Category</option>');
                            subCategorySelect.append('<option value="" selected disabled>Select Sub Category</option>');
                            unitSelect.append('<option value="" selected disabled>Select Unit</option>');
                            locationSelect.append('<option value="" selected disabled>Select Location</option>');

                            brands.forEach(brand => {
                                brandSelect.append(`<option value="${brand.id}">${brand.name}</option>`);
                            });

                            mainCategories.forEach(mainCategory => {
                                mainCategorySelect.append(`<option value="${mainCategory.id}">${mainCategory.mainCategoryName}</option>`);
                            });

                            subCategories.forEach(subCategory => {
                                subCategorySelect.append(`<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`);
                            });

                            units.forEach(unit => {
                                unitSelect.append(`<option value="${unit.id}">${unit.name}</option>`);
                            });

                            locations.forEach(location => {
                                locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                            });
                        } else {
                            // If no records are found, show appropriate message
                            brandSelect.append('<option value="" selected disabled>No brands available</option>');
                            mainCategorySelect.append('<option value="" selected disabled>No main categories available</option>');
                            subCategorySelect.append('<option value="" selected disabled>No sub categories available</option>');
                            unitSelect.append('<option value="" selected disabled>No units available</option>');
                            locationSelect.append('<option value="" selected disabled>No locations available</option>');
                        }
                    }
                },
                error: function(error) {
                    // Handle error silently or show user-friendly message
                }
            });
          // Fetch main category, sub category, location, unit, brand details to select box code start



        function populateBrandDropdown(brands) {
            const brandSelect = $('#edit_brand_id');
            brandSelect.empty(); // Clear existing options

            if (brands.length > 0) {
                // If there are brands, add the default option and populate with brand options
                brandSelect.append('<option selected disabled>Product Brand</option>');
                brands.forEach(brand => {
                    brandSelect.append(`<option value="${brand.id}">${brand.name}</option>`);
                });
            } else {
                // If no brands are found, add a single disabled option indicating no records
                brandSelect.append('<option selected disabled>No brands available</option>');
            }
        }


        // Submit the Save And Another product only
        $('#SaveProductButtonAndAnother').click(function(e) {
            e.preventDefault(); // Prevent default form submission

            // Prevent double submission
            if (isSubmitting) {
                toastr.warning('Product is already being saved, please wait...', 'Please Wait');
                return;
            }

            // Custom validation check
            let isValid = true;
            let errorMessages = [];
            
            // Check required fields
            if (!$('input[name="product_name"]').val()) {
                errorMessages.push('Product Name is required');
                isValid = false;
            }
            if (!$('select[name="unit_id"]').val()) {
                errorMessages.push('Unit is required');
                isValid = false;
            }
            if (!$('select[name="brand_id"]').val()) {
                errorMessages.push('Brand is required');
                isValid = false;
            }
            if (!$('select[name="main_category_id"]').val()) {
                errorMessages.push('Main Category is required');
                isValid = false;
            }
            if (!$('select[name="sub_category_id"]').val()) {
                errorMessages.push('Sub Category is required');
                isValid = false;
            }
            
            // Check locations array
            const locations = $('select[name="locations[]"]').val();
            if (!locations || locations.length === 0) {
                errorMessages.push('Business Location is required');
                isValid = false;
            }
            
            if (!$('input[name="retail_price"]').val()) {
                errorMessages.push('Retail Price is required');
                isValid = false;
            }
            if (!$('input[name="whole_sale_price"]').val()) {
                errorMessages.push('Whole Sale Price is required');
                isValid = false;
            }
            if (!$('input[name="special_price"]').val()) {
                errorMessages.push('Special Price is required');
                isValid = false;
            }
            if (!$('input[name="original_price"]').val()) {
                errorMessages.push('Cost Price is required');
                isValid = false;
            }
            
            if (!isValid) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error(errorMessages.join('<br>'), 'Please fill required fields', {
                    allowHtml: true
                });
                
                // NEVER disable buttons for validation errors - let user try again immediately
                $('#onlySaveProductButton').prop('disabled', false);
                $(this).prop('disabled', false);
                $('#openingStockAndProduct').prop('disabled', false);
                return; // Return if form is not valid - buttons remain enabled
            }

            // Only disable button and set flags AFTER validation passes
            isSubmitting = true; // Set flag to prevent double submission
            $(this).prop('disabled', true); // Disable button

            // Gather the form data
            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Log FormData to the console
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            $.ajax({
                url: '/product/store'

                , type: 'POST'
                , headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
                , data: formData
                , contentType: false
                , processData: false
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 400) {
                        // Clear previous error messages
                        $('.text-danger').html('');
                        
                        // Show validation errors
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                        
                        // Re-enable button and reset submitting flag for validation errors
                        isSubmitting = false;
                        $('#SaveProductButtonAndAnother').prop('disabled', false);
                        
                        document.getElementsByClassName('warningSound')[0].play(); // for sound
                        toastr.error('Please fix the validation errors and try again', 'Validation Error');
                    } else {
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message + ' - Form cleared for next product', 'Added');
                        
                        // Extra aggressive form reset for "Save & Add Another"
                        setTimeout(function() {
                            resetFormAndValidation();
                            
                            // Force clear all visible values
                            $('input, textarea').val('');
                            $('select').prop('selectedIndex', 0);
                            
                            // Aggressive Select2 reset - Multiple attempts for stubborn dropdowns
                            $('.select2-hidden-accessible').each(function() {
                                const $this = $(this);
                                $this.val(null).trigger('change');
                                
                                // Extra cleanup for location dropdowns
                                if ($this.attr('name') === 'locations[]' || $this.hasClass('multiple-location')) {
                                    // Clear any selected values
                                    $this.empty();
                                    // Re-populate with original options if needed
                                    $this.append('<option value="" disabled>Select Location</option>');
                                }
                            });
                            
                            // Specifically target location dropdowns with multiple methods
                            $('select[name="locations[]"]').each(function() {
                                const $select = $(this);
                                $select.val([]).trigger('change'); // Empty array for multiple select
                                
                                // Force visual clear
                                const $container = $select.next('.select2-container');
                                if ($container.length) {
                                    $container.find('.select2-selection__choice').remove();
                                    $container.find('.select2-selection__rendered')
                                        .html('<span class="select2-selection__placeholder">Select Location</span>');
                                }
                            });
                            
                            // Reset validation states
                            $('.is-invalidRed, .is-validGreen').removeClass('is-invalidRed is-validGreen');
                            $('.text-danger').html('');
                            
                            // Additional cleanup after a longer delay
                            setTimeout(function() {
                                $('.select2-selection__choice').remove();
                                $('select[name="locations[]"]').val([]).trigger('change');
                            }, 300);
                            
                        }, 100);
                        
                        // Stay on same page to add another product (don't redirect)
                    }
                }
                , error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    var errorMsg = 'An error occurred while saving the product.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, 'Error');
                }
                , complete: function() {
                    // Only reset flags if not already handled in success (for validation errors)
                    if (isSubmitting) {
                        isSubmitting = false; // Reset flag
                        $('#SaveProductButtonAndAnother').prop('disabled', false); // Re-enable button
                    }
                }
            });
        });


        // Submit the Save & Add Opening Stock only
        $('#openingStockAndProduct').click(function(e) {
            e.preventDefault(); // Prevent default form submission

            // Prevent double submission
            if (isSubmitting) {
                toastr.warning('Product is already being saved, please wait...', 'Please Wait');
                return;
            }

            // Custom validation check
            let isValid = true;
            let errorMessages = [];
            
            // Check required fields
            if (!$('input[name="product_name"]').val()) {
                errorMessages.push('Product Name is required');
                isValid = false;
            }
            if (!$('select[name="unit_id"]').val()) {
                errorMessages.push('Unit is required');
                isValid = false;
            }
            if (!$('select[name="brand_id"]').val()) {
                errorMessages.push('Brand is required');
                isValid = false;
            }
            if (!$('select[name="main_category_id"]').val()) {
                errorMessages.push('Main Category is required');
                isValid = false;
            }
            if (!$('select[name="sub_category_id"]').val()) {
                errorMessages.push('Sub Category is required');
                isValid = false;
            }
            
            // Check locations array
            const locations = $('select[name="locations[]"]').val();
            if (!locations || locations.length === 0) {
                errorMessages.push('Business Location is required');
                isValid = false;
            }
            
            if (!$('input[name="retail_price"]').val()) {
                errorMessages.push('Retail Price is required');
                isValid = false;
            }
            if (!$('input[name="whole_sale_price"]').val()) {
                errorMessages.push('Whole Sale Price is required');
                isValid = false;
            }
            if (!$('input[name="special_price"]').val()) {
                errorMessages.push('Special Price is required');
                isValid = false;
            }
            if (!$('input[name="original_price"]').val()) {
                errorMessages.push('Cost Price is required');
                isValid = false;
            }
            
            if (!isValid) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error(errorMessages.join('<br>'), 'Please fill required fields', {
                    allowHtml: true
                });
                
                // NEVER disable buttons for validation errors - let user try again immediately
                $('#onlySaveProductButton').prop('disabled', false);
                $('#SaveProductButtonAndAnother').prop('disabled', false);
                $(this).prop('disabled', false);
                return; // Return if form is not valid - buttons remain enabled
            }

            // Only disable button and set flags AFTER validation passes
            isSubmitting = true; // Set flag to prevent double submission
            $(this).prop('disabled', true); // Disable button

            // Gather the form data
            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Log FormData to the console
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            $.ajax({
                url: '/product/store'
                , type: 'POST'
                , headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
                , data: formData
                , contentType: false
                , processData: false
                , dataType: 'json'
                , success: function(response) {
                    if (response.status == 400) {
                        // Clear previous error messages
                        $('.text-danger').html('');
                        
                        // Show validation errors
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                        
                        // Re-enable button and reset submitting flag for validation errors
                        isSubmitting = false;
                        $('#openingStockAndProduct').prop('disabled', false);
                        
                        document.getElementsByClassName('warningSound')[0].play(); // for sound
                        toastr.error('Please fix the validation errors and try again', 'Validation Error');
                    } else {
                        document.getElementsByClassName('successSound')[0].play(); // for sound
                        toastr.success(response.message, 'Added');
                        resetFormAndValidation();
                           // Show the opening stock modal and pass product ID
                           $('#addOpeningStockModal').modal('show');

                    }
                }
                , error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    var errorMsg = 'An error occurred while saving the product.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, 'Error');
                }
                , complete: function() {
                    // Only reset flags if not already handled in success (for validation errors)
                    if (isSubmitting) {
                        isSubmitting = false; // Reset flag
                        $('#openingStockAndProduct').prop('disabled', false); // Re-enable button
                    }
                }
            });
        });

    });

    // Final safety net - ensure buttons are never permanently disabled
    setInterval(function() {
        if (!isSubmitting) {
            const buttons = $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct');
            if (buttons.is(':disabled')) {
                buttons.prop('disabled', false);
            }
        }
    }, 2000); // Check every 2 seconds

</script>
