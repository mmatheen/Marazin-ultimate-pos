<style>
    /* Ensure proper button colors and styling */
    .btn-primary {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    .btn-secondary {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: #fff !important;
    }

    .btn-primary:hover {
        background-color: #0056b3 !important;
        border-color: #0056b3 !important;
        color: #fff !important;
    }

    .btn:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    /* Validation styling */
    .is-invalidRed {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .is-validGreen {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    /* Select2 styling improvements */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: #fff !important;
    }

    /* Batch Price Modal Styling */
    #batchPricesModal .form-control {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        color: #495057 !important;
        font-size: 14px !important;
        padding: 6px 12px !important;
    }

    #batchPricesModal .form-control:focus {
        background-color: #ffffff !important;
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        color: #495057 !important;
    }

    #batchPricesModal .form-control-sm {
        font-size: 13px !important;
        padding: 4px 8px !important;
        min-width: 80px !important;
    }

    #batchPricesModal input[type="number"] {
        text-align: right !important;
        font-weight: 500 !important;
    }

    #batchPricesModal .table td {
        vertical-align: middle !important;
        padding: 8px !important;
    }

    #batchPricesModal .card .form-control {
        margin-bottom: 8px !important;
    }

    /* Better contrast for readonly fields */
    #batchPricesModal .form-control[readonly] {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        border-color: #dee2e6 !important;
    }

    /* Enhanced styling for price inputs */
    #batchPricesModal .price-input {
        border-radius: 4px !important;
        transition: all 0.15s ease-in-out !important;
    }

    #batchPricesModal .price-input:hover {
        border-color: #007bff !important;
        box-shadow: 0 1px 3px rgba(0, 123, 255, 0.1) !important;
    }

    /* Highlight retail price as it's most important */
    #batchPricesModal input[name="retail_price"] {
        border-color: #007bff !important;
        font-weight: 600 !important;
    }

    #batchPricesModal input[name="retail_price"]:focus {
        border-color: #0056b3 !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.3) !important;
    }

    /* Better labels styling */
    #batchPricesModal .form-label {
        margin-bottom: 4px !important;
        color: #495057 !important;
    }

    /* Table header styling */
    #batchPricesModal .table th {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
        font-weight: 600 !important;
        color: #495057 !important;
        padding: 12px 8px !important;
    }

    /* Mobile card improvements */
    #batchPricesModal .card {
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    }

    #batchPricesModal .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #dee2e6 !important;
        padding: 12px 15px !important;
    }

    /* Price indicator legend */
    .price-legend {
        font-size: 11px;
        color: #6c757d;
        margin-top: 5px;
        text-align: right;
    }

    .price-legend .legend-item {
        display: inline-block;
        margin-left: 15px;
        padding: 2px 6px;
        background-color: #f8f9fa;
        border-radius: 3px;
    }
</style>

