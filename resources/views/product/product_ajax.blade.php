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
                success: function(response) {
                    // Handle different response structures
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
                $('#product-selectedImage').attr('src', imagePath).show();
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
                    const locations = response
                        .message; // Ensure this is correct according to your API response
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




        //Delte product

        function deleteProduct(productId) {
            if (swal({
                    title: "Are you sure?",
                    text: "You will not be able to recover this imaginary file!",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })) {
                $.ajax({
                    url: '/delete-product/' + productId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success(response.message, 'Deleted');
                            fetchProductData();
                        } else {
                            toastr.error(response.message || 'Failed to delete product', 'Error');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred while deleting the product', 'Error');
                    }
                });
            }
        }


        // Fetch and cache category, brand, location data
        function fetchCategoriesAndBrands(callback) {
            let loaded = 0;
            fetchData('/main-category-get-all', function(response) {
                (response.message || []).forEach(c => {
                    categoryMap[c.id] = c.mainCategoryName;
                });
                if (++loaded === 3) callback();
            });
            fetchData('/brand-get-all', function(response) {
                (response.message || []).forEach(b => {
                    brandMap[b.id] = b.name;
                });
                if (++loaded === 3) callback();
            });
            fetchData('/location-get-all', function(response) {
                (response.message || []).forEach(l => {
                    locationMap[l.id] = l.name;
                });
                if (++loaded === 3) callback();
            });
        }

        // Populate filter dropdowns from current page data
        function populateProductFilter(pageData) {
            const productNameFilter = $('#productNameFilter');
            const categoryFilter = $('#categoryFilter');
            const brandFilter = $('#brandFilter');
            const productNames = [...new Set(pageData.map(item => item.product.product_name))];
            const categories = [...new Set(pageData.map(item => item.product.main_category_id))];
            const brands = [...new Set(pageData.map(item => item.product.brand_id))];

            productNameFilter.empty().append('<option value="">Select Product</option>');
            categoryFilter.empty().append('<option value="">Select Category</option>');
            brandFilter.empty().append('<option value="">Select Brand</option>');

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
            // You must generate permission logic in PHP and pass as flags, or show all for demo
            return `
                <div class="dropdown">
                    <button class="btn btn-outline-info btn-sm dropdown-toggle action-button" type="button" id="actionsDropdown-${row.id}" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="actionsDropdown-${row.id}">
                        <li><a class="dropdown-item view-product" href="#" data-product-id="${row.id}"><i class="fas fa-eye"></i> View</a></li>
                        <li><a class="dropdown-item" href="/edit-product/${row.id}"><i class="fas fa-edit"></i> Edit</a></li>
                        <li><a class="dropdown-item btn-delete-product" href="#" data-product-id="${row.id}"><i class="fas fa-trash-alt"></i> Delete</a></li>
                        <li><a class="dropdown-item" href="/edit-opening-stock/${row.id}"><i class="fas fa-plus"></i> Add or Edit Opening Stock</a></li>
                        <li><a class="dropdown-item" href="/products/stock-history/${row.id}"><i class="fas fa-history"></i> Product Stock History</a></li>
                        ${row.is_imei_or_serial_no === 1
                            ? `<li><a class="dropdown-item show-imei-modal" href="#" data-product-id="${row.id}"><i class="fas fa-barcode"></i> IMEI Entry</a></li>`
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
                ajax: function(data, callback) {
                    // DataTables sends search and paging params in 'data'
                    let params = {
                        draw: data.draw,
                        start: data.start,
                        length: data.length,
                        // DataTables global search
                        'search[value]': data.search.value,
                        // DataTables ordering
                        'order[0][column]': data.order && data.order.length ? data.order[0]
                            .column : 0,
                        'order[0][dir]': data.order && data.order.length ? data.order[0].dir :
                            'asc',
                        // DataTables columns (for ordering)
                        'columns': data.columns,
                        // Custom filters
                        product_name: $('#productNameFilter').val(),
                        main_category_id: $('#categoryFilter').val(),
                        brand_id: $('#brandFilter').val()
                    };

                    $.ajax({
                        url: '/products/stocks',
                        type: 'GET',
                        data: params,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 200) {
                                // For dropdowns, only update if it's the first page
                                if (data.start === 0) populateProductFilter(response
                                    .data);
                                const tableData = response.data.map(function(item) {
                                    let product = item.product;
                                    product.total_stock = item.total_stock;
                                    product.batches = item.batches;
                                    product.locations = item.locations;
                                    product.imei_numbers = item.imei_numbers ||
                                        [];
                                    product.main_category_id = product
                                        .main_category_id;
                                    product.brand_id = product.brand_id;
                                    return product;
                                });

                                callback({
                                    draw: response.draw,
                                    recordsTotal: response.recordsTotal,
                                    recordsFiltered: response.recordsFiltered,
                                    data: tableData
                                });
                            } else {
                                callback({
                                    draw: data.draw,
                                    recordsTotal: 0,
                                    recordsFiltered: 0,
                                    data: []
                                });
                            }
                        },
                        error: function() {
                            callback({
                                draw: data.draw,
                                recordsTotal: 0,
                                recordsFiltered: 0,
                                data: []
                            });
                        }
                    });
                },
                columns: [{
                        data: null,
                        render: function(data, type, row) {
                            return `<input type="checkbox" class="product-checkbox" data-product-id="${row.id}" style="width: 16px; height: 16px;">`;
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
                        data: "product_name"
                    },
                    {
                        data: "sku"
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let locationName = 'N/A';
                            if (row.batches && row.batches.length > 0) {
                                locationName = row.batches[0]?.location_batches?.[0]
                                    ?.location_name || 'N/A';
                            } else if (row.locations && row.locations.length > 0) {
                                locationName = row.locations[0]?.location_name || 'N/A';
                            }
                            return locationName;
                        }
                    },
                    {
                        data: "retail_price"
                    },
                    {
                        data: "total_stock"
                    },
                    {
                        data: "main_category_id",
                        render: function(data) {
                            return categoryMap[data] || 'N/A';
                        }
                    },
                    {
                        data: "brand_id",
                        render: function(data) {
                            return brandMap[data] || 'N/A';
                        }
                    },
                    {
                        data: "is_imei_or_serial_no",
                        render: function(data) {
                            return data === 1 ? "True" : "False";
                        }
                    }
                ],
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                pageLength: 10,
                ordering: false,
                select: {
                    style: 'multi',
                    selector: 'td:first-child input[type="checkbox"]',
                },
                drawCallback: function() {
                    attachEventHandlers();
                }
            });
        }

        // Attach all event handlers for table actions (call after each draw)
        function attachEventHandlers() {
            // Select all checkbox
            $('#selectAll').off('change').on('change', function() {
                const isChecked = this.checked;
                $('.product-checkbox').prop('checked', isChecked).trigger('change');
            });

            // Prevent sort on action columns
            $('#productTable thead').off('click', 'th').on('click', 'th', function(event) {
                event.stopImmediatePropagation();
            });

            // Dropdown item click
            $('#productTable tbody').off('click', '.dropdown-item').on('click', '.dropdown-item', function(
                event) {
                event.stopPropagation();
            });

            // View product modal
            $('#productTable tbody').off('click', '.view-product').on('click', '.view-product', function(
                event) {
                event.preventDefault();
                var productId = $(this).data('product-id');
                if (productId) {
                    fetchProductDetails(productId);
                    $('#viewProductModal').modal('show');
                }
            });

            // Delete product
            $('#productTable tbody').off('click', '.btn-delete-product').on('click', '.btn-delete-product',
                function(event) {
                    event.preventDefault();
                    var productId = $(this).data('product-id');
                    if (productId) {
                        deleteProduct(productId);
                    }
                });

            // IMEI modal
            $('#productTable tbody').off('click', '.show-imei-modal').on('click', '.show-imei-modal', function(
                event) {
                event.preventDefault();
                const productId = $(this).data('product-id');
                $('#currentProductId').val(productId);
                $('#imeiTableBody').empty();

                // Find row data in current table page
                const table = $('#productTable').DataTable();
                const rowData = table.rows().data().toArray().find(r => r.id == productId);

                if (!rowData || !rowData.imei_numbers || rowData.imei_numbers.length === 0) {
                    $('#imeiTableBody').append(
                        '<tr><td colspan="5" class="text-center">No IMEI numbers found.</td></tr>'
                    );
                    $('#imeiModal').modal('show');
                    return;
                }

                let counter = 1;
                rowData.imei_numbers.forEach(imei => {
                    $('#imeiTableBody').append(`
                <tr>
                    <td>${counter++}</td>
                    <td>
                        <input type="text" class="form-control imei-input"
                            data-imei-id="${imei.id}"
                            value="${imei.imei_number}" ${!imei.editable ? 'disabled' : ''}>
                    </td>
                    <td>${imei.location_name}</td>
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
                    `<span class="text-info">${rowData.product_name}</span> (IMEI NO: ${rowData.imei_numbers.length})`
                );
                $('#imeiModal').modal('show');
            });

            // Prevent row click from triggering on checkbox/dropdown
            $('#productTable tbody').off('click', 'tr').on('click', 'tr', function(event) {
                if ($(event.target).closest(
                        '.product-checkbox, input[type="checkbox"],.dropdown,.dropdown-toggle,.dropdown-menu'
                    ).length > 0) return;
                var productId = $(this).find('.product-checkbox').data('product-id');
                if (productId) {
                    fetchProductDetails(productId);
                    $('#viewProductModal').modal('show');
                }
            });
        }

        // On filter change, reload DataTable (triggers ajax with filters)
        $('#productNameFilter, #categoryFilter, #brandFilter').on('change', function() {
            if ($.fn.DataTable.isDataTable('#productTable')) {
                $('#productTable').DataTable().ajax.reload();
            }
        });

        // Fetch initial dropdown data and product data on page load
        fetchInitialDropdowns(fetchProductData);

        // On page load: fetch categories/brands/locations, then initialize DataTable
        $(document).ready(function() {
            fetchCategoriesAndBrands(fetchProductData);
        });

        function resetFormAndValidation() {
            $('#addForm')[0].reset();
            $('#addForm').validate().resetForm();
            $('#addForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addForm').find('.is-validGreen').removeClass('is-validGreen');
            $('#product-selectedImage').attr('src', '/assets/img/No Product Image Available.png');
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
            fetchData('/get-last-product', function(response) {

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
                const quantityInput = existingRow.find('.purchase-quantity');
                let currentVal = parseFloat(quantityInput.val());
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
            <tr data-id="${product.id}">
                <td>${product.id}</td>
                <td>${product.product_name} <br><small>Stock: ${currentStock}</small></td>
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

                const $newRow = $(newRow);
                table.row.add($newRow).draw();

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

            // Fetch and populate data dynamically only if productId is valid
            if (productId && /^\d+$/.test(productId)) {
                fetchOpeningStockData(productId, isEditMode);
            }

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
                                                <td><input type="text" class="form-control imei-input" value="${imei.imei_number}" data-id="${imei.id}"></td>
                                                <td><button class="btn btn-sm btn-danger removeImei">Remove</button></td>
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

        $('#productTable').on('click', '.view_btn, .edit-product, .btn-delete-product', function(e) {
            e.stopPropagation();
        });







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
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching product details:', error);
                }
            });
        }

    });
