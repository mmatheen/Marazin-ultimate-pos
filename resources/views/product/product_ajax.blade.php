<script>
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

        // Helper function to populate a dropdown with improved caching and error handling
        function fetchData(url, successCallback, errorCallback) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                cache: true, // Enable browser caching for better performance
                timeout: 10000, // 10 second timeout
                success: function(response) {
                    console.log('fetchData response for ' + url + ':', response);
                    
                    // For /get-last-product endpoint, pass the response as-is
                    if (url.includes('/get-last-product')) {
                        successCallback(response);
                        return;
                    }
                    
                    // Handle different response structures for other endpoints
                    if (response.status === true || response.status === 200) {
                        // If response has a 'data' property, use it; otherwise use 'message'
                        const data = response.data || response.message;
                        successCallback({
                            status: 200,
                            message: data
                        });
                    } else {
                        successCallback(response);
                    }
                },
                error: errorCallback || function(xhr, status, error) {
                    console.error('Error fetching data from ' + url + ':', error);
                    if (typeof toastr !== 'undefined') {
                        if (status === 'timeout') {
                            toastr.error('Request timed out. Please try again.', 'Timeout Error');
                        } else {
                            toastr.error('Failed to load data. Please try again.', 'Network Error');
                        }
                    }
                }
            });
        }

        function populateDropdown(selector, items, displayProperty) {
            const selectElement = $(selector).empty();
            items.forEach(item => {
                const option = new Option(item[displayProperty], item.id);
                if (item.selected) {
                    option.selected = true;
                }
                selectElement.append(option);
            });
        }

        function populateInitialDropdowns(mainCategories, subCategories, brands, units, locations, autoSelectSingle, callback) {
            populateDropdown('#edit_main_category_id', mainCategories, 'mainCategoryName');
            populateDropdown('#edit_sub_category_id', subCategories, 'subCategoryname');
            populateDropdown('#edit_brand_id', brands, 'name');
            populateDropdown('#edit_unit_id', units, 'name');
            populateDropdown('#edit_location_id', locations, 'name');
            
            // Auto-select location for Select2 if only one location is available
            if (locations.length === 1 && locations[0].selected) {
                setTimeout(function() {
                    $('#edit_location_id').val([locations[0].id]).trigger('change');
                    console.log('Auto-selected location:', locations[0].name);
                }, 100);
            }
            
            // Populate location filter dropdown with "All Location" option
            populateLocationFilterDropdown(locations, autoSelectSingle);
            
            if (callback) callback();
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
                console.log('Auto-selected single accessible location for filter:', locations[0].name);
            }
        }

        function fetchInitialDropdowns(callback) {
            fetchData('/initial-product-details', function(response) {

                if (response.status === 200) {
                    const brands = response.message.brands;
                    const mainCategories = response.message.mainCategories;
                    subCategories = response.message.subCategories; // Store subcategories globally
                    const units = response.message.units;
                    const locations = response.message.locations;
                    const autoSelectSingle = response.message.auto_select_single_location;

                    populateInitialDropdowns(mainCategories, subCategories, brands, units, locations,
                        autoSelectSingle, callback);
                    
                    // Log auto-selection info
                    if (autoSelectSingle && locations.length === 1) {
                        console.log('Auto-selected single location:', locations[0].name);
                    }
                } else {
                    console.error('Failed to load initial product details. Status:', response.status);
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
            populateInitialDropdowns(mainCategories, subCategories, brands, units, locations, false, function() {
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
                alert('Please select at least one product and one location.');
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


        $('#saveDiscountButton').on('click', function() {
            const discountData = {
                name: $('#discountName').val(), // Fixed typo (was discounTName)
                description: $('#discountDescription').val(),
                type: $('#discountType').val(),
                amount: $('#discountAmount').val(),
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val() || null,
                is_active: $('#isActive').is(':checked') ? 1 :
                0, // Convert to 1/0 instead of true/false
                product_ids: selectedProductIds
            };

            // Validate required fields
            if (!discountData.name || !discountData.type || !discountData.amount || !discountData
                .start_date) {
                toastr.error('Please fill all required fields', 'Error');
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
                        fetchProductData();
                    } else {
                        toastr.error(response.message || 'Failed to apply discount',
                            'Error');
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
            
            // Use native confirm dialog - more reliable
            const confirmMessage = `Are you sure you want to ${action} this product?`;
            
            if (confirm(confirmMessage)) {
                performStatusToggle(productId, action);
            }
        }

        // Separate function to perform the actual AJAX call
        function performStatusToggle(productId, action) {
            $.ajax({
                url: '/toggle-product-status/' + productId,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    console.log('Status toggle response:', response);
                    if (response.status === 200) {
                        toastr.success(response.message, 'Success');
                        // Reload the DataTable
                        if ($.fn.DataTable.isDataTable('#productTable')) {
                            $('#productTable').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        toastr.error(response.message || 'Failed to update status', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    let errorMessage = 'Failed to update product status';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage, 'Error');
                }
            });
        }


        // Fetch and cache category, brand, location data
        function fetchCategoriesAndBrands(callback) {
            let loaded = 0;
            fetchData('/main-category-get-all', function(response) {
                if (Array.isArray(response.message)) {
                    response.message.forEach(c => {
                        categoryMap[c.id] = c.mainCategoryName;
                    });
                }
                if (++loaded === 3) callback();
            });
            fetchData('/brand-get-all', function(response) {
                if (Array.isArray(response.message)) {
                    response.message.forEach(b => {
                        brandMap[b.id] = b.name;
                    });
                }
                if (++loaded === 3) callback();
            });
            fetchData('/location-get-all', function(response) {
                if (Array.isArray(response.message)) {
                    response.message.forEach(l => {
                        locationMap[l.id] = l.name;
                    });
                }
                if (++loaded === 3) callback();
            });
        }

        // Populate filter dropdowns from current page data
        function populateProductFilter(pageData) {
            const productNameFilter = $('#productNameFilter');
            const categoryFilter = $('#categoryFilter');
            const brandFilter = $('#brandFilter');
            const locationFilter = $('#locationFilter');
            
            const productNames = [...new Set(pageData.map(item => item.product.product_name))];
            const categories = [...new Set(pageData.map(item => item.product.main_category_id))];
            const brands = [...new Set(pageData.map(item => item.product.brand_id))];
            const locations = [...new Set(pageData.flatMap(item => item.locations.map(loc => loc.id)))];

            productNameFilter.empty().append('<option value="">Select Product</option>');
            categoryFilter.empty().append('<option value="">Select Category</option>');
            brandFilter.empty().append('<option value="">Select Brand</option>');
            
            // Don't clear location filter as it's populated from initial data
            // locationFilter.empty().append('<option value="">Select Location</option>');

            productNames.forEach(name => {
                productNameFilter.append(`<option value="${name}">${name}</option>`);
            });
            categories.forEach(category => {
                if (categoryMap[category]) categoryFilter.append(
                    `<option value="${category}">${categoryMap[category]}</option>`);
            });
            brands.forEach(brand => {
                if (brandMap[brand]) brandFilter.append(
                    `<option value="${brand}">${brandMap[brand]}</option>`);
            });
            
        }

        function buildActionsDropdown(row) {
            // Debug the row structure
            console.log('Building dropdown for row:', row);
            
            // Ensure we have product data
            if (!row.product) {
                console.error('No product data found in row:', row);
                return '<div class="text-danger">Error: No product data</div>';
            }
            
            // Determine button text and icon based on status - access from nested product object
            const isActive = row.product.is_active === 1 || row.product.is_active === true || row.product.is_active === "1";
            
            console.log('Product status check:', {
                productId: row.product.id,
                rawStatus: row.product.is_active,
                isActive: isActive
            });
            
            // Pass the actual boolean status to the click handler
            const statusButton = isActive
                ? `<li><a class="dropdown-item btn-toggle-status" href="#" data-product-id="${row.product.id}" data-status="true"><i class="fas fa-times-circle text-warning"></i> Deactivate</a></li>`
                : `<li><a class="dropdown-item btn-toggle-status" href="#" data-product-id="${row.product.id}" data-status="false"><i class="fas fa-check-circle text-success"></i> Activate</a></li>`;
            
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
                    data: function(d) {
                        // DataTables sends search and paging params in 'd'
                        const requestData = {
                            draw: d.draw,
                            start: d.start,
                            length: d.length,
                            // DataTables global search
                            'search[value]': d.search.value,
                            // DataTables ordering
                            'order[0][column]': d.order && d.order.length ? d.order[0].column : 0,
                            'order[0][dir]': d.order && d.order.length ? d.order[0].dir : 'asc',
                            // DataTables columns (for ordering)
                            'columns': d.columns,
                            // Custom filters
                            product_name: $('#productNameFilter').val(),
                            main_category_id: $('#categoryFilter').val(),
                            brand_id: $('#brandFilter').val(),
                            location_id: $('#locationFilter').val(),
                            // Show all products (active and inactive) in product list
                            show_all: true
                        };
                        console.log('DataTable request data:', requestData);
                        return requestData;
                    },
                    beforeSend: function(xhr) {
                        console.log('Making AJAX request to /products/stocks');
                    },
                    dataSrc: function(response) {
                        console.log('DataTable response received:', response);
                        console.log('Response type:', typeof response);

                        // DataTables expects an object with at least 'data' property as array
                        if (!response || typeof response !== 'object') {
                            console.error('Invalid JSON response from server');
                            return [];
                        }

                        // If your backend returns {status: 200, data: [...]}, convert to DataTables format
                        if (response.status === 200 && Array.isArray(response.data)) {
                            // Optionally update filters
                            if (response.draw === 1) populateProductFilter(response.data);
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

                        // Show a user-friendly error
                        toastr.error('Failed to load product data. Please check your server response.', 'Error');
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
                            const imagePath = productImage && productImage !== 'null' && productImage !== null &&
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
                            
                            console.log('Business Location render - Row data:', row);
                            console.log('Row locations:', row.locations);
                            console.log('Row batches:', row.batches);
                            
                            if (row.batches && row.batches.length > 0) {
                                // Collect locations with stock from batches (already filtered by backend location scope)
                                const locationStocks = {};
                                
                                row.batches.forEach(batch => {
                                    if (batch.location_batches) {
                                        batch.location_batches.forEach(lb => {
                                            if (lb.quantity > 0) {
                                                if (!locationStocks[lb.location_id]) {
                                                    locationStocks[lb.location_id] = {
                                                        name: lb.location_name,
                                                        qty: 0
                                                    };
                                                }
                                                locationStocks[lb.location_id].qty += parseFloat(lb.quantity);
                                            }
                                        });
                                    }
                                });
                                
                                // Build display array with quantities
                                Object.values(locationStocks).forEach(location => {
                                    if (location.qty > 0) {
                                        locationDisplay.push(`${location.name} (${location.qty})`);
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
                                    const locationName = location.location_name || location.name;
                                    if (locationName && !existingLocationNames.includes(locationName)) {
                                        locationDisplay.push(locationName);
                                    }
                                });
                            }
                            
                            return locationDisplay.length > 0 ? locationDisplay.join(', ') : 'No locations';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            // Get the latest batch retail price if batches exist, otherwise use product retail price
                            let displayPrice = row.product.retail_price || 0;
                            let priceSource = 'default'; // Track whether price is from batch or default
                            
                            if (row.product.batches && row.product.batches.length > 0) {
                                // Get the latest batch (assuming they are ordered by creation date)
                                const latestBatch = row.product.batches[row.product.batches.length - 1];
                                if (latestBatch && latestBatch.retail_price !== null && latestBatch.retail_price !== undefined) {
                                    displayPrice = latestBatch.retail_price;
                                    priceSource = 'batch';
                                }
                            }
                            
                            const formattedPrice = parseFloat(displayPrice).toFixed(2);
                            
                            // Add a small indicator to show price source
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
                            return isActive === 1 || isActive === true 
                                ? '<span class="badge bg-success">Active</span>' 
                                : '<span class="badge bg-secondary">Inactive</span>';
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
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
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
                
                console.log('Button clicked - Raw data:', { 
                    productId: productId, 
                    statusData: statusData, 
                    element: this 
                });
                
                // Convert string to boolean
                const currentStatus = statusData === 'true' || statusData === true || statusData === 1 || statusData === "1";
                
                console.log('Parsed status:', { 
                    statusData: statusData, 
                    currentStatus: currentStatus,
                    typeof_statusData: typeof statusData
                });
                
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
                        
                        if (response.status === 200 && response.data && response.data.length > 0) {
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
                            $('#imeiModalTitle').html('<span class="text-info">Product</span> (IMEI NO: 0)');
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
        $('#productNameFilter, #categoryFilter, #brandFilter, #locationFilter').on('change', function() {
            console.log('Filter changed, reloading DataTable...');
            if ($.fn.DataTable.isDataTable('#productTable')) {
                try {
                    $('#productTable').DataTable().ajax.reload(function(json) {
                        console.log('DataTable reloaded successfully:', json);
                    }, false);
                } catch (error) {
                    console.error('Error reloading DataTable:', error);
                    toastr.error('Error reloading product list', 'Error');
                }
            }
        });

        // Fetch initial dropdown data and product data on page load
        fetchInitialDropdowns(fetchProductData);

        // On page load: fetch categories/brands/locations, then initialize DataTable
        $(document).ready(function() {
            fetchCategoriesAndBrands(fetchProductData);
            
            // Initialize buttons based on current page mode
            const isEditPage = window.location.pathname.includes('/edit-product/');
            if (!isEditPage) {
                // Ensure buttons are in add mode by default
                resetButtonsForAddMode();
            }
        });

        function resetFormAndValidation() {
            $('#addForm')[0].reset();
            $('#addForm').validate().resetForm();
            $('#addForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addForm').find('.is-validGreen').removeClass('is-validGreen');
            $('#product-selectedImage').attr('src', '/assets/img/No Product Image Available.png');
            
            // Clear product ID when resetting (important for edit mode)
            $('#product_id').val('');
            
            // Reset buttons to add mode text
            resetButtonsForAddMode();
            
            // Clear summernote content
            if ($('#summernote').length) {
                $('#summernote').summernote('code', '');
            }
        }

        // Global flag to track submission state
        let isSubmitting = false;

        // Function to get the form action URL based on whether we are adding or updating
        function getFormActionUrl() {
            const productId = $('#product_id').val();
            return productId ? `/product/update/${productId}` : '/product/store';

        }

        // Function to handle form submission
        function handleFormSubmit(buttonType) {
            if (isSubmitting) {
                // Clear existing toastr notifications before showing new one
                toastr.clear();
                toastr.warning('Form is already being submitted. Please wait.', 'Please Wait');
                return; // Prevent further execution
            }

            isSubmitting = true; // Set the flag to indicate that the form is being submitted

            let form = $('#addForm')[0];
            let formData = new FormData(form);

            // Add Summernote content to form data if necessary
            if ($('#summernote').length) {
                formData.append('description', $('#summernote').val());
            }

            // Validate the form before submitting
            if (!$('#addForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // Play warning sound
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                isSubmitting = false;
                return; // Return if form is not valid
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
                                resetFormAndValidation();
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
                                // Edit mode - show success message and go to add product page
                                toastr.clear(); // Clear any existing notifications
                                toastr.success('Product updated successfully!', 'Updated');
                                window.location.href = '/add-product';
                            } else {
                                // Add mode - normal behavior
                                toastr.clear(); // Clear any existing notifications
                                toastr.success(response.message, 'Success');
                                resetFormAndValidation();
                                // Also fetch and add the last product to purchase table if it exists
                                if ($('#purchase_product').length > 0) {
                                    fetchLastAddedProducts();
                                }
                                // For "Save & Add Another", we stay on the same page to add more products
                            }
                        } else if (buttonType === 'saveAndOpeningStock') {
                            const productId = $('#product_id').val();
                            toastr.clear(); // Clear any existing notifications
                            toastr.success(response.message, 'Success');
                            if (productId) {
                                window.location.href = `/edit-opening-stock/${response.product_id || productId}`;
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
                    isSubmitting = false; // Reset the flag after the request completes (success or failure)
                    // Re-enable all buttons
                    $('#onlySaveProductButton, #SaveProductButtonAndAnother, #openingStockAndProduct').prop('disabled', false);
                }
            });
        }

        // Button click event handlers with debouncing
        $('#onlySaveProductButton').click(function(e) {
            e.preventDefault();
            $(this).prop('disabled', true); // Disable button immediately
            handleFormSubmit('onlySave');
            // Re-enable button after a delay (will be handled in complete callback)
        });

        $('#SaveProductButtonAndAnother').click(function(e) {
            e.preventDefault();
            $(this).prop('disabled', true); // Disable button immediately
            handleFormSubmit('saveAndAnother');
        });

        $('#openingStockAndProduct').click(function(e) {
            e.preventDefault();
            $(this).prop('disabled', true); // Disable button immediately
            handleFormSubmit('saveAndOpeningStock');
        });



        function fetchLastAddedProducts() {
            fetchData('/get-last-product', function(response) {
                console.log('Last product response:', response);
                
                if (response.status === 200) {
                    const product = response.product;
                    console.log('Adding product to table:', product);
                    
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
            console.log('Adding product to table:', product);
            
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
                console.log('Using existing DataTable');
            } else {
                console.log('DataTable not initialized, using regular table');
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
                console.log('Updated existing row quantity to:', newQuantity);
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
                <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;

                try {
                    const $newRow = $(newRow);
                    
                    if (isDataTable) {
                        // Add to DataTable
                        table.row.add($newRow).draw();
                        console.log('Added new row to DataTable');
                    } else {
                        // Add to regular table
                        $('#purchase_product tbody').append($newRow);
                        console.log('Added new row to regular table');
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
                '<option selected disabled>Sub Category</option>');

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
                            $('#edit_sub_category_id').append('<option value="">No subcategories available</option>');
                        }
                    } else {
                        console.log('Error: ', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ', error);
                    // Handle 404 or other errors gracefully
                    $('#edit_sub_category_id').append('<option value="">Error loading subcategories</option>');
                }
            });
        });


        $(document).ready(function() {
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
                handleFormSubmission(isEditMode, productId);
            });

            $('#submitOpeningStock-saveAndAddAnother').click(function(e) {
                e.preventDefault();
                handleFormSubmission(isEditMode, productId);
                // After successful submission, redirect to add product page
                setTimeout(function() {
                    window.location.href = '/add-product';
                }, 1000);
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
                                        const formattedQuantity = formatQuantity(batch.quantity);
                                        
                                        // Handle null expiry date
                                        const expiryDate = batch.expiry_date && batch.expiry_date !== 'null' ? batch.expiry_date : '';
                                        
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
            $(document).on('click', '.removeRowBtn', function() {
                $(this).closest('tr').remove();
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

            // function handleFormSubmission(isEditMode, productId) {
            //     let form = $('#openingStockForm')[0];
            //     let formData = new FormData(form);

            //     let locations = [];
            //     formData.forEach((value, key) => {
            //         if (key.includes('locations') && value) {
            //             let parts = key.split('[');
            //             let index = parts[1].split(']')[0];
            //             if (!locations[index]) {
            //                 locations[index] = {};
            //             }
            //             let field = parts[2].split(']')[0];
            //             locations[index][field] = value;
            //         }
            //     });

            //     // Filter out locations with empty qty and ensure expiry_date is set
            //     locations = locations.filter(location => location.qty).map(location => {
            //         if (!location.expiry_date) {
            //             location.expiry_date = ''; // or any default value you consider
            //         }
            //         return location;
            //     });

            //     // if (!validateBatchNumbers(locations)) {
            //     //     toastr.error(
            //     //         'Invalid Batch Number. It should start with "BATCH" followed by at least 3 digits.',
            //     //         'Warning');
            //     //     return;
            //     // }

            //     let url = isEditMode ? `/opening-stock/${productId}` : `/opening-stock/${productId}`;
            //     $.ajax({
            //         url: url,
            //         type: 'POST',
            //         headers: {
            //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            //         },
            //         data: JSON.stringify({ locations }),
            //         contentType: 'application/json',
            //         processData: false,
            //         success: function(response) {
            //             if (response.status === 200) {
            //                 toastr.success(response.message, 'Success');
            //                 window.location.href = '/list-product';
            //             } else {
            //                 toastr.error(response.message, 'Error');
            //             }
            //         },
            //         error: function(xhr) {
            //             if (xhr.status === 422) {
            //                 let errors = xhr.responseJSON.errors;
            //                 $.each(errors, function(key, val) {
            //                     $(`#${key}_error`).text(val[0]);
            //                 });
            //             } else {
            //                 toastr.error('Unexpected error occurred', 'Error');
            //             }
            //         }
            //     });
            // }
            function handleFormSubmission(isEditMode, productId) {
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


                                    $.ajax({
                                        url: '/save-or-update-imei',
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': $(
                                                'meta[name="csrf-token"]'
                                            ).attr('content')
                                        },
                                        data: JSON.stringify({
                                            product_id: productId,
                                            batches: response
                                                .batches,
                                            imeis: imeis
                                        }),
                                        contentType: 'application/json',
                                        success: function(imeiRes) {
                                            if (imeiRes.status ===
                                                200) {
                                                toastr.success(imeiRes
                                                    .message,
                                                    'Success');
                                                $('#imeiModal').modal(
                                                    'hide');
                                                window.location.href =
                                                    '/list-product';
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
                                        window.location.href = '/list-product';
                                    });
                            } else {
                                toastr.success(response.message, 'Success');
                                setTimeout(() => {
                                    window.location.href = '/list-product';
                                }, 1000);
                            }
                        } else {
                            toastr.error(response.message, 'Error');
                        }
                    },
                    error: function(xhr) {
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
            const pathSegments = window.location.pathname.split('/');
            const productId = pathSegments[pathSegments.length - 1];

            // Only proceed if productId is a positive integer
            if (/^\d+$/.test(productId)) {
                // Show loading indicator
                if (typeof toastr !== 'undefined') {
                    toastr.info('Loading product details...', 'Please wait');
                }
                
                fetchInitialDropdowns(() => {
                    $.ajax({
                        url: `/edit-product/${productId}`,
                        type: 'GET',
                        dataType: 'json',
                        cache: true, // Enable caching for better performance
                        success: function(response) {
                            if (response.status === 200) {
                                const product = response.message.product;
                                const mainCategories = response.message.mainCategories;
                                const subCategories = response.message.subCategories;
                                const brands = response.message.brands;
                                const units = response.message.units;
                                const locations = response.message.locations;

                                populateProductDetails(product, mainCategories,
                                    subCategories, brands, units, locations);
                                
                                // Hide loading indicator
                                if (typeof toastr !== 'undefined') {
                                    toastr.clear();
                                    toastr.success('Product details loaded successfully', 'Success');
                                }
                            } else {
                                console.error('Error: ' + response.message);
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('Failed to load product details: ' + response.message, 'Error');
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('An error occurred while fetching product details:', error);
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Failed to load product details. Please try again.', 'Error');
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
                                        
                                        if (product.batches && product.batches.length > 0) {
                                            const latestBatch = product.batches[product.batches.length - 1];
                                            if (latestBatch && latestBatch.retail_price !== null && latestBatch.retail_price !== undefined) {
                                                displayPrice = latestBatch.retail_price;
                                                priceSource = `Latest Batch Price (${latestBatch.batch_no || 'N/A'})`;
                                            }
                                        }
                                        
                                        return `Rs. ${parseFloat(displayPrice).toFixed(2)} <br><small class="text-muted">${priceSource}</small>`;
                                    })()}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Imei is Checked</th>
                                    <td>${product.is_imei_or_serial_no === 1 ? "True" : "False"}</td>
                                </tr>
    
                            </tbody>
                        </table>
                    </div>
                `;
                        $('#productDetails').html(details);
                    } else {
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
        $.ajax({
            url: '/initial-product-details',
            method: 'GET',
            success: function(response) {
                console.log('Location loading response:', response);
                if (response.status === 200 && response.message.locations) {
                    let locationSelect = $('#import_location');
                    locationSelect.empty();
                    locationSelect.append('<option value="">Choose Location to Import Products...</option>');
                    
                    response.message.locations.forEach(function(location) {
                        let selected = location.selected ? 'selected' : '';
                        locationSelect.append(`<option value="${location.id}" ${selected}>${location.name}</option>`);
                    });

                    // If only one location and auto-select is enabled, auto-select it
                    if (response.message.auto_select_single_location && response.message.locations.length === 1) {
                        locationSelect.val(response.message.locations[0].id);
                    }
                    
                    console.log('Successfully loaded', response.message.locations.length, 'locations for import');
                } else {
                    console.error('Invalid response structure:', response);
                    toastr.error('Invalid response when loading locations.', 'Error');
                }
            },
            error: function(xhr) {
                console.error('Error loading locations:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                let errorMsg = 'Failed to load locations. Please refresh the page.';
                if (xhr.status === 404) {
                    errorMsg = 'Location service not found. Please contact support.';
                } else if (xhr.status === 403) {
                    errorMsg = 'Access denied. Please check your permissions.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error while loading locations. Please try again.';
                }
                
                toastr.error(errorMsg, 'Error');
                
                // Fallback: try to load using the alternative location endpoint
                $.ajax({
                    url: '/location-get-all',
                    method: 'GET',
                    success: function(fallbackResponse) {
                        console.log('Fallback location response:', fallbackResponse);
                        if (fallbackResponse.status === 200 && fallbackResponse.message) {
                            let locationSelect = $('#import_location');
                            locationSelect.empty();
                            locationSelect.append('<option value="">Choose Location to Import Products...</option>');
                            
                            fallbackResponse.message.forEach(function(location) {
                                locationSelect.append(`<option value="${location.id}">${location.name}</option>`);
                            });
                            
                            toastr.success('Locations loaded successfully using fallback method.', 'Success');
                            console.log('Fallback: Successfully loaded', fallbackResponse.message.length, 'locations');
                        }
                    },
                    error: function(fallbackXhr) {
                        console.error('Fallback also failed:', fallbackXhr);
                        toastr.error('Both primary and fallback location loading failed. Please contact support.', 'Critical Error');
                    }
                });
            }
        });
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
                    toastr.success(response.message || `Import successful! Products have been imported.`, 'Import Complete', {
                        timeOut: 5000,
                        extendedTimeOut: 2000
                    });
                    
                    // Show navigation button after a delay
                    setTimeout(function() {
                        toastr.info('<div class="text-center"><a href="/list-product" class="btn btn-primary btn-sm">View Products</a></div>', 'Next Step', {
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
                        
                        let errorHtml = '<div class="alert alert-danger"><h5><i class="fas fa-exclamation-triangle"></i> Import Failed</h5>';
                        errorHtml += '<p><strong>' + response.message + '</strong></p>';
                        errorHtml += '<div class="row">';
                        errorHtml += '<div class="col-md-6">';
                        errorHtml += '<p><strong>Total Errors:</strong> ' + response.validation_errors.length + '</p>';
                        errorHtml += '</div>';
                        errorHtml += '</div>';
                        
                        // Create a scrollable detailed error area
                        errorHtml += '<div class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">';
                        errorHtml += '<h6>Detailed Errors:</h6>';
                        
                        Object.keys(errorsByRow).forEach(function(rowKey) {
                            if (rowKey === 'general') {
                                errorHtml += '<div class="mb-2"><strong>General Errors:</strong><ul class="mb-0">';
                                errorsByRow[rowKey].forEach(function(error) {
                                    errorHtml += '<li class="text-danger">' + error + '</li>';
                                });
                                errorHtml += '</ul></div>';
                            } else {
                                errorHtml += '<div class="mb-2"><strong>Row ' + rowKey + ':</strong><ul class="mb-0">';
                                errorsByRow[rowKey].forEach(function(error) {
                                    errorHtml += '<li class="text-danger">' + error + '</li>';
                                });
                                errorHtml += '</ul></div>';
                            }
                        });
                        
                        errorHtml += '</div>';
                        errorHtml += '<div class="mt-3">';
                        errorHtml += '<div class="d-flex justify-content-between align-items-start">';
                        errorHtml += '<div>';
                        errorHtml += '<p class="mb-0"><i class="fas fa-info-circle"></i> <strong>Next Steps:</strong></p>';
                        errorHtml += '<ol class="mb-0">';
                        errorHtml += '<li>Fix the highlighted errors in your Excel file</li>';
                        errorHtml += '<li>Ensure all required fields are filled</li>';
                        errorHtml += '<li>Upload the corrected file again</li>';
                        errorHtml += '</ol>';
                        errorHtml += '</div>';
                        errorHtml += '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="$(\'#error-display-area\').html(\'\'); $(this).closest(\'.alert\').fadeOut();" title="Clear errors">';
                        errorHtml += '<i class="fas fa-times"></i> Clear';
                        errorHtml += '</button>';
                        errorHtml += '</div>';
                        errorHtml += '</div></div>';
                        
                        // Display errors in the error area
                        if ($('#error-display-area').length) {
                            $('#error-display-area').html(errorHtml);
                        } else {
                            // Create error display area if it doesn't exist
                            let errorArea = '<div id="error-display-area" class="mt-3">' + errorHtml + '</div>';
                            $('#importProductForm').after(errorArea);
                        }
                        
                        // Scroll to error area
                        $('html, body').animate({
                            scrollTop: $("#error-display-area").offset().top - 100
                        }, 1000);
                        
                        $('.errorSound')[0].play();
                        
                        // Show summary toastr error message
                        let errorCount = response.validation_errors.length;
                        let uniqueRows = Object.keys(errorsByRow).filter(k => k !== 'general').length;
                        let summary = `Import failed with ${errorCount} error${errorCount > 1 ? 's' : ''} across ${uniqueRows} row${uniqueRows > 1 ? 's' : ''}. Please check the detailed errors above.`;
                        toastr.error(summary, 'Import Failed', {
                            timeOut: 8000,
                            extendedTimeOut: 3000
                        });
                    }
                } else if (response.status == 500) {
                    // Server error
                    $("#importProductForm")[0].reset();
                    $('.errorSound')[0].play();
                    toastr.error(response.message || 'Server error occurred during import.', 'Server Error');
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
            const formattedQty = allowDecimal ? parseFloat(batch.qty || 0).toFixed(2) : parseInt(batch.qty || 0);
            
            // Format locations with proper quantity formatting
            let locationsText = 'No locations';
            if (batch.locations && batch.locations.length > 0) {
                locationsText = batch.locations.map(loc => {
                    const locQty = allowDecimal ? parseFloat(loc.qty || 0).toFixed(2) : parseInt(loc.qty || 0);
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
                    <td><input type="number" class="form-control form-control-sm" name="wholesale_price" value="${parseFloat(batch.wholesale_price || 0).toFixed(2)}" min="0" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="special_price" value="${parseFloat(batch.special_price || 0).toFixed(2)}" min="0" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="retail_price" value="${parseFloat(batch.retail_price || 0).toFixed(2)}" min="0" step="0.01"></td>
                    <td><input type="number" class="form-control form-control-sm" name="max_retail_price" value="${parseFloat(batch.max_retail_price || 0).toFixed(2)}" min="0" step="0.01"></td>
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
                                <label class="form-label small">Wholesale Price</label>
                                <input type="number" class="form-control form-control-sm" name="wholesale_price" value="${parseFloat(batch.wholesale_price || 0).toFixed(2)}" min="0" step="0.01">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Special Price</label>
                                <input type="number" class="form-control form-control-sm" name="special_price" value="${parseFloat(batch.special_price || 0).toFixed(2)}" min="0" step="0.01">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Retail Price</label>
                                <input type="number" class="form-control form-control-sm" name="retail_price" value="${parseFloat(batch.retail_price || 0).toFixed(2)}" min="0" step="0.01">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Max Retail Price</label>
                                <input type="number" class="form-control form-control-sm" name="max_retail_price" value="${parseFloat(batch.max_retail_price || 0).toFixed(2)}" min="0" step="0.01">
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
</script>