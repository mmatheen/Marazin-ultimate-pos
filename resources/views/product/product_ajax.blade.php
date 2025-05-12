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
                // sub_category_id: {
                //     required: true
                // },
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
                // sub_category_id: {
                //     required: "Sub Category is required"
                // },
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

        // Helper function to populate a dropdown
        function fetchData(url, successCallback, errorCallback) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: successCallback,
                error: errorCallback || function(xhr, status, error) {
                    console.error('Error fetching data from ' + url + ':', error);
                }
            });
        }

        function populateDropdown(selector, items, displayProperty) {
            const selectElement = $(selector).empty();
            items.forEach(item => {
                selectElement.append(new Option(item[displayProperty], item.id));
            });
        }

        function populateInitialDropdowns(mainCategories, subCategories, brands, units, locations, callback) {
            populateDropdown('#edit_main_category_id', mainCategories, 'mainCategoryName');
            populateDropdown('#edit_sub_category_id', subCategories, 'subCategoryname');
            populateDropdown('#edit_brand_id', brands, 'name');
            populateDropdown('#edit_unit_id', units, 'name');
            populateDropdown('#edit_location_id', locations, 'name');
            if (callback) callback();
        }

        function fetchInitialDropdowns(callback) {
            fetchData('/initial-product-details', function(response) {
                if (response.status === 200) {
                    const brands = response.message.brands;
                    const mainCategories = response.message.mainCategories;
                    subCategories = response.message.subCategories; // Store subcategories globally
                    const units = response.message.units;
                    const locations = response.message.locations;

                    populateInitialDropdowns(mainCategories, subCategories, brands, units, locations,
                        callback);
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
                $('#selectedImage').attr('src', imagePath).show();
            }

            // Populate initial dropdowns with callback to set selected values
            populateInitialDropdowns(mainCategories, subCategories, brands, units, locations, function() {
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

       // Format product data into table rows
function formatProductData(product) {
    let locationName = 'N/A';
    if (product.batches && product.batches.length > 0) {
        locationName = product.batches[0]?.location_batches?.[0]?.location_name || 'N/A';
    } else if (product.locations && product.locations.length > 0) {
        locationName = product.locations[0]?.location_name || 'N/A';
    }

    let imagePath = product.product_image ? `/assets/images/${product.product_image}` :
        '/assets/img/No Product Image Available.png';
    return `
    <tr data-product-id="${product.id}">
    <td>  <input type="checkbox" class="product-checkbox" data-product-id="${product.id}" style="width: 16px; height: 16px;"></td>
        <td>
            <div class="dropdown">
                <button class="btn btn-outline-info btn-sm dropdown-toggle action-button" type="button" id="actionsDropdown-${product.id}" data-bs-toggle="dropdown" aria-expanded="false">
                    Actions
                </button>
                <ul class="dropdown-menu" aria-labelledby="actionsDropdown-${product.id}">
                    @can("show one product details")
                        <li><a class="dropdown-item view-product" href="#" data-product-id="${product.id}"><i class="fas fa-eye"></i> View</a></li>
                    @endcan
                    @can("edit product")
                        <li><a class="dropdown-item" href="/edit-product/${product.id}"><i class="fas fa-edit"></i> Edit</a></li>
                    @endcan
                    @can("delete product")
                        <li><a class="dropdown-item delete-product-btn" href="#" data-product-id="${product.id}"><i class="fas fa-trash-alt"></i> Delete</a></li>
                    @endcan
                    @can("Add & Edit Opening Stock product")
                        <li><a class="dropdown-item" href="/edit-opening-stock/${product.id}"><i class="fas fa-plus"></i> Add or Edit Opening Stock</a></li>
                    @endcan
                    @can("product Full History")
                        <li><a class="dropdown-item" href="/products/stock-history/${product.id}"><i class="fas fa-history"></i> Product Stock History</a></li>
                    @endcan
                </ul>
            </div>
        </td>
        <td><img src="${imagePath}" alt="${product.product_name}" width="50" height="50"></td>
        <td>${product.product_name}</td>
        <td>${locationName}</td>
        <td>${product.retail_price}</td>
        <td>${product.total_stock}</td>
        <td>${product.product_type}</td>
        <td>${categoryMap[product.main_category_id] || 'N/A'}</td>
        <td>${brandMap[product.brand_id] || 'N/A'}</td>
        <td>${product.sku}</td>
        <td>${product.discounts ? product.discounts.map(d => d.name).join(', ') : 'None'}</td>
    </tr>`;
}


        // Delete product functionality
        $(document).on('click', '.delete-product-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = $(this).data('product-id');
            const productName = $(this).closest('tr').find('td:nth-child(4)').text().trim();
            
            // Show confirmation modal
            Swal.fire({
                title: 'Are you sure?',
                html: `You are about to delete <strong>${productName}</strong> and all its associated batches.<br>This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Deleting...',
                        html: 'Please wait while we delete the product',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request
                    $.ajax({
                        url: `/products/${productId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.status === 200) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                );
                                // Refresh the product table
                                fetchProductData();
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Failed to delete product',
                                    'error'
                                );
                            }
                        },
                        error: function(xhr) {
                            Swal.close();
                            let errorMessage = 'An error occurred while deleting the product';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire(
                                'Error!',
                                errorMessage,
                                'error'
                            );
                        }
                    });
                }
            });
        });