</script>


<script>
    $(document).on('submit', '#importProductForm', function(e) {
        e.preventDefault();
        let formData = new FormData($('#importProductForm')[0]);
        let fileInput = $('input[name="file"]')[0];

        // Validate file input
        if (fileInput.files.length === 0) {
            $('#file_error').html('Please select an Excel file.');
            $('.errorSound')[0].play();
            toastr.error('Please select an Excel file.', 'Error');
            return;
        } else {
            $('#file_error').html('');
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
                $('.progress-bar').css('width', '0%').text('0%');
                $('.progress').show();
            },
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                        $('.errorSound')[0].play();
                        toastr.error(err_value, 'Error');
                    });
                } else if (response.status == 200) {
                    $("#importProductForm")[0].reset();
                    $('.successSound')[0].play();
                    toastr.success(response.message, 'Success');
                } else if (response.status == 401) {
                    $("#importProductForm")[0].reset();
                    $('.errorSound')[0].play();
                    toastr.error(response.validation_errors, 'Error');
                }

                $('.progress').hide();
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
                $('.errorSound')[0].play();
                toastr.error('An error occurred while uploading the file.');
                $('.progress').hide();
            },
            complete: function() {
                $("#importProductForm")[0].reset();
                $('.progress').hide();
            }
        });
    });
</script>