<script>
    // Global variables for initial product data - accessible across different script blocks
    window.initialProductDataLoaded = false;
    window.initialProductData = null;
    window.initialDataFetchTime = null;

    $(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content'); // For CSRF token
    let allProducts = [];
    let categoryMap = {};
    let brandMap = {};
    let locationMap = {};
    let subCategories = [];
    const discountMap = {};

    // Validation options
    var addAndUpdateValidationOptions = {
        rules: {
            product_name: {
                required: true
            },
            unit_id: {
                required: true
            },
            brand_id: {
                required: true
            },
            main_category_id: {
                required: true
            },
            'locations[]': {
                required: true
            },
            retail_price: {
                required: true
            },
            whole_sale_price: {
                required: true
            },
            original_price: {
                required: true
            },
            max_retail_price: {
                required: true
            },

        },
        messages: {
            product_name: {
                required: "Product Name is required"
            },
            unit_id: {
                required: "Product Unit is required"
            },
            brand_id: {
                required: "Product Brand is required"
            },
            main_category_id: {
                required: "Main Category is required"
            },
            'locations[]': {
                required: "Business Location is required"
            },
            retail_price: {
                required: "Retail Price is required"
            },
            whole_sale_price: {
                required: "Whole Sale Price is required"
            },
            original_price: {
                required: "Cost Price is required"
            },
            max_retail_price: {
                required: "Max Retail Price is required"
            },
        },
        errorElement: 'span',
        errorPlacement: function(error, element) {
            error.addClass('text-danger');
            if (element.is("select")) {
                error.insertAfter(element.closest('div'));
            } else if (element.is(":checkbox")) {
                error.insertAfter(element.closest('div').find('label').last());
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalidRed').removeClass('is-validGreen');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalidRed').addClass('is-validGreen');
        }
    };

    // Apply validation to forms
    $('#addForm').validate(addAndUpdateValidationOptions);

    // Initialize form validation and button states after setup - will be handled in main document ready

    // Global button selector for easier management
    const allButtons = $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct');

    // Improved form validation and button state management
    function validateFormAndUpdateButtons() {
        if (isSubmitting) return; // Don't change button state during submission

        // Check all required fields
        const requiredFields = [
            'input[name="product_name"]',
            'select[name="unit_id"]',
            'select[name="brand_id"]',
            'select[name="main_category_id"]',
            'input[name="retail_price"]',
            'input[name="whole_sale_price"]',
            'input[name="original_price"]',
            'input[name="max_retail_price"]'
        ];

        let isFormValid = true;
        let missingFields = [];

        // Check each required field
        requiredFields.forEach(function(selector) {
            const field = $(selector);
            const fieldName = field.attr('name') || selector;
            if (field.length && (!field.val() || field.val() === '')) {
                isFormValid = false;
                missingFields.push(fieldName);
            }
        });

        // Special check for locations array
        const selectedLocations = $('select[name="locations[]"]').val();
        if (!selectedLocations || selectedLocations.length === 0) {
            isFormValid = false;
            missingFields.push('locations');
        }

        // Update button state and styling
        if (isFormValid) {
            allButtons.prop('disabled', false);
            allButtons.removeClass('btn-secondary btn-outline-secondary').addClass('btn-primary');
        } else {
            allButtons.prop('disabled', true);
            allButtons.removeClass('btn-primary').addClass('btn-secondary');
        }

        return isFormValid;
    }

    // Initialize buttons as disabled on page load
    allButtons.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');

    // Monitor form changes with improved debouncing
    let validationTimeout;

    function scheduleValidation() {
        clearTimeout(validationTimeout);
        validationTimeout = setTimeout(validateFormAndUpdateButtons, 100);
    }

    // Attach validation to form events
    $('#addForm').on('input change keyup blur', 'input, select, textarea', scheduleValidation);

    // Special handling for Select2 dropdowns
    $(document).on('change select2:select select2:unselect select2:clear', 'select', scheduleValidation);

    // Initial validation check after page load
    $(document).ready(function() {
        // Only run on product add/edit modal
        if ($('#addForm').length || $('#edit_product_id').length) {
            setTimeout(validateFormAndUpdateButtons, 500);
        }
    });

    // =============================
    // DATA CACHING OPTIMIZATION
    // =============================
    // Prevents multiple redundant API calls to /initial-product-details
    // Previously called 3+ times on page load, now cached after first call

    // Simple cache object to store fetched data
    const dataCache = {};

    // Use global variables for initial product data

    // Function to clear cache (useful for refreshing data)
    function clearDataCache() {
        Object.keys(dataCache).forEach(key => delete dataCache[key]);
        categoriesAndBrandsLoaded = false; // Reset the loaded flag
        window.initialProductDataLoaded = false; // Reset initial data flag
        window.initialProductData = null; // Clear cached initial data
        console.log('🗑️ Data cache cleared and reload flags reset');
    }

    // Make cache clearing available globally for debugging
    window.clearProductDataCache = clearDataCache;

    // Helper function to populate a dropdown with improved caching and error handling
    function fetchData(url, successCallback, errorCallback) {
        // Check cache first
        if (dataCache[url]) {
            console.log('✅ Using cached data for:', url);
            successCallback(dataCache[url]);
            return;
        }

        console.log('🔄 Fetching data from:', url);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            cache: true, // Enable browser caching for better performance
            timeout: 15000, // Increased to 15 second timeout
            success: function(response) {
                // For /get-last-product endpoint, pass the response as-is (don't cache this)
                if (url.includes('/get-last-product')) {
                    successCallback(response);
                    return;
                }

                // Handle different response structures for other endpoints
                let processedResponse;
                if (response.status === true || response.status === 200) {
                    // If response has a 'data' property, use it; otherwise use 'message'
                    const data = response.data || response.message;
                    processedResponse = {
                        status: 200,
                        message: data
                    };
                } else {
                    processedResponse = response;
                }

                // Cache the processed response for future use
                dataCache[url] = processedResponse;
                console.log('✅ Cached data for:', url);

                successCallback(processedResponse);
            },
            error: errorCallback || function(xhr, status, error) {
                console.error('❌ Error fetching data from ' + url + ':', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });

                if (typeof toastr !== 'undefined') {
                    if (status === 'timeout') {
                        toastr.warning('Request timed out for ' + url.split('/').pop() + '. Using defaults...', 'Timeout Warning');
                    } else {
                        toastr.warning('Failed to load ' + url.split('/').pop() + '. Using defaults...', 'Network Warning');
                    }
                }

                // Provide fallback empty data to prevent blocking
                successCallback({
                    status: 200,
                    message: []
                });
            }
        });
    }

    function populateDropdown(selector, items, displayProperty) {
        const selectElement = $(selector).empty();

        // Add appropriate placeholder based on selector
        const placeholders = {
            '#edit_unit_id': 'Select Unit',
            '#edit_brand_id': 'Select Brand',
            '#edit_main_category_id': 'Select Main Category',
            '#edit_sub_category_id': 'Select Sub Category',
            '#edit_location_id': 'Select Location'
        };

        // Add placeholder option for all dropdowns
        const placeholder = placeholders[selector] || 'Select Option';
        selectElement.append(`<option value="">${placeholder}</option>`);

        // Add the actual data items
        items.forEach(item => {
            const option = new Option(item[displayProperty], item.id);
            if (item.selected && !selector.includes('locations[]')) {
                option.selected = true;
            }
            selectElement.append(option);
        });

        // For location dropdowns, handle Select2 initialization separately
        if (selector === '#edit_location_id' || selector.includes('locations[]')) {
            setTimeout(function() {
                if (!selectElement.hasClass('select2-hidden-accessible')) {
                    selectElement.select2({
                        placeholder: 'Select Location',
                        allowClear: true,
                        width: '100%',
                        multiple: selector.includes('locations[]')
                    });
                }
            }, 100);
        }
    }

    function populateInitialDropdowns(mainCategories, subCategories, brands, units, locations,
        autoSelectSingle, callback) {
        // Populate dropdowns with data
        populateDropdown('#edit_main_category_id', mainCategories, 'mainCategoryName');
        populateDropdown('#edit_sub_category_id', subCategories, 'subCategoryname');
        populateDropdown('#edit_brand_id', brands, 'name');
        populateDropdown('#edit_unit_id', units, 'name');

        // Handle location dropdown - add placeholder and options
        const $locationSelect = $('#edit_location_id, select[name="locations[]"]');
        $locationSelect.empty(); // Clear first
        
        // Add placeholder option for better Select2 compatibility
        $locationSelect.append('<option value="">Select Location</option>');

        // Add location options
        locations.forEach(location => {
            const option = new Option(location.name, location.id);
            $locationSelect.append(option);
        });

        // Initialize or reinitialize Select2 for location dropdown
        setTimeout(function() {
            $locationSelect.each(function() {
                const $this = $(this);
                // Destroy existing Select2 if any
                if ($this.hasClass('select2-hidden-accessible')) {
                    $this.select2('destroy');
                }
                // Initialize Select2 with proper configuration
                $this.select2({
                    placeholder: 'Select Location',
                    allowClear: true,
                    width: '100%',
                    multiple: $this.attr('name') === 'locations[]' || $this.hasClass('multiple-location')
                });
            });
        }, 100);

        // Auto-select location only in edit mode
        if (locations.length === 1 && locations[0].selected && $('#product_id').val()) {
            setTimeout(function() {
                $('#edit_location_id').val([locations[0].id]).trigger('change');
                validateFormAndUpdateButtons();
            }, 200);
        }

        // Populate location filter dropdown (for product list page)
        populateLocationFilterDropdown(locations, autoSelectSingle);

        // Validate form after initialization
        setTimeout(function() {
            validateFormAndUpdateButtons();
            if (callback) callback();
        }, 300);
    }

    // New function to handle location filter dropdown with "All Location" option
    function populateLocationFilterDropdown(locations, autoSelectSingle = false) {
        const locationFilter = $('#locationFilter');
        locationFilter.empty();

        // Add "All Location" option as default
        locationFilter.append('<option value="">All Location</option>');

        // Add individual locations
        locations.forEach(location => {
            const option = $(`<option value="${location.id}">${location.name}</option>`);
            locationFilter.append(option);
        });

        // Auto-select single location if user has access to only one location
        if (autoSelectSingle && locations.length === 1) {
            locationFilter.val(locations[0].id);
        }
    }

    function fetchInitialDropdowns(callback) {
        // Check if data is already loaded and cached
        if (window.initialProductDataLoaded && window.initialProductData) {
            const cacheAge = Date.now() - window.initialDataFetchTime;
            console.log(`✅ Using cached initial product data (${cacheAge}ms old) - no API call needed`);
            const data = window.initialProductData;
            populateInitialDropdowns(
                data.mainCategories,
                data.subCategories,
                data.brands,
                data.units,
                data.locations,
                data.autoSelectSingle,
                callback
            );
            return;
        }

        console.log('🔄 Fetching initial product details from API...');
        fetchData('/initial-product-details', function(response) {
            if (response.status === 200) {
                // Cache the data globally to prevent future API calls
                window.initialProductData = {
                    brands: response.message.brands,
                    mainCategories: response.message.mainCategories,
                    subCategories: response.message.subCategories,
                    units: response.message.units,
                    locations: response.message.locations,
                    autoSelectSingle: response.message.auto_select_single_location
                };

                // Store subcategories globally for compatibility
                subCategories = window.initialProductData.subCategories;

                // Mark as loaded with timestamp
                window.initialProductDataLoaded = true;
                window.initialDataFetchTime = Date.now();

                console.log('✅ Initial product data fetched and cached successfully');

                populateInitialDropdowns(
                    window.initialProductData.mainCategories,
                    window.initialProductData.subCategories,
                    window.initialProductData.brands,
                    window.initialProductData.units,
                    window.initialProductData.locations,
                    window.initialProductData.autoSelectSingle,
                    callback
                );
            } else {
                console.error('❌ Failed to load initial product details');
                if (callback) callback();
            }
        });
    }

    function populateSubCategories(selectedMainCategoryId) {
        const subCategorySelect = $('#edit_sub_category_id').empty();
        subCategorySelect.append('<option selected disabled>Sub Category</option>');

        subCategories
            .filter(subCategory => subCategory.main_category_id == selectedMainCategoryId)
            .forEach(subCategory => {
                subCategorySelect.append(new Option(subCategory.subCategoryname, subCategory.id));
            });

        subCategorySelect.trigger('change');
    }

    $('#edit_main_category_id').change(function() {
        const selectedMainCategoryId = $(this).val();
        populateSubCategories(selectedMainCategoryId);
    });

    function populateProductDetails(product, mainCategories, subCategories, brands, units, locations) {
        $('#product_id').val(product.id);
        $('#edit_product_name').val(product.product_name);
        $('#summernote').summernote('code', product.description || ''); // Use Summernote API to set content
        $('#edit_sku').val(product.sku ?? '');
        $('#edit_pax').val(product.pax || 0);
        $('#edit_original_price').val(product.original_price || 0);
        $('#edit_retail_price').val(product.retail_price || 0);
        $('#edit_whole_sale_price').val(product.whole_sale_price || 0);
        $('#edit_special_price').val(product.special_price || 0);
        $('#edit_max_retail_price').val(product.max_retail_price || 0);
        $('#edit_alert_quantity').val(product.alert_quantity || 0);
        $('#edit_product_type').val(product.product_type || "").trigger('change');
        $('#Enable_Product_description').prop('checked', product.is_imei_or_serial_no === 1);
        $('#Not_for_selling').prop('checked', product.is_for_selling === "1");

        if (product.product_image) {
            const imagePath = `/assets/images/${product.product_image}`;
            $('#product-selectedImage').attr('src', imagePath).show();
        }

        // Change button text for edit mode
        updateButtonsForEditMode();

        // Populate initial dropdowns with callback to set selected values
        populateInitialDropdowns(mainCategories, subCategories, brands, units, locations, false,
            function() {
                $('#edit_main_category_id').val(product.main_category_id).trigger('change');

                setTimeout(() => {
                    populateSubCategories(product.main_category_id);
                    $('#edit_sub_category_id').val(product.sub_category_id).trigger('change');
                }, 300);

                $('#edit_brand_id').val(product.brand_id).trigger('change');
                $('#edit_unit_id').val(product.unit_id).trigger('change');
                const locationIds = product.locations.map(location => location.id);
                $('#edit_location_id').val(locationIds).trigger('change');
            });
    }

    // Function to update button text for edit mode
    function updateButtonsForEditMode() {
        // Change button text for edit mode
        $('#onlySaveProductButton').text('Update');
        $('#SaveProductButtonAndAnother').text('Update & Add Another');
        $('#openingStockAndProduct').text('Update & Opening Stock');

        // Update any button titles/tooltips
        $('#onlySaveProductButton').attr('title', 'Update the product');
        $('#SaveProductButtonAndAnother').attr('title', 'Update product and add another');
        $('#openingStockAndProduct').attr('title', 'Update product and manage opening stock');
    }

    // Function to reset buttons for add mode
    function resetButtonsForAddMode() {
        // Reset button text for add mode
        $('#onlySaveProductButton').text('Save');
        $('#SaveProductButtonAndAnother').text('Save & Add Another');
        $('#openingStockAndProduct').text('Save & Opening Stock');

        // Reset button titles/tooltips
        $('#onlySaveProductButton').attr('title', 'Save the product');
        $('#SaveProductButtonAndAnother').attr('title', 'Save product and add another');
        $('#openingStockAndProduct').attr('title', 'Save product and manage opening stock');
    }






    // Collect selected product IDs
    let selectedProductIds = [];

    function toggleAddLocationButton() {
        if (selectedProductIds.length > 0) {
            $('#addLocationButton').show();
        } else {
            $('#addLocationButton').hide();
        }
    }

    // Fetch and populate the location dropdown
    function populateLocationDropdown() {
        fetchData('/location-get-all', function(response) {
            if (response.status === 200) {
                const locations = response
                    .message; // Ensure this is correct according to your API response
                const locationSelect = $('#locations');
                locationSelect.empty();

                // Get current locations for selected products
                getCurrentProductLocations(function(currentLocationIds) {
                    locations.forEach(function(location) {
                        const option = new Option(location.name, location.id);
                        // Pre-select if this location is currently assigned to any selected product
                        if (currentLocationIds.includes(location.id)) {
                            option.selected = true;
                        }
                        locationSelect.append(option);
                    });

                    // Trigger change to update the select2 display
                    locationSelect.trigger('change');
                });
            } else {
                console.error('Failed to fetch locations.');
            }
        });
    }

    // Function to get current locations for selected products
    function getCurrentProductLocations(callback) {
        if (selectedProductIds.length === 0) {
            callback([]);
            return;
        }

        $.ajax({
            url: '/get-product-locations',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                product_ids: selectedProductIds
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Get unique location IDs from all selected products
                    const locationIds = [];
                    response.data.forEach(product => {
                        product.locations.forEach(location => {
                            if (!locationIds.includes(location.id)) {
                                locationIds.push(location.id);
                            }
                        });
                    });
                    callback(locationIds);
                } else {
                    console.error('Failed to fetch product locations');
                    callback([]);
                }
            },
            error: function(xhr) {
                console.error('Error fetching product locations:', xhr);
                callback([]);
            }
        });
    }

    $('#addLocationModal').on('show.bs.modal', function() {
        populateLocationDropdown();
    });

    $('#saveLocationsButton').on('click', function() {
        const selectedLocations = $('#locations').val();
        if (selectedLocations && selectedProductIds.length > 0) {
            $.ajax({
                url: '/save-changes',

                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    product_ids: selectedProductIds,
                    location_ids: selectedLocations
                },
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success(response.message, 'Success');
                        $('#addLocationModal').modal('hide');
                        fetchProductData();
                    } else {
                        toastr.error(response.message || 'Failed to save changes.',
                            'Error');
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred while saving changes.', 'Error');
                }
            });
        } else {
            toastr.warning('Please select at least one product and one location.',
                'Selection Required');
        }
    });


    // Toggle both buttons when products are selected
    function toggleActionButtons() {
        if (selectedProductIds.length > 0) {
            $('#addLocationButton').show();
            $('#applyDiscountButton').show();
        } else {
            $('#addLocationButton').hide();
            $('#applyDiscountButton').hide();
        }
    }

    // Variable to store selected products price data
    let selectedProductsPriceData = [];

    // Function to fetch and display selected products price range
    function loadSelectedProductsPrices() {
        if (selectedProductIds.length === 0) {
            $('#selected-products-info').hide();
            $('#discount-validation-warning').hide();
            selectedProductsPriceData = [];
            return;
        }

        // Show loading state
        $('#selected-products-count').html('<i class="fas fa-spinner fa-spin"></i> Loading prices...');

        $.ajax({
            url: '/discounts/validate-prices',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                product_ids: selectedProductIds
            },
            success: function(response) {
                if (response.valid && response.products) {
                    selectedProductsPriceData = response.products;

                    // Calculate overall min and max prices from batches
                    let allMinPrices = [];
                    let allMaxPrices = [];
                    let totalBatches = 0;
                    let totalStock = 0;

                    response.products.forEach(function(product) {
                        if (product.min_price > 0) allMinPrices.push(product.min_price);
                        if (product.max_price > 0) allMaxPrices.push(product.max_price);
                        totalBatches += product.batch_count || 0;
                        totalStock += product.total_stock || 0;
                    });

                    const overallMinPrice = allMinPrices.length > 0 ? Math.min(...allMinPrices) : 0;
                    const overallMaxPrice = allMaxPrices.length > 0 ? Math.max(...allMaxPrices) : 0;

                    let priceInfo = '<div><strong><i class="fas fa-check-circle text-success"></i> ' + response.products.length + ' product(s) selected</strong></div>';
                    priceInfo += '<small class="text-muted">Total batches: ' + totalBatches + ', Total stock: ' + totalStock + '</small><br>';

                    if (overallMinPrice > 0 || overallMaxPrice > 0) {
                        if (overallMinPrice === overallMaxPrice) {
                            priceInfo += '<small><strong>Batch retail price:</strong> Rs. ' + overallMaxPrice.toFixed(2) + '</small>';
                        } else {
                            priceInfo += '<small><strong>Batch retail price range:</strong> Rs. ' + overallMinPrice.toFixed(2) + ' - Rs. ' + overallMaxPrice.toFixed(2) + '</small>';
                        }
                    }

                    $('#selected-products-count').html(priceInfo);
                    $('#selected-products-info').show();

                    // Validate discount if amount is already entered
                    validateDiscountAmount();
                }
            },
            error: function(xhr) {
                console.error('Error loading product prices:', xhr);
                $('#selected-products-count').html('<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error loading prices</span>');
                toastr.warning('Could not load product prices. Please proceed with caution.', 'Warning');
            }
        });
    }

    // Function to validate discount amount against product prices
    function validateDiscountAmount() {
        const discountType = $('#discountType').val();
        const discountAmount = parseFloat($('#discountAmount').val());

        if (!discountType || !discountAmount || selectedProductsPriceData.length === 0) {
            $('#discount-validation-warning').hide();
            return;
        }

        let hasIssue = false;
        let issueMessage = '';

        if (discountType === 'percentage') {
            if (discountAmount > 100) {
                hasIssue = true;
                issueMessage = 'Percentage discount cannot exceed 100%';
            } else if (discountAmount === 100) {
                issueMessage = 'Warning: 100% discount will make products free';
                hasIssue = true;
            }
        } else if (discountType === 'fixed') {
            // Check if fixed discount exceeds any product's price
            const allMaxPrices = selectedProductsPriceData
                .map(p => p.max_price)
                .filter(price => price > 0);

            if (allMaxPrices.length > 0) {
                const minProductPrice = Math.min(...allMaxPrices);

                if (discountAmount >= minProductPrice) {
                    hasIssue = true;
                    issueMessage = 'Fixed discount (Rs. ' + discountAmount.toFixed(2) + ') exceeds the lowest product price (Rs. ' + minProductPrice.toFixed(2) + ')';
                }
            }
        }

        if (hasIssue) {
            $('#validation-message').text(issueMessage);
            $('#discount-validation-warning').show();
        } else {
            $('#discount-validation-warning').hide();
        }
    }

    // Update selected products count and prices when modal opens
    $('#applyDiscountModal').on('show.bs.modal', function() {
        loadSelectedProductsPrices();
    });

    // Validate discount when type or amount changes
    $('#discountType, #discountAmount').on('change input', function() {
        validateDiscountAmount();
    });

    $('#saveDiscountButton').on('click', function() {
        const discountData = {
            name: $('#discountName').val(),
            description: $('#discountDescription').val(),
            type: $('#discountType').val(),
            amount: $('#discountAmount').val(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val() || null,
            is_active: $('#isActive').is(':checked') ? 1 : 0,
            product_ids: selectedProductIds
        };

        // Validate required fields
        if (!discountData.name || !discountData.type || !discountData.amount || !discountData.start_date) {
            toastr.error('Please fill all required fields', 'Error');
            return;
        }

        // Validate discount amount
        if (discountData.type === 'percentage' && (parseFloat(discountData.amount) < 0 || parseFloat(discountData.amount) > 100)) {
            toastr.error('Percentage discount must be between 0 and 100', 'Validation Error');
            return;
        }

        if (parseFloat(discountData.amount) <= 0) {
            toastr.error('Discount amount must be greater than 0', 'Validation Error');
            return;
        }

        $.ajax({
            url: '/apply-discount',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: discountData,
            success: function(response) {
                if (response.status === 'success') {
                    toastr.success(response.message, 'Success');
                    $('#applyDiscountModal').modal('hide');
                    $('#discountForm')[0].reset();
                    selectedProductIds = [];
                    selectedProductsPriceData = [];
                    fetchProductData();
                } else {
                    toastr.error(response.message || 'Failed to apply discount', 'Error');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(error => {
                        toastr.error(error[0], 'Validation Error');
                    });
                } else {
                    toastr.error('An error occurred while applying discount', 'Error');
                }
                console.error(xhr.responseText);
            }
        });
    });



    // Toggle product status (activate/deactivate)
    function toggleProductStatus(productId, currentStatus) {
        const action = currentStatus ? 'deactivate' : 'activate';
        const actionText = currentStatus ? 'Deactivate' : 'Activate';
        const actionCapitalized = action.charAt(0).toUpperCase() + action.slice(1);

        // Use SweetAlert for confirmation
        swal({
            title: "Are you sure?",
            text: `Do you want to ${action} this product?`,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: currentStatus ? "#DD6B55" : "#5cb85c",
            confirmButtonText: `Yes, ${action} it!`,
            cancelButtonText: "No, cancel",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function(isConfirm) {
            if (isConfirm) {
                performStatusToggle(productId, action, actionCapitalized);
            }
        });
    }

    // Separate function to perform the actual AJAX call
    function performStatusToggle(productId, action, actionCapitalized) {
        $.ajax({
            url: '/toggle-product-status/' + productId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.status === 200) {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload the DataTable
                    if ($.fn.DataTable.isDataTable('#productTable')) {
                        $('#productTable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    swal("Error!", response.message || 'Failed to update status', "error");
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                let errorMessage = 'Failed to update product status';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                swal("Error!", errorMessage, "error");
            }
        });
    }


    // Track if categories/brands/locations have been loaded to prevent duplicate requests
    let categoriesAndBrandsLoaded = false;

    // Fetch and cache category, brand, location data with timeout protection
    function fetchCategoriesAndBrands(callback) {
        // Skip if already loaded
        if (categoriesAndBrandsLoaded) {
            console.log('🔄 Categories/brands/locations already loaded, skipping...');
            callback();
            return;
        }

        console.log('🔄 Fetching categories, brands, and locations...');

        let loaded = 0;
        const totalRequests = 3;
        let hasFinished = false;

        // Set a maximum timeout to prevent indefinite waiting
        const maxTimeout = setTimeout(function() {
            if (!hasFinished) {
                console.warn('⚠️ fetchCategoriesAndBrands timed out, proceeding with available data');
                hasFinished = true;
                categoriesAndBrandsLoaded = true; // Mark as loaded even on timeout
                callback();
            }
        }, 20000); // 20 seconds max wait

        function checkComplete() {
            if (++loaded === totalRequests && !hasFinished) {
                hasFinished = true;
                categoriesAndBrandsLoaded = true; // Mark as successfully loaded
                clearTimeout(maxTimeout);
                console.log('✅ All category/brand/location data loaded');
                callback();
            }
        }

        fetchData('/main-category-get-all', function(response) {
            try {
                if (Array.isArray(response.message)) {
                    response.message.forEach(c => {
                        categoryMap[c.id] = c.mainCategoryName;
                    });
                    console.log('✅ Loaded', response.message.length, 'main categories');
                }
            } catch (e) {
                console.error('Error processing main categories:', e);
            }
            checkComplete();
        });

        fetchData('/brand-get-all', function(response) {
            try {
                if (Array.isArray(response.message)) {
                    response.message.forEach(b => {
                        brandMap[b.id] = b.name;
                    });
                    console.log('✅ Loaded', response.message.length, 'brands');
                }
            } catch (e) {
                console.error('Error processing brands:', e);
            }
            checkComplete();
        });

        fetchData('/location-get-all', function(response) {
            try {
                if (Array.isArray(response.message)) {
                    response.message.forEach(l => {
                        locationMap[l.id] = l.name;
                    });
                    console.log('✅ Loaded', response.message.length, 'locations');
                }
            } catch (e) {
                console.error('Error processing locations:', e);
            }
            checkComplete();
        });
    }

    // Populate filter dropdowns - products from current page, but categories/brands from all available data
    function populateProductFilter(pageData) {
        const productNameFilter = $('#productNameFilter');
        const categoryFilter = $('#categoryFilter');
        const brandFilter = $('#brandFilter');
        const locationFilter = $('#locationFilter');

        // Only get product names from current page data
        const productNames = [...new Set(pageData.map(item => item.product.product_name))];

        // Clear and populate product names from current page
        productNameFilter.empty().append('<option value="">Select Product</option>');
        productNames.forEach(name => {
            productNameFilter.append(`<option value="${name}">${name}</option>`);
        });

        // Categories and brands will be populated from all available data in populateAllFilterOptions
        // Don't populate them here to avoid limiting to current page data
    }

    // Populate category and brand filters with ALL available options (not limited to current page)
    function populateAllFilterOptions() {
        const categoryFilter = $('#categoryFilter');
        const brandFilter = $('#brandFilter');

        // Clear existing options
        categoryFilter.empty().append('<option value="">Select Category</option>');
        brandFilter.empty().append('<option value="">Select Brand</option>');

        // Populate with ALL categories from categoryMap (loaded from initialProductDetails)
        if (typeof categoryMap === 'object' && categoryMap !== null) {
            Object.entries(categoryMap).forEach(([categoryId, categoryName]) => {
                if (categoryId && categoryName) {
                    categoryFilter.append(`<option value="${categoryId}">${categoryName}</option>`);
                }
            });
            console.log('✅ Populated', Object.keys(categoryMap).length, 'categories in filter');
        }

        // Populate with ALL brands from brandMap (loaded from initialProductDetails)
        if (typeof brandMap === 'object' && brandMap !== null) {
            Object.entries(brandMap).forEach(([brandId, brandName]) => {
                if (brandId && brandName) {
                    brandFilter.append(`<option value="${brandId}">${brandName}</option>`);
                }
            });
            console.log('✅ Populated', Object.keys(brandMap).length, 'brands in filter');
        }
    }

    function buildActionsDropdown(row) {
        // Ensure we have product data
        if (!row.product) {
            return '<div class="text-danger">Error: No product data</div>';
        }

        // Determine button text and icon based on status - access from nested product object
        const isActive = row.product.is_active === 1 || row.product.is_active === true || row.product
            .is_active === "1";

        // Pass the actual boolean status to the click handler
        const statusButton = isActive ?
            `<li><a class="dropdown-item btn-toggle-status" href="#" data-product-id="${row.product.id}" data-status="true"><i class="fas fa-times-circle text-warning"></i> Deactivate</a></li>` :
            `<li><a class="dropdown-item btn-toggle-status" href="#" data-product-id="${row.product.id}" data-status="false"><i class="fas fa-check-circle text-success"></i> Activate</a></li>`;

        return `
                <div class="dropdown">
                    <button class="btn btn-outline-info btn-sm dropdown-toggle action-button" type="button" id="actionsDropdown-${row.product.id}" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="actionsDropdown-${row.product.id}">
                        <li><a class="dropdown-item view-product" href="#" data-product-id="${row.product.id}"><i class="fas fa-eye"></i> View</a></li>
                        <li><a class="dropdown-item" href="/edit-product/${row.product.id}"><i class="fas fa-edit"></i> Edit</a></li>
                        ${window.canEditBatchPrices ?
                            `<li><a class="dropdown-item edit-batch-prices" href="#" data-product-id="${row.product.id}"><i class="fas fa-dollar-sign"></i> Edit Batch Prices</a></li>`
                            : ''}
                        ${statusButton}
                        <li><a class="dropdown-item" href="/edit-opening-stock/${row.product.id}"><i class="fas fa-plus"></i> Add or Edit Opening Stock</a></li>
                        <li><a class="dropdown-item" href="/products/stock-history/${row.product.id}"><i class="fas fa-history"></i> Product Stock History</a></li>
                        ${row.product.is_imei_or_serial_no === 1
                            ? `<li><a class="dropdown-item show-imei-modal" href="#" data-product-id="${row.product.id}"><i class="fas fa-barcode"></i> IMEI Entry</a></li>`
                            : ''}
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger delete-product-dropdown" href="#" data-product-id="${row.product.id}"><i class="fas fa-trash"></i> Delete Product</a></li>
                    </ul>
                </div>
            `;
    }

    function fetchProductData() {
        if ($.fn.DataTable.isDataTable('#productTable')) {
            $('#productTable').DataTable().destroy();
        }
        $('#productTable tbody').empty();

        $('#productTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true, // Improve performance for large datasets
            stateSave: true, // Save table state (pagination, search, etc.)
            ajax: {
                url: '/products/stocks',
                type: 'GET',
                dataType: 'json', // Ensure jQuery expects JSON
                cache: true, // Enable caching
                timeout: 60000, // 60 second timeout for large datasets
                data: function(d) {
                    // DataTables sends search and paging params in 'd'
                    const locationId = $('#locationFilter').val();

                    // Warn user if trying to load all records
                    if (d.length === -1) {
                        console.warn('⚠️ Loading all records - this may take a while...');
                        toastr.info('Loading all products... This may take a moment.', 'Please Wait', {
                            timeOut: 3000
                        });
                    }

                    const requestData = {
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        // DataTables global search
                        'search[value]': d.search.value,
                        // DataTables ordering
                        'order[0][column]': d.order && d.order.length ? d.order[0].column :
                            0,
                        'order[0][dir]': d.order && d.order.length ? d.order[0].dir : 'asc',
                        // DataTables columns (for ordering)
                        'columns': d.columns,
                        // Custom filters
                        product_name: $('#productNameFilter').val(),
                        main_category_id: $('#categoryFilter').val(),
                        brand_id: $('#brandFilter').val(),
                        location_id: locationId,
                        stock_status: $('#stockStatusFilter').val(),
                        // Show all products (active and inactive) in product list
                        show_all: true
                    };
                    // Debug: Log the location filter value being sent
                    if (locationId) {
                        console.log('🔍 Location filter applied:', locationId, $('#locationFilter option:selected').text());
                    } else {
                        console.log('🔍 No location filter - showing all locations');
                    }
                    return requestData;
                },
                dataSrc: function(response) {

                    // DataTables expects an object with at least 'data' property as array
                    if (!response || typeof response !== 'object') {
                        console.error('Invalid JSON response from server');
                        return [];
                    }

                    // If your backend returns {status: 200, data: [...]}, convert to DataTables format
                    if (response.status === 200 && Array.isArray(response.data)) {
                        // Update product name filter with current page data
                        // But categories and brands will be populated separately with ALL available data
                        if (response.draw === 1) {
                            populateProductFilter(response.data);
                            // Also populate category/brand filters with ALL available options
                            populateAllFilterOptions();
                        }
                        return response.data;
                    }

                    // If backend returns DataTables format, just return response.data
                    if (Array.isArray(response.data)) {
                        return response.data;
                    }

                    // If backend returns error, show message
                    if (response.message) {
                        toastr.error(response.message, 'Error');
                    }

                    return [];
                },
                error: function(xhr, status, error) {
                    console.error('DataTable AJAX error details:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Status Code:', xhr.status);
                    console.error('Response Text:', xhr.responseText);

                    // Show specific error messages based on error type
                    if (status === 'timeout') {
                        toastr.error(
                            'Request timed out. Try using filters or smaller page sizes instead of "All".',
                            'Timeout Error', {
                                timeOut: 5000
                            });
                    } else if (xhr.status === 500) {
                        toastr.error(
                            'Server error. The dataset may be too large. Try using filters or pagination.',
                            'Server Error', {
                                timeOut: 5000
                            });
                    } else if (xhr.status === 0) {
                        toastr.error(
                            'Network error. Please check your connection and try again.',
                            'Connection Error');
                    } else {
                        toastr.error(
                            'Failed to load product data. Try using smaller page sizes or apply filters.',
                            'Error', {
                                timeOut: 5000
                            });
                    }
                }
            },
            columns: [{
                    data: null,
                    render: function(data, type, row) {
                        return `<input type="checkbox" class="product-checkbox" data-product-id="${row.product.id}" style="width: 16px; height: 16px;">`;
                    },
                    orderable: false
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return buildActionsDropdown(row);
                    },
                    orderable: false
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        const productImage = row.product.product_image;
                        const imagePath = productImage && productImage !== 'null' &&
                            productImage !== null &&
                            productImage !== '' ?
                            `/assets/images/${productImage}` :
                            '/assets/images/No Product Image Available.png';
                        return `<img src="${imagePath}" alt="Product Image" style="width: 50px; height: 50px;">`;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return row.product.product_name;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return row.product.sku;
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        let locationDisplay = [];

                        if (row.batches && row.batches.length > 0) {
                            // Collect locations with stock from batches (already filtered by backend location scope)
                            const locationStocks = {};

                            row.batches.forEach(batch => {
                                if (batch.location_batches) {
                                    batch.location_batches.forEach(lb => {
                                        if (lb.quantity > 0) {
                                            if (!locationStocks[lb
                                                    .location_id]) {
                                                locationStocks[lb
                                                    .location_id] = {
                                                    name: lb
                                                        .location_name,
                                                    qty: 0
                                                };
                                            }
                                            locationStocks[lb.location_id]
                                                .qty += parseFloat(lb
                                                    .quantity);
                                        }
                                    });
                                }
                            });

                            // Build display array with quantities
                            Object.values(locationStocks).forEach(location => {
                                if (location.qty > 0) {
                                    locationDisplay.push(
                                        `${location.name} (${location.qty})`);
                                }
                            });
                        }

                        // Always show all assigned locations, regardless of stock
                        if (row.locations && row.locations.length > 0) {
                            // Get location names that are not already in locationDisplay
                            const existingLocationNames = locationDisplay.map(display => {
                                const match = display.match(/^(.+?)\s*\(/);
                                return match ? match[1] : display;
                            });

                            row.locations.forEach(location => {
                                const locationName = location.location_name ||
                                    location.name;
                                if (locationName && !existingLocationNames.includes(
                                        locationName)) {
                                    locationDisplay.push(locationName);
                                }
                            });
                        }

                        return locationDisplay.length > 0 ? locationDisplay.join(', ') :
                            'No locations';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        // Get the latest batch retail price if batches exist, otherwise use product retail price
                        let displayPrice = row.product.retail_price || 0;
                        let priceSource = 'default';

                        // Check if product has batches (ordered by newest first)
                        if (row.product.batches && row.product.batches.length > 0) {
                            // Debug: Log batch data for first product to check structure
                            if (row.product.id === 51) { // Bajaj Ceiling Fan
                                console.log('=== DEBUG BATCHES FOR PRODUCT 51 ===');
                                console.log('Batches:', row.product.batches);
                                row.product.batches.forEach((batch, index) => {
                                    console.log(`Batch ${index}:`, {
                                        batch_no: batch.batch_no,
                                        retail_price: batch.retail_price,
                                        created_at: batch.created_at,
                                        location_batches: batch.location_batches
                                    });
                                });
                            }

                            // Simply find the most recent batch with a valid retail price
                            // The batches are already ordered by created_at desc from backend
                            const batchWithPrice = row.product.batches.find(batch =>
                                batch.retail_price !== null &&
                                batch.retail_price !== undefined &&
                                batch.retail_price !== '' &&
                                parseFloat(batch.retail_price) > 0
                            );

                            if (batchWithPrice) {
                                displayPrice = batchWithPrice.retail_price;
                                priceSource = 'batch';

                                if (row.product.id === 51) {
                                    console.log('Selected batch for product 51:', batchWithPrice);
                                    console.log('Display price:', displayPrice);
                                }
                            }
                        }

                        const formattedPrice = parseFloat(displayPrice).toFixed(2);

                        // Add indicator to show price source
                        if (priceSource === 'batch') {
                            return `${formattedPrice} <small class="text-muted">(B)</small>`;
                        } else {
                            return formattedPrice;
                        }
                    }
                },
                {
                    data: "total_stock"
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return categoryMap[row.product.main_category_id] || 'N/A';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return brandMap[row.product.brand_id] || 'N/A';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        // Access is_active from the nested product object
                        const isActive = row.product && row.product.is_active;
                        return isActive === 1 || isActive === true ?
                            '<span class="badge bg-success">Active</span>' :
                            '<span class="badge bg-secondary">Inactive</span>';
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return row.product.is_imei_or_serial_no === 1 ? "True" : "False";
                    }
                }
            ],
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            pageLength: 10,
            ordering: false,
            drawCallback: function() {
                attachEventHandlers();
            }
        });
    }

    // Attach all event handlers for table actions (call after each draw)
    function attachEventHandlers() {
        // Remove all existing event handlers first to prevent duplicates
        $('#productTable tbody').off('click');
        $('.product-checkbox').off('click change');
        $('.dropdown-item').off('click');
        $('.btn-toggle-status').off('click');
        $('.view-product').off('click');
        $('.edit-batch-prices').off('click');
        $('.show-imei-modal').off('click');
        $('.delete-product-dropdown').off('click');
        $('#selectAll').off('change');

        // Select all checkbox
        $('#selectAll').on('change', function() {
            const isChecked = this.checked;
            $('.product-checkbox').prop('checked', isChecked).trigger('change');
        });

        // Checkbox click handler - prevent any modal opening
        $('.product-checkbox').on('click', function(e) {
            e.stopImmediatePropagation();
        });

        // Checkbox change handler
        $('.product-checkbox').on('change', function(e) {
            e.stopPropagation();
            const productId = $(this).data('product-id');
            if (this.checked) {
                if (!selectedProductIds.includes(productId)) {
                    selectedProductIds.push(productId);
                }
            } else {
                selectedProductIds = selectedProductIds.filter(id => id !== productId);
            }
            toggleActionButtons();
        });

        // Prevent sort on action columns
        $('#productTable thead th').off('click').on('click', function(event) {
            event.stopImmediatePropagation();
        });

        // Action dropdown clicks
        $('.dropdown-item').on('click', function(event) {
            event.stopPropagation();
            event.preventDefault();

            const href = $(this).attr('href');
            if (href && href !== '#') {
                window.location.href = href;
            }
        });

        // View product modal - single event handler
        $('.view-product').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const productId = $(this).data('product-id');
            if (productId) {
                fetchProductDetails(productId);
                $('#viewProductModal').modal('show');
            }
        });

        // Toggle product status - single event handler
        $('.btn-toggle-status').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const productId = $(this).data('product-id');
            const statusData = $(this).data('status');

            // Convert string to boolean
            const currentStatus = statusData === 'true' || statusData === true || statusData ===
                1 || statusData === "1";

            if (productId !== undefined && productId !== null) {
                try {
                    toggleProductStatus(productId, currentStatus);
                } catch (error) {
                    console.error('Error in toggleProductStatus:', error);
                    alert('Error occurred while toggling product status');
                }
            } else {
                console.error('Product ID is undefined or null');
                alert('Error: Product ID not found');
            }
        });

        // Batch Prices modal - single event handler
        $('.edit-batch-prices').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const productId = $(this).data('product-id');
            loadBatchPricesModal(productId);
        });

        // IMEI modal - single event handler
        $('.show-imei-modal').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            const productId = $(this).data('product-id');
            $('#currentProductId').val(productId);
            $('#imeiTableBody').empty();

            // Show loading message
            $('#imeiTableBody').append(
                '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading IMEI numbers...</td></tr>'
            );
            $('#imeiModal').modal('show');

            // Get selected location from filter
            const selectedLocationId = $('#locationFilter').val();

            // Fetch IMEI data from server
            $.ajax({
                url: `/get-imeis/${productId}`,
                type: 'GET',
                data: {
                    location_id: selectedLocationId
                },
                success: function(response) {
                    $('#imeiTableBody').empty();

                    if (response.status === 200 && response.data && response.data
                        .length > 0) {
                        let counter = 1;
                        response.data.forEach(imei => {
                            $('#imeiTableBody').append(`
                            <tr>
                                <td>${counter++}</td>
                                <td>
                                    <input type="text" class="form-control imei-input"
                                        data-imei-id="${imei.id}"
                                        value="${imei.imei_number}" ${!imei.editable ? 'disabled' : ''}>
                                </td>
                                <td>${imei.location_name || 'N/A'}</td>
                                <td>${imei.batch_no || 'N/A'}</td>
                                <td>
                                    ${imei.status === 'available'
                                        ? '<span class="badge bg-success">Available</span>'
                                        : imei.status === 'unavailable'
                                        ? '<span class="badge bg-danger">Unavailable</span>'
                                        : '<span class="badge bg-warning text-dark">Sold</span>'}
                                </td>
                            </tr>
                        `);
                        });

                        $('#imeiModalTitle').html(
                            `<span class="text-info">${response.product_name || 'Product'}</span> (IMEI NO: ${response.data.length})`
                        );
                    } else {
                        $('#imeiTableBody').append(
                            '<tr><td colspan="5" class="text-center">No IMEI numbers found for this product in the selected location.</td></tr>'
                        );
                        $('#imeiModalTitle').html(
                            '<span class="text-info">Product</span> (IMEI NO: 0)');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching IMEI data:', error);
                    $('#imeiTableBody').empty();
                    $('#imeiTableBody').append(
                        '<tr><td colspan="5" class="text-center text-danger">Error loading IMEI numbers. Please try again.</td></tr>'
                    );
                }
            });
        });

        // Delete product handler - single event handler
        $('.delete-product-dropdown').on('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            const productId = $(this).data('product-id');

            if (!productId) {
                toastr.error('Product ID not found', 'Error');
                return;
            }

            // Show SweetAlert confirmation dialog
            swal({
                title: "Are you sure?",
                text: "Do you want to delete this product? This action cannot be undone!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function(isConfirm) {
                if (!isConfirm) return;

                $.ajax({
                    url: `/product-delete/${productId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 200 && response.can_delete) {
                            // Product deleted successfully
                            swal({
                                title: "Deleted!",
                                text: response.message,
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Reload the DataTable
                            if ($.fn.DataTable.isDataTable('#productTable')) {
                                $('#productTable').DataTable().ajax.reload(null,
                                    false);
                            }
                        } else if (response.status === 403 && !response
                            .can_delete) {
                            // Product cannot be deleted - show detailed message
                            swal({
                                title: "Cannot Delete!",
                                text: response.message,
                                type: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Deactivate Instead",
                                cancelButtonText: "Cancel",
                                closeOnConfirm: false
                            }, function(willDeactivate) {
                                if (willDeactivate) {
                                    // Toggle product status
                                    $.ajax({
                                        url: `/toggle-product-status/${productId}`,
                                        type: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': $(
                                                'meta[name="csrf-token"]'
                                                ).attr(
                                                'content')
                                        },
                                        success: function(
                                            toggleResponse
                                            ) {
                                            if (toggleResponse
                                                .status ===
                                                200) {
                                                swal({
                                                    title: "Success!",
                                                    text: "Product deactivated successfully",
                                                    type: "success",
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });

                                                // Reload table
                                                if ($.fn
                                                    .DataTable
                                                    .isDataTable(
                                                        '#productTable'
                                                        )) {
                                                    $('#productTable')
                                                        .DataTable()
                                                        .ajax
                                                        .reload(
                                                            null,
                                                            false
                                                            );
                                                }
                                            }
                                        },
                                        error: function(xhr) {
                                            swal("Error!",
                                                "Failed to deactivate product",
                                                "error");
                                        }
                                    });
                                }
                            });
                        } else {
                            swal("Error!", response.message ||
                                'Failed to delete product', "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error);

                        let errorMessage = 'Failed to delete product';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        swal("Error!", errorMessage, "error");
                    }
                });
            });
        });

        // Row click handler - only for non-interactive elements
        $('#productTable tbody tr').on('click', function(event) {
            // Check if click is on interactive elements
            const target = $(event.target);

            // Don't trigger modal for these elements
            if (target.hasClass('product-checkbox') ||
                target.is('input[type="checkbox"]') ||
                target.hasClass('dropdown-toggle') ||
                target.hasClass('dropdown-item') ||
                target.hasClass('btn') ||
                target.hasClass('action-button') ||
                target.closest('.dropdown').length > 0 ||
                target.closest('button').length > 0 ||
                target.closest('.product-checkbox').length > 0 ||
                target.closest('td:first-child').length > 0) {
                return;
            }

            // Get product ID and show modal
            const productId = $(this).find('.product-checkbox').data('product-id');
            if (productId) {
                fetchProductDetails(productId);
                $('#viewProductModal').modal('show');
            }
        });
    }

    // On filter change, reload DataTable (triggers ajax with filters)
    $('#productNameFilter, #categoryFilter, #brandFilter, #locationFilter, #stockStatusFilter').on('change', function() {
        if ($.fn.DataTable.isDataTable('#productTable')) {
            try {
                $('#productTable').DataTable().ajax.reload(null, false);
            } catch (error) {
                console.error('Error reloading DataTable:', error);
                toastr.error('Error reloading product list', 'Error');
            }
        }
    });

    // Clear Filters Button Handler
    $('#clearFiltersBtn').on('click', function() {
        console.log('🧹 Clearing all filters...');

        // Clear all filter dropdowns to their default "Select..." options
        $('#productNameFilter').val('').trigger('change');
        $('#categoryFilter').val('').trigger('change');
        $('#brandFilter').val('').trigger('change');
        $('#locationFilter').val('').trigger('change');
        $('#stockStatusFilter').val('').trigger('change');

        // If using Select2, also trigger Select2 events for proper clearing
        if (typeof $.fn.select2 !== 'undefined') {
            $('#productNameFilter, #categoryFilter, #brandFilter, #locationFilter, #stockStatusFilter').select2();
        }

        // Reload DataTable to show all products (no filters applied)
        if ($.fn.DataTable.isDataTable('#productTable')) {
            try {
                $('#productTable').DataTable().ajax.reload(null, false);
                toastr.success('Filters cleared successfully', 'Success');
            } catch (error) {
                console.error('Error reloading DataTable after clearing filters:', error);
                toastr.error('Error reloading product list', 'Error');
            }
        }

        console.log('✅ Filters cleared and table reloaded');
    });

    // On page load: fetch categories/brands/locations, then initialize DataTable
    $(document).ready(function() {
        // Only run this on actual product page, not on purchase/sale pages
        // Check for: product table (list page), edit_product_id (edit page), or addForm (add page)
        if (!$('#productTable').length && !$('#edit_product_id').length && !$('#addForm').length) {
            console.log('⏭️ Skipping product page initialization (not on product page)');
            return;
        }

        console.log('🚀 Initializing product page...');

        // Initialize buttons based on current page mode
        const isEditPage = window.location.pathname.includes('/edit-product/');
        if (!isEditPage) {
            // Ensure buttons are in add mode by default
            resetButtonsForAddMode();
        }

        // Only fetch initial data once and handle both form validation and DataTable initialization
        fetchInitialDropdowns(function() {
            // First validate form and update buttons
            validateFormAndUpdateButtons();

            // Then initialize DataTable if we're on a list page
            if (typeof fetchCategoriesAndBrands === 'function' && typeof fetchProductData === 'function') {
                fetchCategoriesAndBrands(function() {
                    // First initialize the DataTable
                    fetchProductData();
                    // Then populate the filter options with ALL available categories and brands
                    setTimeout(function() {
                        populateAllFilterOptions();
                    }, 100); // Small delay to ensure DataTable is fully initialized
                });
            }
        });
    });

    function resetFormAndValidation() {
        // Reset form and validation
        $('#addForm')[0].reset();
        $('#addForm').validate().resetForm();

        // Clear validation styling
        $('#addForm').find('.is-invalidRed, .is-validGreen').removeClass('is-invalidRed is-validGreen');
        $('.text-danger').html('');

        // Reset product image
        $('#product-selectedImage').attr('src', '/assets/img/No Product Image Available.png');

        // Clear product ID (important for edit mode)
        $('#product_id').val('');

        // Reset buttons to add mode
        resetButtonsForAddMode();

        // Clear summernote content
        if ($('#summernote').length) {
            $('#summernote').summernote('code', '');
        }

        // Clear all input fields
        $('input[type="text"], input[type="number"], input[type="email"], input[type="tel"], textarea').val(
            '');

        // Clear checkboxes and radio buttons
        $('input[type="checkbox"], input[type="radio"]').prop('checked', false);

        // Reset dropdowns (only clear selections, not options)
        resetAllDropdowns();

        // Disable buttons initially
        allButtons.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');

        // Focus on first field and validate
        setTimeout(function() {
            $('input[name="product_name"]').focus();
            validateFormAndUpdateButtons();
        }, 300);
    }

    // Function to reset only the product form without affecting purchase data
    function resetProductFormOnly() {
        // Reset form and validation
        $('#addForm')[0].reset();
        $('#addForm').validate().resetForm();

        // Clear validation styling
        $('#addForm').find('.is-invalidRed, .is-validGreen').removeClass('is-invalidRed is-validGreen');
        $('.text-danger').html('');

        // Reset product image
        $('#product-selectedImage').attr('src', '/assets/img/No Product Image Available.png');

        // Clear product ID (important for edit mode)
        $('#product_id').val('');

        // Reset buttons to add mode
        resetButtonsForAddMode();

        // Clear summernote content
        if ($('#summernote').length) {
            $('#summernote').summernote('code', '');
        }

        // Clear all input fields in the product form only
        $('#addForm input[type="text"], #addForm input[type="number"], #addForm input[type="email"], #addForm input[type="tel"], #addForm textarea').val('');

        // Clear checkboxes and radio buttons in the product form only
        $('#addForm input[type="checkbox"], #addForm input[type="radio"]').prop('checked', false);

        // Reset dropdowns in the product form only (clear selections, not options)
        resetAllDropdowns();

        // Disable buttons initially
        allButtons.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');

        // Focus on first field and validate
        setTimeout(function() {
            $('input[name="product_name"]').focus();
            validateFormAndUpdateButtons();
        }, 300);

        console.log('✅ Product form reset without affecting purchase data');
    }

    // Enhanced function to reset all dropdowns with proper placeholders
    function resetAllDropdowns() {
        console.log('🔄 Resetting dropdown selections (preserving option data)');

        // Define dropdowns with their placeholders
        const dropdownConfig = {
            '#edit_unit_id': 'Select Unit',
            '#edit_brand_id': 'Select Brand',
            '#edit_main_category_id': 'Select Main Category',
            '#edit_sub_category_id': 'Select Sub Category',
            '#edit_location_id': 'Select Location',
            'select[name="locations[]"]': 'Select Location'
        };

        // Reset each dropdown with proper placeholder
        Object.entries(dropdownConfig).forEach(function([selector, placeholder]) {
            const $dropdown = $(selector);
            if (!$dropdown.length) return;

            const optionsCount = $dropdown.find('option').length;
            console.log(`📋 Dropdown ${selector}: ${optionsCount} options preserved`);

            try {
                if ($dropdown.hasClass('select2-hidden-accessible')) {
                    // For Select2 dropdowns - just clear the selection, don't destroy or empty the options
                    $dropdown.val(null).trigger('change');

                    // Update the placeholder display
                    const $container = $dropdown.next('.select2-container');
                    if ($container.length) {
                        const $rendered = $container.find('.select2-selection__rendered');
                        if (selector.includes('locations[]')) {
                            // For multiple select, clear all selections
                            $rendered.empty().html(`<span class="select2-selection__placeholder">${placeholder}</span>`);
                        } else {
                            // For single select, show placeholder
                            $rendered.html(`<span class="select2-selection__placeholder">${placeholder}</span>`);
                        }
                    }
                } else {
                    // For regular dropdowns - only clear the selected value, not the options
                    $dropdown.val('').trigger('change');
                }

            } catch (e) {
                console.warn('Failed to reset dropdown:', selector, e);
                // Fallback: simple reset
                $dropdown.val('').trigger('change');
            }
        });

        // Additional cleanup for location fields
        setTimeout(function() {
            // Clear any remaining Select2 visual artifacts for multiple selections
            $('.select2-selection__choice').remove();
        }, 100);

        // Reset sub-category when main category changes
        setTimeout(function() {
            // Only reset sub-category selection, not the options
            const $subCategory = $('#edit_sub_category_id');
            $subCategory.val('').trigger('change');

            // Update placeholder display for sub-category
            if ($subCategory.hasClass('select2-hidden-accessible')) {
                const $container = $subCategory.next('.select2-container');
                if ($container.length) {
                    $container.find('.select2-selection__rendered').html(
                        '<span class="select2-selection__placeholder">Select Sub Category</span>'
                    );
                }
            }
        }, 200);

        console.log('✅ All dropdown selections reset, option data preserved');
    }

    // Global flag to track submission state
    let isSubmitting = false;

    // Function to get the form action URL based on whether we are adding or updating
    function getFormActionUrl() {
        const productId = $('#product_id').val();
        return productId ? `/product/update/${productId}` : '/product/store';

    }

    // Simplified form submission handler
    function handleFormSubmit(buttonType) {
        // Prevent double submission
        if (isSubmitting) {
            toastr.clear();
            toastr.warning('Form is already being submitted. Please wait.', 'Please Wait');
            return;
        }

        // Validate form first
        if (!$('#addForm').valid() || !validateFormAndUpdateButtons()) {
            // Play warning sound if available
            if (document.getElementsByClassName('warningSound')[0]) {
                document.getElementsByClassName('warningSound')[0].play();
            }
            toastr.error('Please fill all required fields correctly!', 'Validation Error');
            return;
        }

        // Set submission state and disable buttons
        isSubmitting = true;
        allButtons.prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');

        // Prepare form data
        let form = $('#addForm')[0];
        let formData = new FormData(form);

        // Handle nullable fields - convert empty string to null for sub_category_id
        const subCategoryValue = $('#edit_sub_category_id').val();
        if (subCategoryValue === '' || subCategoryValue === null || subCategoryValue === 'Select Sub Category') {
            formData.delete('sub_category_id'); // Remove the field entirely if empty
        }

        // Add Summernote content if available
        if ($('#summernote').length) {
            formData.append('description', $('#summernote').val());
        }

        $.ajax({
            url: getFormActionUrl(),
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            timeout: 30000, // 30 second timeout for file uploads
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                    });
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Please fix the validation errors', 'Validation Failed');
                    }
                } else {
                    // Play success sound if available
                    if (document.getElementsByClassName('successSound').length > 0) {
                        document.getElementsByClassName('successSound')[0].play();
                    }

                    const isEditMode = $('#product_id').val(); // Check if we're editing
                    const currentPath = window.location.pathname;
                    const isEditPage = currentPath.includes('/edit-product');
                    const isAddPage = currentPath.includes('/add-product');

                    if (buttonType === 'onlySave') {
                        if (isEditMode || isEditPage) {
                            // Edit mode - show success message and navigate to list
                            toastr.clear(); // Clear any existing notifications
                            toastr.success('Product updated successfully!', 'Updated');
                            // Navigate to list-product after edit
                            window.location.href = '/list-product';
                        } else {
                            // Add mode - normal behavior
                            toastr.clear(); // Clear any existing notifications
                            toastr.success(response.message, 'Success');

                            // Only reset form if NOT in purchase context (to prevent clearing purchase table)
                            if ($('#purchase_product').length === 0) {
                                resetFormAndValidation();
                            } else {
                                // In purchase context - only reset the product form, not the purchase data
                                resetProductFormOnly();
                            }

                            // Only fetch and add to purchase table if it exists
                            if ($('#purchase_product').length > 0) {
                                fetchLastAddedProducts();
                            }

                            // Navigate to list-product only when on add-product page
                            if (isAddPage) {
                                window.location.href = '/list-product';
                            }
                        }
                        $('#new_purchase_product').modal('hide');
                    } else if (buttonType === 'saveAndAnother') {
                        if (isEditMode || isEditPage) {
                            // Edit mode - redirect to add new product
                            toastr.clear();
                            toastr.success('Product updated successfully!', 'Updated');
                            window.location.href = '/add-product';
                        } else {
                            // Add mode - reset form for next product
                            toastr.clear();
                            toastr.success(response.message + ' - Ready for next product',
                                'Success');

                            // Use the appropriate reset function based on context
                            if ($('#purchase_product').length === 0) {
                                resetFormAndValidation();
                            } else {
                                // In purchase context - only reset the product form, not the purchase data
                                resetProductFormOnly();
                            }

                            // Add product to purchase table if it exists
                            if ($('#purchase_product').length > 0) {
                                fetchLastAddedProducts();
                            }
                        }
                    } else if (buttonType === 'saveAndOpeningStock') {
                        const productId = $('#product_id').val();
                        toastr.clear(); // Clear any existing notifications
                        toastr.success(response.message, 'Success');
                        if (productId) {
                            window.location.href =
                                `/edit-opening-stock/${response.product_id || productId}`;
                        } else {
                            window.location.href = `/opening-stock/${response.product_id}`;
                        }
                    }
                }
            },
            error: function(xhr) {
                toastr.error('Failed to add product. Please try again.', 'Error');
            },
            complete: function() {
                // Reset submission flag
                isSubmitting = false;

                // Re-validate form to determine proper button state
                setTimeout(function() {
                    validateFormAndUpdateButtons();
                }, 100);
            }
        });
    }

    // Button click event handlers with validation check first
    $('#onlySaveProductButton').click(function(e) {
        e.preventDefault();

        // Only disable button AFTER validation passes (handled in handleFormSubmit)
        handleFormSubmit('onlySave');
    });

    $('#SaveProductButtonAndAnother').click(function(e) {
        e.preventDefault();

        // Only disable button AFTER validation passes (handled in handleFormSubmit)
        handleFormSubmit('saveAndAnother');
    });

    $('#openingStockAndProduct').click(function(e) {
        e.preventDefault();

        // Only disable button AFTER validation passes (handled in handleFormSubmit)
        handleFormSubmit('saveAndOpeningStock');
    });



    function fetchLastAddedProducts() {
        fetchData('/get-last-product', function(response) {
            if (response.status === 200) {
                const product = response.product;

                // Check if purchase_product table exists
                if ($('#purchase_product').length === 0) {
                    console.error('Purchase product table not found');
                    toastr.error('Purchase product table not found', 'Error');
                    return;
                }

                addProductToTable(product);
                // Success message already shown from main form submission
            } else {
                toastr.error(response.message || 'Unable to fetch product details.', 'Error');
            }
        }, function(xhr, status, error) {
            console.error('Error fetching last product:', error);
            toastr.error('Failed to fetch last added product', 'Error');
        });
    }

    function addProductToTable(product, isEditing = false, prices = {}) {
        // Check if the purchase_product table exists and is initialized as DataTable
        if ($('#purchase_product').length === 0) {
            console.error('Purchase product table element not found');
            toastr.error('Purchase product table not found', 'Error');
            return;
        }

        let table;
        let isDataTable = false;

        // Check if DataTable is initialized
        if ($.fn.DataTable.isDataTable('#purchase_product')) {
            table = $("#purchase_product").DataTable();
            isDataTable = true;
        } else {
            table = $("#purchase_product");
        }

        let existingRow = null;

        $('#purchase_product tbody tr').each(function() {
            const rowProductId = $(this).data('id');
            if (rowProductId === product.id) {
                existingRow = $(this);
                return false;
            }
        });

        // Determine if decimal is allowed for this product
        const allowDecimal = product.unit?.allow_decimal === 1 || product.unit?.allow_decimal === "1";
        const quantityStep = allowDecimal ? "0.01" : "1";
        const quantityMin = allowDecimal ? "0.01" : "1";
        const quantityPattern = allowDecimal ? "[0-9]+([.][0-9]{1,2})?" : "[0-9]+";

        // Get current stock quantity from locations
        const currentStock = product.locations && product.locations.length > 0 ? product.locations[0].pivot
            .qty : 0;

        if (existingRow && !isEditing) {
            // Update IMEI data attribute for existing row
            existingRow.attr('data-imei-enabled', product.is_imei_or_serial_no || 0);

            const quantityInput = existingRow.find('.purchase-quantity');
            let currentVal = parseFloat(quantityInput.val()) || 0;
            let newQuantity = allowDecimal ? (currentVal + 1) : (parseInt(currentVal) + 1);
            quantityInput.val(newQuantity).trigger('input');
        } else {
            // Use correct property names from the JSON response
            const wholesalePrice = parseFloat(prices.wholesale_price || product.whole_sale_price) || 0;
            const specialPrice = parseFloat(prices.special_price || product.special_price) || 0;
            const maxRetailPrice = parseFloat(prices.max_retail_price || product.max_retail_price) || 0;
            const retailPrice = parseFloat(prices.retail_price || product.retail_price) || 0;
            const unitCost = parseFloat(prices.unit_cost || product.original_price) || 0;

            const newRow = `
            <tr data-id="${product.id}" data-imei-enabled="${product.is_imei_or_serial_no || 0}">
                <td>${product.id}</td>
                <td>${product.product_name} <br><small>Stock: ${currentStock}</small>${product.is_imei_or_serial_no ? ' <span class="badge badge-info">IMEI</span>' : ''}</td>
                <td>
                    <input type="number" class="form-control purchase-quantity" value="${prices.quantity || 1}" min="${quantityMin}" step="${quantityStep}" pattern="${quantityPattern}" ${allowDecimal ? '' : 'oninput="this.value = this.value.replace(/[^0-9]/g, \'\')"'}>
                </td>
                <td>
                    <input type="number" class="form-control product-price" value="${unitCost.toFixed(2)}" min="0">
                </td>
                <td>
                    <input type="number" class="form-control discount-percent" value="0" min="0" max="100">
                </td>
                <td><input type="number" class="form-control amount unit-cost" value="${unitCost.toFixed(2)}" min="0"></td>
                <td class="sub-total">0</td>
                <td><input type="number" class="form-control special-price" value="${specialPrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control wholesale-price" value="${wholesalePrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control max-retail-price" value="${maxRetailPrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control profit-margin" value="0" min="0"></td>
                <td><input type="number" class="form-control retail-price" value="${retailPrice.toFixed(2)}" min="0" required></td>
                <td><input type="date" class="form-control expiry-date" value="${product.expiry_date || ''}"></td>
                <td><input type="text" class="form-control batch_no" value="${product.batch_no || ''}"></td>
                <td><button class="btn btn-danger btn-sm remove-purchase-row"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;

            try {
                const $newRow = $(newRow);

                if (isDataTable) {
                    // Add to DataTable
                    table.row.add($newRow).draw();
                } else {
                    // Add to regular table
                    $('#purchase_product tbody').append($newRow);
                }

                // Trigger any necessary events
                $newRow.find('.purchase-quantity').trigger('input');

            } catch (error) {
                console.error('Error adding row to table:', error);
                toastr.error('Failed to add product to table', 'Error');
            }
        }
    }




    $(".show-picture").on("change", function() {
        const input = this;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();

            if (file.type.startsWith("image/")) {
                reader.onload = function(e) {
                    $("#product-selectedImage").attr("src", e.target.result);
                    $("#product-selectedImage").show();
                    $("#pdfViewer").hide();
                };
                reader.readAsDataURL(file);
            }
        }
    });

    $('#edit_main_category_id').change(function() {
        var main_category_id = $(this).val();
        $('#edit_sub_category_id').empty().append(
            '<option value="">Select Sub Category</option>');

        $.ajax({
            url: '/sub_category-details-get-by-main-category-id/' + main_category_id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status == 200) {
                    // Check if response.message is an array before using forEach
                    if (Array.isArray(response.message)) {
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append(
                                `<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`
                            );
                        });
                    } else if (typeof response.message === 'string') {
                        // Handle case where response.message is a string like "No Records Found!"
                        console.log('No subcategories found: ', response.message);
                        $('#edit_sub_category_id').append(
                            '<option value="">No subcategories available</option>');
                    }
                } else {
                    console.log('Error: ', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ', error);
                // Handle 404 or other errors gracefully
                $('#edit_sub_category_id').append(
                    '<option value="">Error loading subcategories</option>');
            }
        });
    });


    $(document).ready(function() {
        // Only run on opening stock page
        if (!$('#product_id').length) {
            return;
        }

        const productId = $('#product_id').val();
        const productName = $('#product_name').val();
        const productSku = $('#product_sku').val();
        const productOriginalPrice = $('#product_original_price').val();

        const currentPath = window.location.pathname;
        const isEditMode = currentPath.startsWith('/edit-opening-stock/');

        // Global variables for unit information
        let allowDecimal = false;
        let unitName = 'Pc(s)';

        // Initialize datetime picker for expiry date fields
        function initializeDateTimePicker() {
            $(".expiry-date-picker").datepicker({
                dateFormat: 'yy-mm-dd' // Set the date format as per your requirement
            });
        }

        // Function to format quantity based on allow_decimal
        function formatQuantity(value) {
            if (!value || value === '') return '';

            if (allowDecimal) {
                // For decimal units, format to 2 decimal places and remove trailing zeros
                return parseFloat(value).toFixed(2).replace(/\.?0+$/, '');
            } else {
                // For non-decimal units, show as integer
                return Math.floor(parseFloat(value) || 0).toString();
            }
        }

        // Function to get quantity input attributes based on unit
        function getQuantityInputAttributes() {
            if (allowDecimal) {
                return {
                    step: '0.01',
                    min: '0.01',
                    pattern: '[0-9]+([.][0-9]{1,2})?'
                };
            } else {
                return {
                    step: '1',
                    min: '1',
                    pattern: '[0-9]+'
                };
            }
        }

        // Fetch and populate data dynamically only if productId is valid
        if (productId && /^\d+$/.test(productId)) {
            fetchOpeningStockData(productId, isEditMode);
        }

        $('#addRow').click(function() {
            var index = $('#locationRows tr').length;
            var locationId = $('#locationRows tr:last').data('location-id');
            var locationName = $('#locationRows tr:last td:first p').text();

            // Get quantity input attributes based on unit
            const quantityAttrs = getQuantityInputAttributes();

            var newRow = `
            <tr data-location-id="${locationId}">
                <td>
                    <input type="hidden" name="locations[` + index + `][id]" value="${locationId}">
                    <p>${locationName}</p>
                </td>
                <td>
                    <p>${productName}</p>
                </td>
                <td>
                    <p>${productSku}</p>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input"
                        name="locations[` + index + `][qty]"
                        value=""
                        step="${quantityAttrs.step}"
                        min="${quantityAttrs.min}"
                        pattern="${quantityAttrs.pattern}">
                </td>
                <td>
                    <input type="text" class="form-control"
                        name="locations[` + index + `][unit_cost]"
                        value="${productOriginalPrice}" readonly>
                </td>
                <td>
                    <input type="text" class="form-control batch-no-input"
                        name="locations[` + index + `][batch_no]"
                        value="">
                </td>
                <td>
                    <input type="text" class="form-control expiry-date-picker"
                        name="locations[` + index + `][expiry_date]"
                        value="">
                </td>

                        <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn"><i class="fas fa-trash"></i></button>
            </td>
            </tr>
        `;
            $('#locationRows').append(newRow);
            initializeDateTimePicker(); // Re-initialize datetime picker for new rows
        });

        $('#submitOpeningStock').click(function(e) {
            e.preventDefault();

            // Prevent multiple submissions
            if ($(this).prop('disabled')) {
                return false;
            }

            handleFormSubmission(isEditMode, productId, $(this));
        });

        $('#submitOpeningStock-saveAndAddAnother').click(function(e) {
            e.preventDefault();

            // Prevent multiple submissions
            if ($(this).prop('disabled')) {
                return false;
            }

            handleFormSubmission(isEditMode, productId, $(this), true);
        });


        function fetchOpeningStockData(productId, isEditMode) {
            const url = isEditMode ? `/edit-opening-stock/${productId}` :
                `/opening-stock/${productId}`;

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        const product = response.product;
                        const locations = response.locations;
                        const batches = response.openingStock.batches;

                        // Set unit information globally
                        if (product.unit) {
                            allowDecimal = product.unit.allow_decimal;
                            unitName = product.unit.name || 'Pc(s)';
                        }

                        // Get quantity input attributes based on unit
                        const quantityAttrs = getQuantityInputAttributes();

                        $('#locationRows').html(
                            ''); // Clear existing rows before appending

                        // Create a map of location_id to array of batches
                        const batchesByLocation = {};
                        batches.forEach(batch => {
                            if (!batchesByLocation[batch.location_id]) {
                                batchesByLocation[batch.location_id] = [];
                            }
                            batchesByLocation[batch.location_id].push(batch);
                        });

                        // Track row index separately since we may have multiple rows per location
                        let rowIndex = 0;

                        // Show all locations, with all batches for each location
                        locations.forEach(function(location) {
                            const locationBatches = batchesByLocation[location
                                .id] || [];

                            // If no batches exist for this location, show one empty row
                            if (locationBatches.length === 0) {
                                const newRow = `
                            <tr data-location-id="${location.id}">
                                <td>
                                    <input type="hidden" name="locations[${rowIndex}][id]" value="${location.id}">
                                    <p>${location.name}</p>
                                </td>
                                <td>
                                    <p>${product.product_name}</p>
                                </td>
                                <td>
                                    <p>${product.sku}</p>
                                </td>
                                <td>
                                    <input type="number" class="form-control quantity-input"
                                        name="locations[${rowIndex}][qty]"
                                        value=""
                                        step="${quantityAttrs.step}"
                                        min="${quantityAttrs.min}"
                                        pattern="${quantityAttrs.pattern}">
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                        name="locations[${rowIndex}][unit_cost]"
                                        value="${product.original_price}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control batch-no-input"
                                        name="locations[${rowIndex}][batch_no]"
                                        value="">
                                </td>
                                <td>
                                    <input type="text" class="form-control expiry-date-picker"
                                        name="locations[${rowIndex}][expiry_date]"
                                        value="">
                                </td>
                            </tr>
                        `;
                                $('#locationRows').append(newRow);
                                rowIndex++;
                            } else {
                                // Show one row for each batch in this location
                                locationBatches.forEach(batch => {
                                    // Format quantity based on unit settings
                                    const formattedQuantity =
                                        formatQuantity(batch.quantity);

                                    // Handle null expiry date
                                    const expiryDate = batch
                                        .expiry_date && batch
                                        .expiry_date !== 'null' ? batch
                                        .expiry_date : '';

                                    const newRow = `
                                <tr data-location-id="${location.id}">
                                    <td>
                                        <input type="hidden" name="locations[${rowIndex}][id]" value="${location.id}">
                                        <p>${location.name}</p>
                                    </td>
                                    <td>
                                        <p>${product.product_name}</p>
                                    </td>
                                    <td>
                                        <p>${product.sku}</p>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control quantity-input"
                                            name="locations[${rowIndex}][qty]"
                                            value="${formattedQuantity}"
                                            step="${quantityAttrs.step}"
                                            min="${quantityAttrs.min}"
                                            pattern="${quantityAttrs.pattern}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control"
                                            name="locations[${rowIndex}][unit_cost]"
                                            value="${product.original_price}" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control batch-no-input"
                                            name="locations[${rowIndex}][batch_no]"
                                            value="${batch.batch_no || ''}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control expiry-date-picker"
                                            name="locations[${rowIndex}][expiry_date]"
                                            value="${expiryDate}">
                                    </td>

                                </tr>
                            `;
                                    $('#locationRows').append(newRow);
                                    rowIndex++;
                                });
                            }
                        });


                        initializeDateTimePicker
                            (); // Initialize datetime picker for existing rows

                        if (isEditMode) {
                            $('#pageTitle').text('Edit Opening Stock for Product');
                            $('#breadcrumbTitle').text('Edit Opening Stock');
                            $('#submitOpeningStock').text('Update');
                            $('#submitOpeningStock-saveAndAddAnother').text(
                                'Update And Add Another');
                        }
                    } else {
                        console.log('Failed to fetch existing stock data.', 'Error');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to fetch existing stock data.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg += ' ' + xhr.responseJSON.message;
                    } else if (xhr && xhr.statusText) {
                        errorMsg += ' ' + xhr.statusText;
                    }
                    console.error(errorMsg);
                    toastr.error(errorMsg, 'Error');
                }
            });
        }

            // Remove row button handler (only for rows with the button)
            $(document).on('click', '.remove-purchase-row', function() {
                $(this).closest('tr').remove();
            });

        // Delete product button handler with safe deletion logic
        $(document).on('click', '.delete-product', function(e) {
            e.preventDefault();

            const button = $(this);
            const row = button.closest('tr');
            const productId = row.data('id');

            if (!productId) {
                toastr.error('Product ID not found', 'Error');
                return;
            }

            // Show SweetAlert confirmation dialog
            swal({
                title: "Are you sure?",
                text: "Do you want to delete this product? This action cannot be undone!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            }, function(isConfirm) {
                if (!isConfirm) return;

                // Disable button during deletion
                button.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: `/product-delete/${productId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content')
                    },
                    success: function(response) {
                        if (response.status === 200 && response
                            .can_delete) {
                            // Product deleted successfully
                            swal({
                                title: "Deleted!",
                                text: response.message,
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Remove the row from DataTable or regular table
                            if ($.fn.DataTable.isDataTable(
                                    '#productTable')) {
                                $('#productTable').DataTable().row(row)
                                    .remove().draw();
                            } else {
                                row.fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }
                        } else if (response.status === 403 && !response
                            .can_delete) {
                            // Product cannot be deleted - show detailed message
                            swal({
                                title: "Cannot Delete!",
                                text: response.message,
                                type: "warning",
                                showCancelButton: true,
                                confirmButtonText: "Deactivate Instead",
                                cancelButtonText: "Cancel",
                                closeOnConfirm: false
                            }, function(willDeactivate) {
                                if (willDeactivate) {
                                    // Toggle product status
                                    $.ajax({
                                        url: `/toggle-product-status/${productId}`,
                                        type: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': $(
                                                    'meta[name="csrf-token"]'
                                                    )
                                                .attr(
                                                    'content'
                                                    )
                                        },
                                        success: function(
                                            toggleResponse
                                            ) {
                                            if (toggleResponse
                                                .status ===
                                                200
                                                ) {
                                                swal({
                                                    title: "Success!",
                                                    text: "Product deactivated successfully",
                                                    type: "success",
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });

                                                // Reload table if exists
                                                if ($
                                                    .fn
                                                    .DataTable
                                                    .isDataTable(
                                                        '#productTable'
                                                        )
                                                    ) {
                                                    $('#productTable')
                                                        .DataTable()
                                                        .ajax
                                                        .reload(
                                                            null,
                                                            false
                                                            );
                                                }
                                            }
                                        },
                                        error: function(
                                            xhr) {
                                            swal("Error!",
                                                "Failed to deactivate product",
                                                "error"
                                                );
                                        }
                                    });
                                }
                            });

                            button.prop('disabled', false).html(
                                '<i class="fas fa-trash"></i>');
                        } else {
                            swal("Error!", response.message ||
                                'Failed to delete product', "error");
                            button.prop('disabled', false).html(
                                '<i class="fas fa-trash"></i>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error);

                        let errorMessage = 'Failed to delete product';
                        if (xhr.responseJSON && xhr.responseJSON
                            .message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        swal("Error!", errorMessage, "error");
                        button.prop('disabled', false).html(
                            '<i class="fas fa-trash"></i>');
                    }
                });
            });
        });

        // Real-time quantity formatting for opening stock inputs
        $(document).on('input', '.quantity-input', function() {
            const input = $(this);
            let value = input.val();

            if (value === '' || value === null) return;

            // Validate input based on unit settings
            if (allowDecimal) {
                // Allow decimal numbers with up to 2 decimal places
                if (!/^\d*\.?\d{0,2}$/.test(value)) {
                    return;
                }
            } else {
                // Allow only integers
                if (!/^\d*$/.test(value)) {
                    value = value.replace(/[^0-9]/g, '');
                    input.val(value);
                    return;
                }
            }
        });

        // Format quantity on blur (when user leaves the input field)
        $(document).on('blur', '.quantity-input', function() {
            const input = $(this);
            let value = input.val();

            if (value && value !== '') {
                const formattedValue = formatQuantity(value);
                input.val(formattedValue);
            }
        });


        function handleFormSubmission(isEditMode, productId, $button, saveAndAddAnother = false) {
            let form = $('#openingStockForm')[0];
            let formData = new FormData(form);

            let locations = [];
            formData.forEach((value, key) => {
                if (key.includes('locations') && value) {
                    let parts = key.split('[');
                    let index = parts[1].split(']')[0];
                    if (!locations[index]) {
                        locations[index] = {};
                    }
                    let field = parts[2].split(']')[0];
                    locations[index][field] = value;
                }
            });

            locations = locations.filter(location => location.qty).map(location => {
                if (!location.expiry_date) location.expiry_date = '';
                return location;
            });

            let url = isEditMode ? `/opening-stock/${productId}` :
                `/opening-stock/${productId}`;

            // Disable both buttons and store original text
            const originalText = $button.text();
            $('#submitOpeningStock, #submitOpeningStock-saveAndAddAnother').prop('disabled', true);
            $button.text('Please wait...');

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify({
                    locations
                }),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    if (response.status === 200) {
                        const enableImei = response.product.is_imei_or_serial_no === 1;

                        if (enableImei && response.batches && response.batches.length >
                            0) {
                            let totalQty = 0;
                            response.batches.forEach(batch => {
                                totalQty += parseInt(batch.qty);
                            });

                            $('#totalImeiCount').text(totalQty);
                            $('#imeiModal').modal('show');

                            // Clear previous rows
                            $('#imeiTable tbody').empty();

                            // Auto-fill from textarea if needed
                            $('#autoFillImeis').off().on('click', function() {
                                const imeiText = $('#imeiInput').val().trim();
                                const imeis = imeiText.split(/\r?\n/).filter(
                                    Boolean);
                                $('#imeiTable tbody').empty();

                                if (imeis.length === 0) {
                                    toastr.warning("No IMEIs found to fill.");
                                    return;
                                }

                                imeis.forEach((imei, idx) => {
                                    $('#imeiTable tbody').append(`
                                <tr>
                                    <td>${idx + 1}</td>
                                    <td><input type="text" class="form-control imei-input" value="${imei}"></td>
                                    <td><button class="btn btn-sm btn-danger removeImei"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            `);
                                });

                                toastr.success(
                                    `Filled ${imeis.length} rows from pasted IMEIs`
                                );
                            });

                            // Load existing IMEIs if editing
                            if (isEditMode) {
                                $.ajax({
                                    url: `/get-imeis/${productId}`,
                                    method: 'GET',
                                    success: function(res) {
                                        if (res.status === 200) {
                                            $('#imeiTable tbody').empty();
                                            res.imeis.forEach((imei,
                                                index) => {
                                                $('#imeiTable tbody')
                                                    .append(`
                                            <tr>
                                                <td>${index + 1}</td>
                                                <td><span class="form-control-plaintext">${imei.imei_number}</span></td>
                                                <td>${imei.location_name || 'N/A'}</td>
                                                <td>${imei.batch_no || 'N/A'}</td>
                                                <td><span class="badge badge-${imei.status === 'available' ? 'success' : (imei.status === 'sold' ? 'warning' : 'secondary')}">${imei.status}</span></td>
                                            </tr>
                                        `);
                                            });
                                            $('#totalImeiCount').text(res
                                                .imeis.length);
                                        }
                                    },
                                    error: function() {
                                        toastr.error(
                                            "Failed to load existing IMEIs",
                                            'Error');
                                    }
                                });
                            }

                            // Add Row Button with qty check
                            $('#addImeiRow').off().on('click', function() {
                                const currentCount = $('#imeiTable tbody tr')
                                    .length;
                                const allowedQty = totalQty;
                                if (currentCount + 1 > allowedQty) {
                                    toastr.warning(
                                        `You cannot add more than ${allowedQty} IMEI rows (Qty limit reached).`
                                    );
                                    return;
                                }
                                $('#imeiTable tbody').append(`
                            <tr>
                                <td>${currentCount + 1}</td>
                                <td><input type="text" class="form-control imei-input" placeholder="Enter IMEI"></td>
                                <td><button class="btn btn-sm btn-danger removeImei">Remove</button></td>
                            </tr>
                        `);
                            });

                            // Remove Row on Click (and update count)
                            $(document).on('click', '.removeImei', function() {
                                $(this).closest('tr').remove();
                                // Re-number the rows after removal
                                $('#imeiTable tbody tr').each(function(idx) {
                                    $(this).find('td:first').text(idx +
                                        1);
                                });
                            });

                            // Save Button Logic
                            $('#saveImeiButton').off().on('click', function() {
                                const imeis = [];
                                let hasEmpty = false;

                                $('#imeiTable tbody tr').each(function() {
                                    let val = $(this).find(
                                        '.imei-input').val().trim();
                                    if (!val) hasEmpty = true;
                                    imeis.push(val);
                                });


                                // Prepare data for IMEI save API
                                let saveData = {
                                    product_id: productId,
                                    imeis: imeis
                                };

                                // Check if we have valid batch data for old format
                                if (response.batches && Array.isArray(response
                                        .batches) &&
                                    response.batches.length > 0 &&
                                    response.batches[0].batch_id &&
                                    response.batches[0].location_id) {
                                    // Use old format with valid batch data
                                    saveData.batches = response.batches;
                                } else {
                                    // Use new format with intelligent batch selection
                                    // Try to get location_id from the first batch or default to 1
                                    saveData.location_id = (response.batches &&
                                        response.batches[0] && response
                                        .batches[0].location_id) || 1;
                                }

                                $.ajax({
                                    url: '/save-or-update-imei',
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $(
                                            'meta[name="csrf-token"]'
                                        ).attr('content')
                                    },
                                    data: JSON.stringify(saveData),
                                    contentType: 'application/json',
                                    success: function(imeiRes) {
                                        if (imeiRes.status ===
                                            200) {
                                            toastr.success(imeiRes
                                                .message,
                                                'Success');
                                            $('#imeiModal').modal(
                                                'hide');

                                            // Handle redirect based on button clicked
                                            if (saveAndAddAnother) {
                                                window.location
                                                    .href =
                                                    '/add-product';
                                            } else {
                                                window.location
                                                    .href =
                                                    '/list-product';
                                            }
                                        } else {
                                            if (imeiRes.message &&
                                                imeiRes.message
                                                .toLowerCase()
                                                .includes(
                                                    'duplicate')) {
                                                toastr.error(
                                                    'Duplicate IMEI numbers found. Please check your entries.',
                                                    'Error');
                                            } else {
                                                toastr.error(imeiRes
                                                    .message,
                                                    'Error');
                                            }
                                        }
                                    },
                                    error: function() {
                                        toastr.error(
                                            "Failed to save IMEIs",
                                            'Error');
                                    }
                                });
                            });

                            $('#imeiModal').on('click', '[data-bs-dismiss="modal"]',
                                function(e) {
                                    if (!confirm(
                                            "Are you sure you want to skip entering IMEIs?"
                                        )) {
                                        e.preventDefault();
                                        return;
                                    }

                                    $('#imeiTable tbody').empty();
                                    $('#imeiInput').val('');
                                    $('#imeiModal').modal('hide');

                                    // Handle redirect based on button clicked
                                    if (saveAndAddAnother) {
                                        window.location.href = '/add-product';
                                    } else {
                                        window.location.href = '/list-product';
                                    }
                                });
                        } else {
                            toastr.success(response.message, 'Success');
                            setTimeout(() => {
                                // Handle redirect based on button clicked
                                if (saveAndAddAnother) {
                                    window.location.href = '/add-product';
                                } else {
                                    window.location.href = '/list-product';
                                }
                            }, 1000);
                        }
                    } else {
                        // Re-enable buttons on error
                        $('#submitOpeningStock, #submitOpeningStock-saveAndAddAnother')
                            .prop('disabled', false);
                        $button.text(originalText);
                        toastr.error(response.message, 'Error');
                    }
                },
                error: function(xhr) {
                    // Re-enable buttons on error
                    $('#submitOpeningStock, #submitOpeningStock-saveAndAddAnother')
                        .prop('disabled', false);
                    $button.text(originalText);

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            $(`#${key}_error`).text(val[0]);
                        });
                    } else {
                        toastr.error('Unexpected error occurred', 'Error');
                    }
                }
            });
        }

    });

    // Extract the product ID from the URL and fetch data if valid
    $(document).ready(function() {
        // Only run on edit product page
        if (!window.location.pathname.includes('/edit-product/')) {
            return;
        }

        const pathSegments = window.location.pathname.split('/');
        const productId = pathSegments[pathSegments.length - 1];

        // Only proceed if productId is a positive integer
        if (/^\d+$/.test(productId)) {
            // Show loading indicator
            if (typeof toastr !== 'undefined') {
                toastr.info('Loading product details...', 'Please wait');
            }

            // Use cached initial data (no additional API call needed)
            fetchInitialDropdowns(() => {
                $.ajax({
                    url: `/edit-product/${productId}`,
                    type: 'GET',
                    dataType: 'json',
                    cache: true, // Enable caching for better performance
                    success: function(response) {
                        if (response.status === 200) {
                            const product = response.message.product;
                            const mainCategories = response.message
                                .mainCategories;
                            const subCategories = response.message
                                .subCategories;
                            const brands = response.message.brands;
                            const units = response.message.units;
                            const locations = response.message.locations;

                            populateProductDetails(product, mainCategories,
                                subCategories, brands, units, locations);

                            // Hide loading indicator
                            if (typeof toastr !== 'undefined') {
                                toastr.clear();
                                toastr.success(
                                    'Product details loaded successfully',
                                    'Success');
                            }
                        } else {
                            console.error('Error: ' + response.message);
                            if (typeof toastr !== 'undefined') {
                                toastr.error(
                                    'Failed to load product details: ' +
                                    response.message, 'Error');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(
                            'An error occurred while fetching product details:',
                            error);
                        if (typeof toastr !== 'undefined') {
                            toastr.error(
                                'Failed to load product details. Please try again.',
                                'Error');
                        }
                    }
                });
            });
        }
    });



    $('#viewProductModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var productId = button.data('product-id');

        fetchProductDetails(productId);
    });

    // Track ongoing requests to prevent duplicates
    let isLoadingProductDetails = false;

    function fetchProductDetails(productId) {
        // Prevent multiple simultaneous requests
        if (isLoadingProductDetails) {
            console.log('Product details already loading, ignoring request');
            return;
        }

        if (!productId) {
            console.error('No product ID provided');
            return;
        }

        isLoadingProductDetails = true;

        $.ajax({
                url: '/product-get-details/' + productId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        var product = response.message;
                        var imagePath = product.product_image ?
                            `/assets/images/${product.product_image}` :
                            '/assets/images/No Product Image Available.png';
                        var details = `
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <td rowspan="8" class="text-center align-middle">
                                            <img src='${imagePath}' width='150' height='200' class="rounded img-fluid" />
                                        </td>
                                        <th scope="row">Product Name</th>
                                        <td>${product.product_name}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">SKU</th>
                                        <td>${product.sku}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Category</th>
                                        <td>${categoryMap[product.main_category_id] || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Brand</th>
                                        <td>${brandMap[product.brand_id] || 'N/A'}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Locations</th>
                                        <td>${product.locations.map(loc => locationMap[loc.id] || 'N/A').join(', ')}</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Selling Price</th>
                                        <td>${(() => {
                                            let displayPrice = product.retail_price || 0;
                                            let priceSource = 'Default Product Price';

                                            // Get current location filter from the main page
                                            const selectedLocationId = $('#locationFilter').val();

                                            // Check if product has batches (ordered by newest first)
                                            if (product.batches && product.batches.length > 0) {
                                                let batchWithPrice = null;

                                                if (selectedLocationId) {
                                                    // Find batches that exist in the selected location
                                                    const locationSpecificBatches = product.batches.filter(batch => {
                                                        return batch.location_batches && batch.location_batches.some(locBatch =>
                                                            locBatch.location_id == selectedLocationId &&
                                                            parseFloat(locBatch.qty || 0) > 0 // Has stock in this location
                                                        );
                                                    });

                                                    // Find the most recent batch with valid price for this location
                                                    batchWithPrice = locationSpecificBatches.find(batch =>
                                                        batch.retail_price !== null &&
                                                        batch.retail_price !== undefined &&
                                                        batch.retail_price !== '' &&
                                                        parseFloat(batch.retail_price) > 0
                                                    );

                                                    if (batchWithPrice) {
                                                        const locationName = locationMap[selectedLocationId] || 'Selected Location';
                                                        priceSource = `Latest Batch Price for ${locationName} (${batchWithPrice.batch_no || 'N/A'})`;
                                                    }
                                                }

                                                // If no location-specific batch found or no location selected, use any batch
                                                if (!batchWithPrice) {
                                                    batchWithPrice = product.batches.find(batch =>
                                                        batch.retail_price !== null &&
                                                        batch.retail_price !== undefined &&
                                                        batch.retail_price !== '' &&
                                                        parseFloat(batch.retail_price) > 0
                                                    );

                                                    if (batchWithPrice) {
                                                        priceSource = `Latest Batch Price (${batchWithPrice.batch_no || 'N/A'})`;
                                                    }
                                                }

                                                if (batchWithPrice) {
                                                    displayPrice = batchWithPrice.retail_price;
                                                }
                                            }

                                            return `Rs.${parseFloat(displayPrice).toFixed(2)}<br><small class="text-muted">${priceSource}</small>`;
                                        })()}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">IMEI is Checked</th>
                                        <td>${product.is_imei_or_serial_no === 1 ? "True" : "False"}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>`;
            $('#productDetails').html(details);
        }
        else {
            console.error('Failed to load product details. Status: ' + response.status);
            toastr.error('Failed to load product details', 'Error');
        }
    },
    error: function(xhr, status, error) {
            console.error('Error fetching product details:', error);
            toastr.error('Failed to load product details', 'Error');
        },
        complete: function() {
            isLoadingProductDetails = false; // Reset the loading flag
        }
    });
    }

    });

    // Global function to fetch initial dropdowns - moved outside document.ready for global access
    window.fetchInitialDropdowns = function(callback) {
        // Check if data is already loaded and cached
        if (window.initialProductDataLoaded && window.initialProductData) {
            const cacheAge = Date.now() - window.initialDataFetchTime;
            console.log(`✅ Using cached initial product data (${cacheAge}ms old) - no API call needed`);
            const data = window.initialProductData;
            if (typeof populateInitialDropdowns === 'function') {
                populateInitialDropdowns(
                    data.mainCategories,
                    data.subCategories,
                    data.brands,
                    data.units,
                    data.locations,
                    data.autoSelectSingle,
                    callback
                );
            } else if (callback) {
                callback();
            }
            return;
        }

        console.log('🔄 Fetching initial product details from API...');
        $.ajax({
            url: '/initial-product-details',
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 200) {
                    // Cache the data globally to prevent future API calls
                    window.initialProductData = {
                        brands: response.message.brands,
                        mainCategories: response.message.mainCategories,
                        subCategories: response.message.subCategories,
                        units: response.message.units,
                        locations: response.message.locations,
                        autoSelectSingle: response.message.auto_select_single_location
                    };

                    // Mark as loaded with timestamp
                    window.initialProductDataLoaded = true;
                    window.initialDataFetchTime = Date.now();

                    console.log('✅ Initial product data fetched and cached successfully');

                    if (typeof populateInitialDropdowns === 'function') {
                        populateInitialDropdowns(
                            window.initialProductData.mainCategories,
                            window.initialProductData.subCategories,
                            window.initialProductData.brands,
                            window.initialProductData.units,
                            window.initialProductData.locations,
                            window.initialProductData.autoSelectSingle,
                            callback
                        );
                    } else if (callback) {
                        callback();
                    }
                } else {
                    console.error('❌ Failed to load initial product details');
                    if (callback) callback();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Failed to load initial product details:', error);
                if (callback) callback();
            }
        });
    };