// Collect selected product IDs
let selectedProductIds = [];

$(document).on('change', '.product-checkbox', function() {
    const productId = $(this).data('product-id');
    if (this.checked) {
        selectedProductIds.push(productId);
    } else {
        selectedProductIds = selectedProductIds.filter(id => id !== productId);
    }
    toggleAddLocationButton();
});

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
            const locations = response.message; // Ensure this is correct according to your API response
            const locationSelect = $('#locations');
            locationSelect.empty();
            locations.forEach(function(location) {
                locationSelect.append(new Option(location.name, location.id));
            });
        } else {
            console.error('Failed to fetch locations.');
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
        success: function (response) {
            if (response.status === 'success') {
                toastr.success(response.message, 'Success');
                $('#addLocationModal').modal('hide');
                fetchProductData();
            } else {
                toastr.error(response.message || 'Failed to save changes.', 'Error');
            }
        },
        error: function (xhr) {
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

// Update the checkbox change handler to use the new function
$(document).on('change', '.product-checkbox', function() {
    const productId = $(this).data('product-id');
    if (this.checked) {
        selectedProductIds.push(productId);
    } else {
        selectedProductIds = selectedProductIds.filter(id => id !== productId);
    }
    toggleActionButtons();
});


$('#saveDiscountButton').on('click', function() {
    const discountData = {
        name: $('#discountName').val(),  // Fixed typo (was discounTName)
        description: $('#discountDescription').val(),
        type: $('#discountType').val(),
        amount: $('#discountAmount').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val() || null,
        is_active: $('#isActive').is(':checked') ? 1 : 0,  // Convert to 1/0 instead of true/false
        product_ids: selectedProductIds
    };

    // Validate required fields
    if (!discountData.name || !discountData.type || !discountData.amount || !discountData.start_date) {
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

// Fetch product data and populate the table
function fetchProductData() {
    fetchData('/products/stocks', function(response) {
        if (response.status === 200) {
            allProducts = response.data;
            populateProductFilter();

            if ($.fn.DataTable.isDataTable('#productTable')) {
                $('#productTable').DataTable().destroy();
            }

            $('#productTable tbody').empty();

            response.data.forEach(function(item) {
                let product = item.product;
                product.total_stock = item.total_stock;
                product.batches = item.batches;
                product.locations = item.locations;
                $('#productTable tbody').append(formatProductData(product));
            });

            let table = $('#productTable').DataTable({
                lengthMenu: [
                    [10, 20, 50, 75, 100, 500,1000,1500,2000,-1],
                    [10, 20, 50, 75, 100,500,1000,1500,2000,"All"]
                ],
                columnDefs: [
                    { orderable: false, targets: [1] }
                ],
                select: {
                    style: 'multi',
                    selector: 'td:first-child input[type="checkbox"]',
                },
            });

            $('#selectAll').on('change', function() {
                const isChecked = this.checked;
                $('.product-checkbox').each(function() {
                    this.checked = isChecked;
                    $(this).trigger('change');
                });
            });

            $('#productTable thead').on('click', 'th', function(event) {
                event.stopImmediatePropagation();
            });

            $('#productTable tbody').on('click', '.dropdown-item', function(event) {
                event.stopPropagation();
            });

            $('#productTable tbody').on('click', '.view-product', function(event) {
                event.preventDefault();
                var productId = $(this).data('product-id');
                if (productId) {
                    fetchProductDetails(productId);
                    $('#viewProductModal').modal('show');
                }
            });

            $('#productTable tbody').on('click', '.product-checkbox, input[type="checkbox"]', function(event) {
                event.stopPropagation();
                event.stopImmediatePropagation();
            });

            $('#productTable tbody').on('click', 'tr', function(event) {
                if ($(event.target).closest('.product-checkbox, input[type="checkbox"]').length > 0) {
                    return;
                }

                if ($(event.target).closest('.dropdown, .dropdown-toggle, .dropdown-menu').length > 0) {
                    return;
                }

                var productId = $(this).data('product-id');
                if (productId) {
                    fetchProductDetails(productId);
                    $('#viewProductModal').modal('show');
                }
            });
        } else {
            console.error('Failed to fetch product data.');
        }
    });
}

// Populate product filter dropdowns
function populateProductFilter() {
    const productNameFilter = $('#productNameFilter');
    const categoryFilter = $('#categoryFilter');
    const brandFilter = $('#brandFilter');

    const productNames = [...new Set(allProducts.map(item => item.product.product_name))];
    const categories = [...new Set(allProducts.map(item => item.product.main_category_id))];
    const brands = [...new Set(allProducts.map(item => item.product.brand_id))];

    productNameFilter.empty().append('<option value="">Select Product</option>');
    categoryFilter.empty().append('<option value="">Select Category</option>');
    brandFilter.empty().append('<option value="">Select Brand</option>');

    productNames.forEach(name => {
        productNameFilter.append(`<option value="${name}">${name}</option>`);
    });

    categories.forEach(category => {
        if (categoryMap[category]) {
            categoryFilter.append(
                `<option value="${category}">${categoryMap[category]}</option>`);
        }
    });

    brands.forEach(brand => {
        if (brandMap[brand]) {
            brandFilter.append(`<option value="${brand}">${brandMap[brand]}</option>`);
        }
    });
}

// Filter products based on selected filters
function filterProducts() {
    const selectedProduct = $('#productNameFilter').val();
    const selectedCategory = $('#categoryFilter').val();
    const selectedBrand = $('#brandFilter').val();

    const filteredProducts = allProducts.filter(item => {
        const product = item.product;
        const matchesProduct = selectedProduct ? product.product_name === selectedProduct :
            true;
        const matchesCategory = selectedCategory ? product.main_category_id ==
            selectedCategory : true;
        const matchesBrand = selectedBrand ? product.brand_id == selectedBrand : true;
        return matchesProduct && matchesCategory && matchesBrand;
    });

    let table = $('#productTable').DataTable();
    table.clear().draw();

    filteredProducts.forEach(function(item) {
        let product = item.product;
        product.total_stock = item.total_stock;
        product.batches = item.batches;
        product.locations = item.locations;
        table.row.add($(formatProductData(product))).draw();
    });
}

// Fetch main category, sub-category, location, unit, brand details to select box
function fetchCategoriesAndBrands() {
    fetchData('/main-category-get-all', function(response) {
        response.message.forEach(function(category) {
            categoryMap[category.id] = category.mainCategoryName;
        });
    });

    fetchData('/brand-get-all', function(response) {
        response.message.forEach(function(brand) {
            brandMap[brand.id] = brand.name;
        });
    });

    fetchData('/location-get-all', function(response) {
        if (response.status === 200) {
            response.message.forEach(function(location) {
                locationMap[location.id] = location
                    .name; // Store location name with ID as key
            });
        } else {
            console.error('Failed to load location data. Status: ' + response.status);
        }
    });
}

// Initialize fetching categories and brands
fetchCategoriesAndBrands();

// Fetch initial dropdown data and product data on page load
fetchInitialDropdowns(fetchProductData);

// Apply filters on change
$('#productNameFilter, #categoryFilter, #brandFilter').on('change', filterProducts);

        function resetFormAndValidation() {
            $('#addForm')[0].reset();
            $('#addForm').validate().resetForm();
            $('#addForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addForm').find('.is-validGreen').removeClass('is-validGreen');
            $('#selectedImage').attr('src', '/assets/img/No Product Image Available.png');
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
                toastr.error('Form is already being submitted. Please wait.', 'Warning');
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
                success: function(response) {
                    if (response.status == 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        document.getElementsByClassName('successSound')[0]
                            .play(); // Play success sound
                        toastr.success(response.message, 'Added');

                        if (buttonType === 'onlySave') {
                            resetFormAndValidation();
                            fetchLastAddedProducts();
                            if (window.location.pathname === '/add-product') {
                                window.location.href = '/list-product';
                            }
                            $('#new_purchase_product').modal('hide');
                        } else if (buttonType === 'saveAndAnother') {
                            resetFormAndValidation();
                        } else if (buttonType === 'saveAndOpeningStock') {
                            const productId = $('#product_id').val();
                            if (productId) {
                                window.location.href = `/edit-opening-stock/${response.product_id}`;
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
                    isSubmitting =
                        false; // Reset the flag after the request completes (success or failure)
                }
            });
        }

        // Button click event handlers
        $('#onlySaveProductButton').click(function(e) {
            e.preventDefault();
            handleFormSubmit('onlySave');
        });

        $('#SaveProductButtonAndAnother').click(function(e) {
            e.preventDefault();
            handleFormSubmit('saveAndAnother');
        });

        $('#openingStockAndProduct').click(function(e) {
            e.preventDefault();
            handleFormSubmit('saveAndOpeningStock');
        });

        function fetchLastAddedProducts() {
            fetchData('get-last-product', function(response) {
                if (response.status === 200) {
                    const product = response.product;
                    addProductToTable(product);
                    // toastr.success('New product added to the table!', 'Success');
                } else {
                    toastr.error(response.message || 'Unable to fetch product details.', 'Error');
                }
            });
        }

        function addProductToTable(product, isEditing = false, prices = {}) {
            const table = $("#purchase_product").DataTable();
            let existingRow = null;

            $('#purchase_product tbody tr').each(function() {
                const rowProductId = $(this).data('id')
                if (rowProductId === product.id) {
                    existingRow = $(this);
                    return false;
                }
            });

            if (existingRow && !isEditing) {
                const quantityInput = existingRow.find('.purchase-quantity');
                const newQuantity = parseFloat(quantityInput.val()) + 1;
                quantityInput.val(newQuantity).trigger('input');
            } else {
                const price = parseFloat(prices.price || product.original_price) || 0;
                const retailPrice = parseFloat(prices.retail_price || product.retail_price) || 0;
                const wholesalePrice = parseFloat(prices.whole_sale_price || product.whole_sale_price) || 0;
                const specialPrice = parseFloat(prices.special_price || product.special_price) || 0;
                const maxRetailPrice = parseFloat(prices.max_retail_price || product.max_retail_price) || 0;
                const unitCost = parseFloat(prices.unit_cost || product.original_price) || 0;

                const newRow = `
            <tr data-id="${product.id}">
                <td>${product.id}</td>
                <td>${product.product_name || '-'} <br><small>Stock: ${product.quantity || 0}</small></td>
                <td><input type="number" class="form-control purchase-quantity" value="${prices.quantity || 1}" min="1"></td>
                <td><input type="number" class="form-control unit-price" value="${price.toFixed(2)}" min="0" step="0.01"></td>
                <td><input type="number" class="form-control discount-percent" value="0" min="0" max="100"></td>
                <td><input type="number" class="form-control product-price" value="${price.toFixed(2)}" min="0" step="0.01"></td>
                <td><input type="number" class="form-control wholesale-price" value="${wholesalePrice.toFixed(2)}" min="0" step="0.01"></td>
                <td><input type="number" class="form-control special-price" value="${specialPrice.toFixed(2)}" min="0" step="0.01"></td>
                <td><input type="number" class="form-control max-retail-price" value="${maxRetailPrice.toFixed(2)}" min="0" step="0.01"></td>
                <td class="sub-total">0</td>
                <td><input type="number" class="form-control profit-margin" value="0" min="0"></td>
                <td><input type="number" class="form-control retail-price" value="${retailPrice.toFixed(2)}" min="0" step="0.01"></td>
                <td><input type="date" class="form-control expiry-date" value="${product.expiry_date || ''}"></td>
                <td><input type="text" class="form-control batch-no" value="${product.batch_no || ''}"></td>
                <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;

                const $newRow = $(newRow);
                table.row.add($newRow).draw();
                updateRow($newRow);
                updateFooter();

                $newRow.find(
                    ".purchase-quantity, .discount-percent, .unit-price, .product-price, .retail-price, .wholesale-price, .special-price, .max-retail-price"
                ).on("input", function() {
                    updateRow($newRow);
                    updateFooter();
                });

                $newRow.find(".delete-product").on("click", function() {
                    table.row($newRow).remove().draw();
                    updateFooter();
                });
            }
        }

        function updateRow($row) {
            const quantity = parseFloat($row.find(".purchase-quantity").val()) || 0;
            const unitPrice = parseFloat($row.find(".unit-price").val()) || 0;
            const discountPercent = parseFloat($row.find(".discount-percent").val()) || 0;
            const profitMargin = parseFloat($row.find(".profit-margin").val()) || 0;

            const discountedPrice = unitPrice - (unitPrice * discountPercent) / 100;
            // const retailPrice = discountedPrice + (discountedPrice * profitMargin) / 100;
            const retailPrice = unitCost + (unitCost * profitMargin) / 100;
            const subTotal = discountedPrice * quantity;

            $row.find(".product-price").val(discountedPrice.toFixed(2));
            $row.find(".retail-price").val(retailPrice.toFixed(2));
            $row.find(".sub-total").text(subTotal.toFixed(2));
        }

        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#purchase_product tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
                const subTotal = parseFloat($(this).find('.sub-total').text()) || 0;

                totalItems += quantity;
                netTotalAmount += subTotal;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#total').val(netTotalAmount.toFixed(2));

            const discountType = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#purchase-total').text(`Purchase Total: Rs ${finalTotal.toFixed(2)}`);
            $('#final-total').val(finalTotal.toFixed(2));
            $('#discount-display').text(`(-) Rs ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs ${taxAmount.toFixed(2)}`);

            const advanceBalance = parseFloat($('#advance-payment').val()) || 0;
            const paymentDue = finalTotal - advanceBalance;
            $('.payment-due').text(`Rs ${paymentDue.toFixed(2)}`);
        }

        $('#discount-type, #discount-amount, #tax-type, #advance-payment').on('change input', updateFooter);


        $(".show-picture").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#selectedImage").attr("src", e.target.result);
                        $("#selectedImage").show();
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
                url: 'sub_category-details-get-by-main-category-id/' + main_category_id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        response.message.forEach(function(subCategory) {
                            $('#edit_sub_category_id').append(
                                `<option value="${subCategory.id}">${subCategory.subCategoryname}</option>`
                            );
                        });
                    } else {
                        console.log('Error: ', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ', error);

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


    // Initialize datetime picker for expiry date fields
    function initializeDateTimePicker() {
        $(".expiry-date-picker").datepicker({
            dateFormat: 'yy-mm-dd' // Set the date format as per your requirement
        });
    }

    // Fetch and populate data dynamically
    fetchOpeningStockData(productId, isEditMode);

    $('#addRow').click(function() {
        var index = $('#locationRows tr').length;
        var locationId = $('#locationRows tr:last').data('location-id');
        var locationName = $('#locationRows tr:last td:first p').text();
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
                    <input type="number" class="form-control"
                        name="locations[` + index + `][qty]"
                        value="">
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
            </tr>
        `;
        $('#locationRows').append(newRow);
        initializeDateTimePicker(); // Re-initialize datetime picker for new rows
    });

    $('#submitOpeningStock').click(function(e) {
        e.preventDefault();
        handleFormSubmission(isEditMode, productId);
    });

  
    function fetchOpeningStockData(productId, isEditMode) {
    const url = isEditMode ? `/edit-opening-stock/${productId}` : `/opening-stock/${productId}`;

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (response.status === 200) {
                const product = response.product;
                const locations = response.locations;
                const batches = response.openingStock.batches;

                $('#locationRows').html(''); // Clear existing rows before appending

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
                    const locationBatches = batchesByLocation[location.id] || [];
                    
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
                                    <input type="number" class="form-control"
                                        name="locations[${rowIndex}][qty]"
                                        value="">
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
                                        <input type="number" class="form-control"
                                            name="locations[${rowIndex}][qty]"
                                            value="${batch.quantity}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control"
                                            name="locations[${rowIndex}][unit_cost]"
                                            value="${product.original_price}" readonly>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control batch-no-input"
                                            name="locations[${rowIndex}][batch_no]"
                                            value="${batch.batch_no}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control expiry-date-picker"
                                            name="locations[${rowIndex}][expiry_date]"
                                            value="${batch.expiry_date}">
                                    </td>
                                </tr>
                            `;
                            $('#locationRows').append(newRow);
                            rowIndex++;
                        });
                    }
                });

                initializeDateTimePicker(); // Initialize datetime picker for existing rows

                if (isEditMode) {
                    $('#pageTitle').text('Edit Opening Stock for Product');
                    $('#breadcrumbTitle').text('Edit Opening Stock');
                    $('#submitOpeningStock').text('Update');
                }
            } else {
                console.log('Failed to fetch existing stock data.', 'Error');
            }
        },
        error: function(xhr) {
            console.log('Failed to fetch existing stock data.', 'Error');
        }
    });
}

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

        // Filter out locations with empty qty and ensure expiry_date is set
        locations = locations.filter(location => location.qty).map(location => {
            if (!location.expiry_date) {
                location.expiry_date = ''; // or any default value you consider
            }
            return location;
        });

        // if (!validateBatchNumbers(locations)) {
        //     toastr.error(
        //         'Invalid Batch Number. It should start with "BATCH" followed by at least 3 digits.',
        //         'Warning');
        //     return;
        // }

        let url = isEditMode ? `/opening-stock/${productId}` : `/opening-stock/${productId}`;
        $.ajax({
            url: url,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: JSON.stringify({ locations }),
            contentType: 'application/json',
            processData: false,
            success: function(response) {
                if (response.status === 200) {
                    toastr.success(response.message, 'Success');
                    window.location.href = '/list-product';
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

    // function validateBatchNumbers(locations) {
    //     let isValid = true;
    //     locations.forEach(location => {
    //         if (location.batch_no && !/^BATCH[0-9]{3,}$/.test(location.batch_no)) {
    //             isValid = false;
    //         }
    //     });
    //     return isValid;
    // }
});
        // Extract the product ID from the URL and fetch data if valid
        $(document).ready(function() {
            const pathSegments = window.location.pathname.split('/');
            const productId = pathSegments[pathSegments.length - 1];

            if (productId && productId !== 'add-product' && productId !== 'list-product') {
                fetchInitialDropdowns(() => {
                    $.ajax({
                        url: `/edit-product/${productId}`,
                        type: 'GET',
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
                            } else {
                                console.error('Error: ' + response.message);
                            }
                        },
                        error: function() {
                            console.error(
                                'An error occurred while fetching product details.'
                                );
                        }
                    });
                });
            }
        });

        $('#productTable').on('click', 'tr', function(e) {
            if (!$(e.target).closest('button').length) {
                const productId = $(this).data('id');
                $('#viewProductModal').modal('show');
                fetchProductDetails(productId);
            }
        });

        $('#productTable').on('click', '.view_btn, .edit-product, .delete-product-btn', function(e) {
            e.stopPropagation();
        });

        // $(document).on('click', '.delete-product-btn', function() {
        //     var id = $(this).data('id');
        //     $('#deleteModal').modal('show');
        //     $('#deleting_id').val(id);
        //     $('#deleteName').text('Delete Product');
        // });

        // $(document).on('click', '.confirm_delete_btn', function() {
        //     var id = $('#deleting_id').val();
        //     $.ajax({
        //         url: '/delete-product/' + id,
        //         type: 'DELETE',
        //         headers: {
        //             'X-CSRF-TOKEN': csrfToken
        //         },
        //         success: function(response) {
        //             if (response.status == 404) {
        //                 toastr.error(response.message, 'Error');
        //             } else {
        //                 $('#deleteModal').modal('hide');
        //                 fetchProductData();
        //                 document.getElementsByClassName('successSound')[0].play();
        //                 toastr.success(response.message, 'Deleted');
        //             }
        //         }
        //     });
        // });

        
        // $(document).on('click', '.delete-product', function(e) {
        //     e.preventDefault();
        //     e.stopPropagation();
            
        //     const productId = $(this).data('product-id');
        //     const productName = $(this).closest('tr').find('td:nth-child(4)').text(); // Get product name from table
            
        //     // Show confirmation modal
        //     Swal.fire({
        //         title: 'Are you sure?',
        //         html: `You are about to delete <strong>${productName}</strong> and all its associated batches.<br>This action cannot be undone!`,
        //         icon: 'warning',
        //         showCancelButton: true,
        //         confirmButtonColor: '#d33',
        //         cancelButtonColor: '#3085d6',
        //         confirmButtonText: 'Yes, delete it!'
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             // Show loading indicator
        //             Swal.fire({
        //                 title: 'Deleting...',
        //                 html: 'Please wait while we delete the product',
        //                 allowOutsideClick: false,
        //                 didOpen: () => {
        //                     Swal.showLoading();
        //                 }
        //             });

        //             // Make AJAX request
        //             $.ajax({
        //                 url: `/delete-product/${productId}`,
        //                 type: 'DELETE',
        //                 headers: {
        //                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //                 },
        //                 success: function(response) {
        //                     Swal.close();
        //                     if (response.status === 200) {
        //                         Swal.fire(
        //                             'Deleted!',
        //                             response.message,
        //                             'success'
        //                         );
        //                         // Refresh the product table
        //                         fetchProductData();
        //                     } else {
        //                         Swal.fire(
        //                             'Error!',
        //                             response.message || 'Failed to delete product',
        //                             'error'
        //                         );
        //                     }
        //                 },
        //                 error: function(xhr) {
        //                     Swal.close();
        //                     let errorMessage = 'An error occurred while deleting the product';
        //                     if (xhr.responseJSON && xhr.responseJSON.message) {
        //                         errorMessage = xhr.responseJSON.message;
        //                     }
        //                     Swal.fire(
        //                         'Error!',
        //                         errorMessage,
        //                         'error'
        //                     );
        //                 }
        //             });
        //         }
        //     });
        // });



        $('#viewProductModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var productId = button.data('product-id');

            fetchProductDetails(productId);
        });

        function fetchProductDetails(productId) {
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
                                    <th scope="row">Price</th>
                                    <td>Rs. ${(Number(product.retail_price) || 0).toFixed(2)}</td>
                                </tr>
    
                            </tbody>
                        </table>
                    </div>
                `;
                        $('#productDetails').html(details);
                    } else {
                        console.error('Failed to load product details. Status: ' + response.status);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product details:', error);
                }
            });
        }

                // Initialize the product data fetching
                $(document).ready(function() {
                    fetchProductData();
                });

    });
</script>


{{-- impoprt product file code start --}}
<script>
    $(document).on('submit', '#importProductForm', function(e) {
        e.preventDefault();
        let formData = new FormData($('#importProductForm')[0]);
        let fileInput = $('input[name="file"]')[0];

        // Check if a file is selected
        if (fileInput.files.length === 0) {
            $('#file_error').html('Please select the excel format file.');
            document.getElementsByClassName('errorSound')[0].play(); //for sound
            toastr.error('Please select the excel format file' ,'Error');
            return;
        } else {
            $('#file_error').html('');
        }

        $.ajax({
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percentComplete = e.loaded / e.total * 100;
                        $('.progress').show();
                        $('.progress-bar').css('width', percentComplete + '%');
                        $('.progress-bar').attr('aria-valuenow', percentComplete);
                        $('.progress-bar').text(Math.round(percentComplete) + '%'); // Display the percentage
                    }
                }, false);
                return xhr;
            },
            url: 'import-product-excel-store',
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function() {
                $('.progress-bar').css('width', '0%').text('0%');
                $('.progress').show();
            },
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value); // Assuming there's only one file input with id 'leadFile'
                        document.getElementsByClassName('errorSound')[0].play(); //for sound
                        toastr.error(err_value,'Error');

                    });
                    $('.progress').hide(); // Hide progress bar on validation error
                } else if (response.status == 200) {
                    $("#importProductForm")[0].reset();
                    document.getElementsByClassName('successSound')[0].play(); //for sound
                        toastr.success(response.message, 'Uploaded');
                    $('.progress').hide();
                }
                else if (response.status == 401) {
                    $("#importProductForm")[0].reset();
                    document.getElementsByClassName('errorSound')[0].play(); //for sound
                        toastr.error(response.validation_errors, 'Error');
                    $('.progress').hide();
                }

            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                document.getElementsByClassName('errorSound')[0].play(); //for sound
                toastr.error('An error occurred while processing the request.');
                $('.progress').hide(); // Hide progress bar on request error

            },
            complete: function() {
                $("#importProductForm")[0].reset();
                $('.progress').hide(); // Ensure progress bar is hidden after completion
            }
        });
    });
</script>