</script>

<!-- Load user locations for import form -->
<script>
    $(document).ready(function() {
        // Load locations when import product page is loaded
        if ($('#import_location').length > 0) {
            loadUserLocations();
        }
    });

    function loadUserLocations() {
        // Use cached initial data instead of making another API call
        if (typeof window.fetchInitialDropdowns === 'function') {
            window.fetchInitialDropdowns(() => {
                console.log('✅ Using cached location data for import');
                if (window.initialProductData && window.initialProductData.locations) {
                    let locationSelect = $('#import_location');
                    locationSelect.empty();
                    locationSelect.append(
                        '<option value="">Choose Location to Import Products...</option>');

                    window.initialProductData.locations.forEach(function(location) {
                        let selected = location.selected ? 'selected' : '';
                        locationSelect.append(
                            `<option value="${location.id}" ${selected}>${location.name}</option>`
                        );
                    });

                    // If only one location and auto-select is enabled, auto-select it
                    if (window.initialProductData.autoSelectSingle && window.initialProductData.locations.length === 1) {
                        locationSelect.val(window.initialProductData.locations[0].id);
                    }

                    console.log('✅ Successfully loaded', window.initialProductData.locations.length,
                        'locations for import from cache');
                } else {
                    console.error('❌ No cached location data available');
                    toastr.error('Error loading locations. Please refresh the page.', 'Error');
                }
            });
        } else {
            console.error('❌ fetchInitialDropdowns function not available');
            toastr.error('Error: Required function not loaded. Please refresh the page.', 'Error');
        }
    }
</script>

<script>
    $(document).on('submit', '#importProductForm', function(e) {
        e.preventDefault();
        let formData = new FormData($('#importProductForm')[0]);
        let fileInput = $('input[name="file"]')[0];
        let locationId = $('#import_location').val();

        // Clear any existing toastr notifications
        toastr.clear();

        // Validate file input
        if (fileInput.files.length === 0) {
            $('#file_error').html('Please select an Excel file.');
            $('.errorSound')[0].play();
            toastr.error('Please select an Excel file.', 'File Required');
            return;
        } else {
            $('#file_error').html('');
        }

        // Validate location selection
        if (!locationId) {
            toastr.error('Please select a location for import.', 'Location Required');
            $('#import_location').focus();
            return;
        }

        $.ajax({
            xhr: function() {
                let xhr = new window.XMLHttpRequest();

                // Track upload progress
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percentComplete = (e.loaded / e.total) * 100;
                        $('.progress').show();
                        $('.progress-bar')
                            .css('width', percentComplete + '%')
                            .attr('aria-valuenow', percentComplete)
                            .text(Math.round(percentComplete) + '%');
                    }
                }, false);

                return xhr;
            },
            url: '/import-product-excel-store',
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function() {
                // Clear any existing notifications
                toastr.clear();
                $('#error-display-area').html('');

                $('.progress-bar').css('width', '0%').text('0%');
                $('.progress').show();
                $('#import_btn').prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                // Clear any existing toastr notifications first
                toastr.clear();

                // Debug log
                console.log('Import Response:', response);

                if (response.status == 400) {
                    // Handle file validation errors
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                        $('.errorSound')[0].play();
                        toastr.error(err_value, 'Validation Error');
                    });
                } else if (response.status == 200) {
                    // Complete success
                    $("#importProductForm")[0].reset();
                    $('#error-display-area').html(''); // Clear any previous errors
                    $('.successSound')[0].play();

                    // Show single consolidated success message
                    let successCount = response.success_count || 0;

                    // If status is 200, it's successful regardless of success_count
                    toastr.success(response.message ||
                        `Import successful! Products have been imported.`, 'Import Complete', {
                            timeOut: 5000,
                            extendedTimeOut: 2000
                        });

                    // Show navigation button after a delay
                    setTimeout(function() {
                        toastr.info(
                            '<div class="text-center"><a href="/list-product" class="btn btn-primary btn-sm">View Products</a></div>',
                            'Next Step', {
                                allowHtml: true,
                                timeOut: 0,
                                extendedTimeOut: 0,
                                closeButton: true
                            });
                    }, 3000);
                } else if (response.status == 401 || response.status == 422) {
                    // Import validation errors
                    $("#importProductForm")[0].reset();

                    // Show detailed error messages with row numbers
                    if (response.validation_errors && response.validation_errors.length > 0) {
                        // Group errors by row number for better display
                        let errorsByRow = {};
                        response.validation_errors.forEach(function(error) {
                            let rowMatch = error.match(/Row (\d+):/);
                            if (rowMatch) {
                                let rowNum = rowMatch[1];
                                if (!errorsByRow[rowNum]) {
                                    errorsByRow[rowNum] = [];
                                }
                                errorsByRow[rowNum].push(error.replace(/Row \d+:\s*/, ''));
                            } else {
                                if (!errorsByRow['general']) {
                                    errorsByRow['general'] = [];
                                }
                                errorsByRow['general'].push(error);
                            }
                        });

                        let errorHtml =
                            '<div class="alert alert-danger"><h5><i class="fas fa-exclamation-triangle"></i> Import Failed</h5>';
                        errorHtml += '<p><strong>' + response.message + '</strong></p>';
                        errorHtml += '<div class="row">';
                        errorHtml += '<div class="col-md-6">';
                        errorHtml += '<p><strong>Total Errors:</strong> ' + response
                            .validation_errors.length + '</p>';
                        errorHtml += '</div>';
                        errorHtml += '</div>';

                        // Create a scrollable detailed error area
                        errorHtml +=
                            '<div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">';
                        errorHtml += '<h6>Detailed Errors:</h6>';

                        Object.keys(errorsByRow).forEach(function(rowKey) {
                            if (rowKey === 'general') {
                                errorHtml +=
                                    '<div class="mb-2"><strong>General Errors:</strong><ul class="mb-0">';
                                errorsByRow[rowKey].forEach(function(error) {
                                    errorHtml += '<li class="text-danger">' +
                                        error + '</li>';
                                });
                                errorHtml += '</ul></div>';
                            } else {
                                errorHtml += '<div class="mb-2"><strong>Row ' + rowKey +
                                    ':</strong><ul class="mb-0">';
                                errorsByRow[rowKey].forEach(function(error) {
                                    errorHtml += '<li class="text-danger">' +
                                        error + '</li>';
                                });
                                errorHtml += '</ul></div>';
                            }
                        });

                        errorHtml += '</div>';
                        errorHtml += '<div class="mt-3">';
                        errorHtml +=
                            '<div class="d-flex justify-content-between align-items-start">';
                        errorHtml += '<div>';
                        errorHtml +=
                            '<p class="mb-0"><i class="fas fa-info-circle"></i> <strong>Next Steps:</strong></p>';
                        errorHtml += '<ol class="mb-0">';
                        errorHtml += '<li>Fix the highlighted errors in your Excel file</li>';
                        errorHtml += '<li>Ensure all required fields are filled</li>';
                        errorHtml += '<li>Upload the corrected file again</li>';
                        errorHtml += '</ol>';
                        errorHtml += '</div>';
                        errorHtml +=
                            '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="$(\'#error-display-area\').html(\'\'); $(this).closest(\'.alert\').fadeOut();" title="Clear errors">';
                        errorHtml += '<i class="fas fa-times"></i> Clear';
                        errorHtml += '</button>';
                        errorHtml += '</div>';
                        errorHtml += '</div></div>';

                        // Display errors in the error area
                        if ($('#error-display-area').length) {
                            $('#error-display-area').html(errorHtml);
                        } else {
                            // Create error display area if it doesn't exist
                            let errorArea = '<div id="error-display-area" class="mt-3">' +
                                errorHtml + '</div>';
                            $('#importProductForm').after(errorArea);
                        }

                        // Scroll to error area
                        $('html, body').animate({
                            scrollTop: $("#error-display-area").offset().top - 100
                        }, 1000);

                        $('.errorSound')[0].play();

                        // Show summary toastr error message
                        let errorCount = response.validation_errors.length;
                        let uniqueRows = Object.keys(errorsByRow).filter(k => k !== 'general')
                            .length;
                        let summary =
                            `Import failed with ${errorCount} error${errorCount > 1 ? 's' : ''} across ${uniqueRows} row${uniqueRows > 1 ? 's' : ''}. Please check the detailed errors above.`;
                        toastr.error(summary, 'Import Failed', {
                            timeOut: 8000,
                            extendedTimeOut: 3000
                        });
                    }
                } else if (response.status == 500) {
                    // Server error
                    $("#importProductForm")[0].reset();
                    $('.errorSound')[0].play();
                    toastr.error(response.message || 'Server error occurred during import.',
                        'Server Error');
                }

                $('.progress').hide();
                $('#import_btn').prop('disabled', false).text('Upload');
            },
            error: function(xhr, status, error) {
                // Clear existing toastr notifications
                toastr.clear();

                console.error("AJAX Error:", xhr.responseText);
                $('.errorSound')[0].play();

                let errorMessage = 'An error occurred while uploading the file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Upload Error');
                $('.progress').hide();
                $('#import_btn').prop('disabled', false).text('Upload');
            },
            complete: function() {
                $('.progress').hide();
                $('#import_btn').prop('disabled', false).text('Upload');
            }
        });
    });

    // Batch Prices Modal Functions
    function loadBatchPricesModal(productId) {
        $.ajax({
            url: `/product/${productId}/batches`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    populateBatchPricesModal(response.product, response.batches);
                    $('#batchPricesModal').modal('show');
                } else {
                    toastr.error('Failed to load batch data', 'Error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading batch data:', error);
                toastr.error('Failed to load batch data', 'Error');
            }
        });
    }

    function populateBatchPricesModal(product, batches) {
        // Set product info
        $('#productName').text(product.product_name);
        $('#productSku').text(product.sku);

        // Clear both table and mobile containers
        $('#batchPricesTableBody').empty();
        $('#batchPricesMobile').empty();

        if (batches.length === 0) {
            $('#batchPricesTableBody').append(
                '<tr><td colspan="9" class="text-center text-muted">No batches found for this product</td></tr>'
            );
            $('#batchPricesMobile').append(
                '<div class="alert alert-info text-center">No batches found for this product</div>'
            );
            $('#saveBatchPrices').hide();
            return;
        }

        $('#saveBatchPrices').show();

        // Check if unit allows decimals
        const allowDecimal = product.unit && product.unit.allow_decimal;

        batches.forEach(function(batch, index) {
            // Format quantities based on unit decimal setting
            const formattedQty = allowDecimal ? parseFloat(batch.qty || 0).toFixed(2) : parseInt(batch.qty ||
                0);

            // Format locations with proper quantity formatting
            let locationsText = 'No locations';
            if (batch.locations && batch.locations.length > 0) {
                locationsText = batch.locations.map(loc => {
                    const locQty = allowDecimal ? parseFloat(loc.qty || 0).toFixed(2) : parseInt(loc
                        .qty || 0);
                    return `${loc.name} (${locQty})`;
                }).join(', ');
            }

            // Format expiry date
            let expiryDate = batch.expiry_date || '-';
            if (batch.expiry_date) {
                expiryDate = new Date(batch.expiry_date).toLocaleDateString();
            }

            // Desktop table row (hidden on mobile)
            const row = `
                <tr data-batch-id="${batch.id}" class="d-none d-md-table-row">
                    <td>${batch.batch_no || 'N/A'}</td>
                    <td>${formattedQty}</td>
                    <td class="text-muted d-none d-md-table-cell">${parseFloat(batch.original_price || 0).toFixed(2)}</td>
                    <td><input type="number" class="form-control form-control-sm price-input" name="wholesale_price" value="${parseFloat(batch.wholesale_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00"></td>
                    <td><input type="number" class="form-control form-control-sm price-input" name="special_price" value="${parseFloat(batch.special_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00"></td>
                    <td><input type="number" class="form-control form-control-sm price-input" name="retail_price" value="${parseFloat(batch.retail_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00"></td>
                    <td><input type="number" class="form-control form-control-sm price-input" name="max_retail_price" value="${parseFloat(batch.max_retail_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00"></td>
                    <td class="d-none d-lg-table-cell">${expiryDate}</td>
                    <td class="d-none d-lg-table-cell"><small class="text-muted">${locationsText}</small></td>
                </tr>
            `;
            $('#batchPricesTableBody').append(row);

            // Mobile card (hidden on desktop)
            const mobileCard = `
                <div class="card mb-3 d-block d-md-none" data-batch-id="${batch.id}">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <strong>Batch: ${batch.batch_no || 'N/A'}</strong>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">Stock: ${formattedQty}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Wholesale Price</label>
                                <input type="number" class="form-control form-control-sm price-input" name="wholesale_price" value="${parseFloat(batch.wholesale_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Special Price</label>
                                <input type="number" class="form-control form-control-sm price-input" name="special_price" value="${parseFloat(batch.special_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-primary">Retail Price</label>
                                <input type="number" class="form-control form-control-sm price-input border-primary" name="retail_price" value="${parseFloat(batch.retail_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Max Retail Price</label>
                                <input type="number" class="form-control form-control-sm price-input" name="max_retail_price" value="${parseFloat(batch.max_retail_price || 0).toFixed(2)}" min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <strong>Cost Price:</strong> ${parseFloat(batch.original_price || 0).toFixed(2)} |
                                    <strong>Expiry:</strong> ${expiryDate} |
                                    <strong>Locations:</strong> ${locationsText}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#batchPricesMobile').append(mobileCard);
        });
    }

    // Save batch prices
    $('#saveBatchPrices').on('click', function() {
        const batches = [];

        // Collect data from desktop table (visible on md+ screens)
        $('#batchPricesTableBody tr[data-batch-id]').each(function() {
            if ($(this).is(':visible')) {
                const batchId = $(this).data('batch-id');
                const wholesalePrice = $(this).find('input[name="wholesale_price"]').val();
                const specialPrice = $(this).find('input[name="special_price"]').val();
                const retailPrice = $(this).find('input[name="retail_price"]').val();
                const maxRetailPrice = $(this).find('input[name="max_retail_price"]').val();

                batches.push({
                    id: batchId,
                    wholesale_price: parseFloat(wholesalePrice),
                    special_price: parseFloat(specialPrice),
                    retail_price: parseFloat(retailPrice),
                    max_retail_price: parseFloat(maxRetailPrice)
                });
            }
        });

        // Collect data from mobile cards (visible on sm screens)
        $('#batchPricesMobile .card[data-batch-id]').each(function() {
            if ($(this).is(':visible')) {
                const batchId = $(this).data('batch-id');
                const wholesalePrice = $(this).find('input[name="wholesale_price"]').val();
                const specialPrice = $(this).find('input[name="special_price"]').val();
                const retailPrice = $(this).find('input[name="retail_price"]').val();
                const maxRetailPrice = $(this).find('input[name="max_retail_price"]').val();

                batches.push({
                    id: batchId,
                    wholesale_price: parseFloat(wholesalePrice),
                    special_price: parseFloat(specialPrice),
                    retail_price: parseFloat(retailPrice),
                    max_retail_price: parseFloat(maxRetailPrice)
                });
            }
        });

        if (batches.length === 0) {
            toastr.warning('No batch data to save', 'Warning');
            return;
        }

        // Disable save button
        $('#saveBatchPrices').prop('disabled', true).text('Saving...');

        $.ajax({
            url: '/batches/update-prices',
            method: 'POST',
            data: {
                batches: batches,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 200) {
                    toastr.success(response.message, 'Success');
                    $('#batchPricesModal').modal('hide');

                    // Notify all browser tabs that product data has been updated
                    // This will clear caches in POS and other product-related pages
                    localStorage.setItem('product_cache_invalidate', Date.now());
                    setTimeout(() => {
                        localStorage.removeItem('product_cache_invalidate');
                    }, 1000);

                    // Reload DataTable if it exists
                    if ($.fn.DataTable.isDataTable('#productTable')) {
                        $('#productTable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    toastr.error(response.message || 'Failed to update batch prices', 'Error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving batch prices:', error);

                let errorMessage = 'Failed to save batch prices';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    // Handle validation errors
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join(', ');
                }

                toastr.error(errorMessage, 'Error');
            },
            complete: function() {
                // Re-enable save button
                $('#saveBatchPrices').prop('disabled', false).text('Save Changes');
            }
        });
    });

    // Safety net - prevent buttons from being permanently disabled
    setInterval(function() {
        if ($('#product_table').length) {
            var form = $('form[id*="product_form"]');
            if (form.length && form.valid()) {
                form.find('button[type="submit"]').prop('disabled', false);
            }
        }
    }, 5000);
</script>
